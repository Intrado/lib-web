<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/form.inc.php");
require_once("obj/Setting.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Wizard.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hassurvey', true) || !$USER->authorize('survey') || !$USER->authorize('sendphone','sendemail')) {
	redirect('unauthorized.php');
}

if (isset($_GET['id'])) {
	setCurrentQuestionnaire($_GET['id']);
	Wizard::clear("surveytemplatewiz");
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

//fills in "add" when this button is clicked, and submits the form.
class AddQuestionButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="" />'; //always blank out value
		$str .= icon_button( _L('Add Another Question'),"add", "$('$n').value='add'; form_submit(event,'samestep');");

		return $str;
	}
}

//sets question fields to something to skip validation
//adds "1" to field value to mark question as deleted
class RemoveQuestionButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="" />'; //always blank out value
		$str .= icon_button($this->args['name'],"delete", "$('$n').value='1'; doRemoveQuestion('".$this->form->name."',".$this->args['qnum']."); form_submit(event,'samestep');");

		return $str;
	}

	function renderJavascriptLibraries() {
		return '
		<script type="text/javascript">
		function doRemoveQuestion(formname,qnum) {
			$(formname+"_question"+qnum+"-reportlabel").value="-Deleting-";
			var webtext = $(formname+"_question"+qnum+"-webtext")
			if (webtext)
				webtext.value="-Deleting-";
			var phonemessage = $(formname+"_question"+qnum+"-phonemessage")
			if (phonemessage) {
				phonemessage.value="{\"delete\":true}";
				$(formname+"_question"+qnum+"-phonemessage_content").update("Deleting");
			}
		}
		</script>
		';
	}
}

class PhoneMessageRecorder extends FormItem {

	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (!$value)
			$value = '{}';
		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';

		// set up easycall stylesheet
		$str .= '
		<style type="text/css">
		.easycallcallprogress {
			float:left;
		}
		.easycallunderline {
			padding-top: 3px;
			margin-bottom: 5px;
			border-bottom:
			1px solid gray;
			clear: both;
		}
		.easycallphoneinput {
			margin-bottom: 5px;
			border: 1px solid gray;
		}

		.surveytemplatecontent {
			padding: 6px;
			white-space:nowrap
		}
		</style>';

		$str .= '
		<div>
			<div id="'.$n.'_content" class="surveytemplatecontent"></div>
		</div>
		<script type="text/javascript">
		setupMessageRecorderButtons("'.$n.'");
		</script>
		';

		return $str;
	}

	function renderJavascriptLibraries() {
		global $USER;
		// include the easycall javascript object and setup to record
		$str = '<script type="text/javascript" src="script/easycall.js.php"></script>';
		$str .= '
		<script type="text/javascript">

		function setupMessageRecorderButtons(e) {
			e = $(e);
			var value = e.value.evalJSON();
			var formname = e.up("form").name;
			var content = $(e.id+"_content");

			if (value.m || value.af) {
				var playbtn = icon_button("'.escapehtml(_L('Play')).'", "fugue/control");
				var rerecordbtn = icon_button("'.escapehtml(_L('Re-record')).'", "diagona/16/118");

				playbtn.observe("click", function () {
					var value = e.value.evalJSON();
					if (value.m)
						popup("previewmessage.php?id=" + value.m, 400, 400);
					else if (value.af)
						popup("previewaudio.php?close=1&id="+value.af, 400, 500);
				});

				function curry (fn,obj) {
					return new function() {
						fn(obj);
					}
				}

				rerecordbtn.observe("click", function () {
					setupMessageRecorderEasyCall(e);
				});

				content.update();
				content.insert(playbtn);
				content.insert(rerecordbtn);
			} else {
				setupMessageRecorderEasyCall(e);
			}
		}

		function setupMessageRecorderEasyCall (e) {
			e = $(e);
			var content = $(e.id+"_content");

			new EasyCall(e, content, "'.Phone::format($USER->phone).'", "Survey Message");

			content.observe("EasyCall:update", function(event) {
				e.value = "{\"af\":" + event.memo.audiofileid + "}";
				setupMessageRecorderButtons(e);
				Event.stopObserving(content,"EasyCall:update");
			});
		}
		</script>
		';

		return $str;
	}
}


class PhoneMessageRecorderValidator extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;


		if (!$USER->authorize("starteasy"))
			return _L('%1$s is not allowed for this user account',$this->label);
		$values = json_decode($value);

		if ($values == null || (!isset($values->m) && !isset($values->af) && !isset($values->delete)))
			return _L('%1$s does not have a message recorded', $this->label);

		//special allow for delete
		if (isset($values->delete))
			return true;

		if (isset($values->m)) {
			if (!QuickQuery("select count(*) from message where id=? and userid=?",false,array($values->m,$USER->id)))
				return _L('%1$s has an invalid or missing message', $this->label);
		}

		if (isset($values->af)) {
			if (!QuickQuery("select count(*) from audiofile where id=? and userid=?",false,array($values->af,$USER->id)))
				return _L('%1$s has an invalid or missing message', $this->label);
		}

		return true;
	}
}
////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$questionnaire = new SurveyQuestionnaire(getCurrentQuestionnaire());
if ($questionnaire->id) {
	$questions = array_values(DBFindMany("SurveyQuestion","from surveyquestion where questionnaireid=? order by questionnumber", false, array($questionnaire->id)));
} else {
	$questions = array();
}

if ($questionnaire->emailmessageid) {
	$emailmessage = new Message($questionnaire->emailmessageid);
	$emailmessage->readHeaders();

	$emailparts = DBFindMany("MessagePart","from messagepart where messageid=? order by sequence",false,array($emailmessage->id));
	$emailbody = $emailmessage->format($emailparts);
} else{
	$emailmessage = false;
}


/**************************** settings ****************************/
class SurveyTempleteWiz_settings extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER, $questionnaire, $questions;

		$formdata = array();
		$formdata["name"] = array(
			"label" => _L('Name'),
			"fieldhelp" => _L('The name of your survey. This will appear in reports and on the web survey.'),
			"value" => $questionnaire->name,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 50),
			"helpstep" => 1
		);
		$formdata["description"] = array(
			"label" => _L('Description'),
			"fieldhelp" => _L('Enter an optional description of the survey for later identification.'),
			"value" => $questionnaire->description,
			"validators" => array(
				array("ValLength","max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 50),
			"helpstep" => 1
		);

		//only present option for phone/web if they have a choice between the two
		if ($questionnaire->hasweb && $questionnaire->hasphone)
			$type = "both";
		else if ($questionnaire->hasweb)
			$type = "web";
		else
			$type = "phone";
		if ($USER->authorize('sendphone') && $USER->authorize('sendemail')) {
			$surveytypes = array("phone" => "Phone","web" => "Web", "both" => "Phone and Web");

			$formdata["surveytype"] = array(
				"label" => _L('Survey Method'),
				"fieldhelp" => _L('Select the delivery medium for your survey.'),
				"value" => $type,
				"validators" => array(
					array("ValRequired"),
					array("ValInArray","values" => array_keys($surveytypes))
				),
				"control" => array("RadioButton", "values" => $surveytypes),
				"helpstep" => 2
			);
		}

		$formdata["randomizeorder"] = array(
			"label" => _L('Randomize Question Order'),
			"fieldhelp" => _L('Shuffle the questions to ensure an even spread of responses in the event of recipients exiting the survey early. Do not use this if some of your questions are based on previous questions.'),
			"value" => (bool)$questionnaire->dorandomizeorder,
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 3
		);

		$helpsteps = array(
			_("Enter a name and description for your survey. The name will appear in reports and on the web page associated with the survey."),
			_("Select a survey method. Phone will call contacts on your list and present them with a set of interactive prompts. Web will send an email with a link to a form."),
			_("Randomize Question Order shuffles the order of the questions for each recipient. In the event that recipients decide to leave the survey without finishing it, randomizing increases the likelihood of receiving data for all questions, not just the first few. Unselect this option if some of your questions are based on previous questions.")
		);


		return new Form("settings", $formdata, $helpsteps);
	}
}

/**************************** phone features ****************************/
class SurveyTemplateWiz_phonefeatures extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER, $questionnaire, $questions;

		$formdata = array();
		$helpsteps = array();
		$helpstepnum = 1;

		$formdata[] = "Phone Survey Features";

		$machineoptions = array("message" => _('Leave a message for answering machines'),"hangup" => _('Hang up and try again later'));
		$formdata["amsweringmachine"] = array(
			"label" => _L('Answering Machine'),
			"fieldhelp" => _L('Select an option for what to do when the system calls and reaches an answering machine.'),
			"value" => $questionnaire->machinemessageid ? "message" : "hangup",
			"validators" => array(
				array("ValInArray","values" => array_keys($machineoptions))
			),
			"control" => array("RadioButton","values" => $machineoptions),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = _('If a survey call reaches an answering machine, it can either leave a message letting the person know why they received a call, or hang up and try again later. It is generally better to leave a message. However, hanging up and trying again may result in more survey responses.');

		$introopts = array("message" => _('Play an intro message'),"skip" => _('Skip right to asking questions'));
		$formdata["intromessage"] = array(
			"label" => _L('Introduction'),
			"fieldhelp" => _L("If you'd like to play an introduction message explaining the purpose of your survey, you can opt to here."),
			"value" => $questionnaire->intromessageid ? "message" : "skip",
			"validators" => array(
				array("ValInArray","values" => array_keys($introopts))
			),
			"control" => array("RadioButton","values" => $introopts),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = _('It is best practice to record an introduction message describing the survey process before asking questions. People are more likely to participate in the survey if they know what it is about and why their response is important.');

		$byeopts = array("message" => _('Play a goodbye message'),"reply" => _('Play a goodbye message and allow reply'), "skip" => _('Hangup after the last question'));
		$formdata["goodbyemessage"] = array(
			"label" => _L('Goodbye message'),
			"fieldhelp" => _L('If you\'d like to play a message after the survey, you can opt to here. You may also allow call recipients to leave a reply message.'),
			"value" => $questionnaire->leavemessage ? "reply" : ($questionnaire->exitmessageid ? "message" : "skip"),
			"validators" => array(
				array("ValInArray","values" => array_keys($byeopts))
			),
			"control" => array("RadioButton","values" => $byeopts),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = _('It is best practice to record a goodbye message thanking the person for their time. People will be more likely to answer future surveys when they know their feedback is appreciated.<br><br>Reply messages can used to collect additional information from the person that is not otherwise covered in the survey questions or to provide feedback about the survey. These messages will show up in the Responses tab. <br><br><b>Note:</b> If you allow replies, be sure to mention in your goodbye message that this is available by pressing the zero key.');


		return new Form("phonefeatures", $formdata, $helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		$surveytype = @$postdata['/settings']['surveytype'] ;
		//true if we can send phone, and type is not email only
		return $USER->authorize('sendphone') && $surveytype != "web";
	}

}

/**************************** phone messages ****************************/
class SurveyTemplateWiz_phonemessages extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER, $questionnaire, $questions;

		$formdata = array();
		$helpsteps = array();
		$helpstepnum = 1;


		//answering machine message
		if ($postdata['/phonefeatures']['amsweringmachine'] == "message") {
			$formdata[] = "Answering Machine Message";
			$formdata["machinetext"] = array(
				"label" => "",
				"control" => array("FormHtml", "html" => _L('Record a message for answering machines. This will be played if the person is unavailable and the call reaches an answering machine.')),
				"helpstep" => $helpstepnum++
			);

			$formdata["amsweringmachine"] = array(
				"label" => _L('Answering Machine Message'),
				"fieldhelp" => _L('Enter a phone number where the system can call you.'),
				"value" => $questionnaire->machinemessageid ? '{"m":' . $questionnaire->machinemessageid . '}' : "",
				"validators" => array(
					array("ValRequired"),
					array("PhoneMessageRecorderValidator")
				),
				"control" => array("PhoneMessageRecorder"),
				"helpstep" => $helpstepnum++
			);
			$helpsteps[] = _L('This message will be left in the event of the system reaching an answering machine. You should write your message before you record.');
		}

		//intro message
		if ($postdata['/phonefeatures']['intromessage'] == "message") {
			$formdata[] = "Intro Message";
			$formdata["introtext"] = array(
				"label" => "",
				"control" => array("FormHtml", "html" => _L('Record an introduction message. This will be played when the person answers the phone. Be sure to introduce yourself and explain the phone survey process.')),
				"helpstep" => $helpstepnum++
			);

			$formdata["intromessage"] = array(
				"label" => _L('Intro Message'),
				"fieldhelp" => _L('Enter a phone number where the system can call you.'),
				"value" => $questionnaire->intromessageid ? '{"m":' . $questionnaire->intromessageid . '}' : "",
				"validators" => array(
					array("ValRequired"),
					array("PhoneMessageRecorderValidator")
				),
				"control" => array("PhoneMessageRecorder"),
				"helpstep" => $helpstepnum++
			);
			$helpsteps[] = _L('Before you enter a phone number where the system can call you to record, you should prepare by writing your message down. This message will be played before the survey starts.');
		}

		//goodbye message
		if ($postdata['/phonefeatures']['goodbyemessage'] == "message" ||
			$postdata['/phonefeatures']['goodbyemessage'] == "reply") {

			$formdata[] = "Goodbye Message";
			$formdata["goodbyetext"] = array(
				"label" => "",
				"control" => array("FormHtml", "html" => _L('Record a goodbye / thank you message.')),
				"helpstep" => $helpstepnum++
			);

			$formdata["goodbyemessage"] = array(
				"label" => _L('Goodbye Message'),
				"fieldhelp" => _L('Enter a phone number where the system can call you.'),
				"value" => $questionnaire->exitmessageid ? '{"m":' . $questionnaire->exitmessageid . '}' : "",
				"validators" => array(
					array("ValRequired"),
					array("PhoneMessageRecorderValidator")
				),
				"control" => array("PhoneMessageRecorder"),
				"helpstep" => $helpstepnum++
			);
			$helpsteps[] = _L('This message will be played after the recipient has completed your survey. Best Practice is to thank them for their time. You should write down your message before you try to record.');
		}

		return new Form("phonemessages", $formdata, $helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		$surveytype = @$postdata['/settings']['surveytype'] ;
		$hasphone = $USER->authorize('sendphone') && $surveytype != "web"; //true if we can send phone, and type is not email only

		if (!$hasphone)
			return false;
		if (@$postdata['/phonefeatures']['amsweringmachine'] == "message" ||
			@$postdata['/phonefeatures']['intromessage'] == "message" ||
			@$postdata['/phonefeatures']['goodbyemessage'] == "message" ||
			@$postdata['/phonefeatures']['goodbyemessage'] == "reply")
			return true;
		return false;
	}

}

/**************************** web features ****************************/
class SurveyTemplateWiz_webfeatures extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER, $questionnaire, $questions, $emailmessage, $emailbody;

		//if we are editing a survey email, load name from there, otherwise use defaults
		if ($emailmessage) {
			$fromname = $emailmessage->fromname;
			$fromemail =  $emailmessage->fromemail;
			$subject = $emailmessage->subject;
			$body = $emailbody;
		} else {
			$fromname = $USER->firstname . " " . $USER->lastname;
			$fromemail =  $USER->email;
			$subject = "Web Survey for " . getCustomerSystemSetting("displayname");
			$body = "This is a web survey from " . getCustomerSystemSetting("displayname") . ".\n\nYour responses are important to us. Please follow the link at the end of this email to participate in this survey.\n\nThank you,\n" . getCustomerSystemSetting("displayname");
		}

		$formdata = array();
		$helpsteps = array();
		$helpstepnum = 1;

		$formdata[] = "Web Survey Features";

		$formdata["webpagetitle"] = array(
			"label" => _L('Web Page Title'),
			"fieldhelp" => _L('The Web Page Title will show up in large text on the web survey form.'),
			"value" => $questionnaire->webpagetitle,
			"validators" => array(
				array("ValLength","max"=> 50)
			),
			"control" => array("TextField", "size" => 30, "maxlength" => 50),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = _('You may enter a title here. This will show up in large text on the web survey form.');

		$formdata["webexitmessage"] = array(
			"label" => _L('Web Thank You'),
			"fieldhelp" => _L('This is displayed when the person completes a web survey.'),
			"value" => $questionnaire->webexitmessage,
			"validators" => array(
				array("ValLength","max"=> 32000)
			),
			"control" => array("TextArea", "rows" => 7, "cols" => 30),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = _('Entering a goodbye message thanking the person for their time and responses is highly recommended. People are more likely to participate in future surveys if they have been thanked for their time.');

		$formdata["usehtml"] = array(
			"label" => _L('Use HTML'),
			"fieldhelp" => _L('Allows HTML in title, thank you, and question text'),
			"value" => $questionnaire->usehtml,
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = _('This feature allows you to use HTML in the title, thank you, and question text. HTML lets you insert images or links. You can also add formatting style to the questions.');


		$formdata[] = _('Email Link');

		$formdata["fromname"] = array(
			"label" => _L('From Name'),
			"fieldhelp" => _L('Recipients will see this name as the sender of the email.'),
			"value" => $fromname,
			"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50)
					),
			"control" => array("TextField","size" => 25, "maxlength" => 50),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = _L('Enter the name for the email acount.');


		$formdata["from"] = array(
			"label" => _L('From Email'),
			"fieldhelp" => _L('This is the sender\'s email address. Recipients will also be able to reply to this address.'),
			"value" => $fromemail,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 255),
				array("ValEmail", "domain" => getSystemSetting('emaildomain'))
				),
			"control" => array("TextField","size" => 35, "maxlength" => 255),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = array(_L('Enter the address where you would like to receive replies.'));


		$formdata["subject"] = array(
			"label" => _L('Subject'),
			"fieldhelp" => _L('The Subject will appear as the subject line of the email.'),
			"value" => $subject,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => 0,"max" => 255)
			),
			"control" => array("TextField","size" => 45, "maxlength"=>255),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = _L('Enter the subject of the email here.');

		$formdata["message"] = array(
			"label" => _L('Email Message'),
			"fieldhelp" => _L('Enter the message you would like to send. A link to the survey will be appended to the end of this message.'),
			"value" => $body,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 30000)
			),
			"control" => array("TextArea","rows"=>10,"cols"=>45),
			"helpstep" =>  $helpstepnum++
		);
		$helpsteps[] = _L('The main email message text goes here. Be sure to introduce yourself and give some information about the survey and why their response is important.<br><br><b>Note:</b> A link to the survey will be appended to the end of this message.');


		return new Form("webfeatures", $formdata, $helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		$surveytype = @$postdata['/settings']['surveytype'] ;

		return $USER->authorize('sendemail') && $surveytype != "phone";
	}
}

/**************************** questions ****************************/
class SurveyTemplateWiz_questions extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER, $questionnaire, $questions;

		$formdata = array();
		$helpsteps = array();
		$helpstepnum = 1;

		$surveytype = @$postdata['/settings']['surveytype'] ;
		$hasphone = $USER->authorize('sendphone') && $surveytype != "web"; //true if we can send phone, and type is not email only
		$hasweb = $USER->authorize('sendemail') && $surveytype != "phone"; //true if we can send emails, and type is not phone only
		$responseoptions = array(
			"2" => "1-2",
			"3" => "1-3",
			"4" => "1-4",
			"5" => "1-5",
			"6" => "1-6",
			"7" => "1-7",
			"8" => "1-8",
			"9" => "1-9"
		);
		//get list of questions, and their data from $postdata
		$questiondata = array();
		if (isset($postdata["/questions"])) {
			//see if we should use data from existing form posts
			$qnum = 1;
			$srcnum = 1;
			//reorder deleted questions for display (any repost will result in correct order)
			while (isset($postdata["/questions"]["question$srcnum-reportlabel"])) {
				//check for a delete marker
				if (@$postdata["/questions"]["question$srcnum-delete"]) {
					//error_log("delete q src:$srcnum qnum:$qnum");
					//skip to next question, don't increment new question number
					$srcnum++;
					continue;
				}
				//error_log("adding question src:$srcnum qnum:$qnum");

				$questiondata[$qnum]["reportlabel"] = $postdata["/questions"]["question$srcnum-reportlabel"];
				$questiondata[$qnum]["validresponse"] = $postdata["/questions"]["question$srcnum-validresponse"];
				if ($hasphone)
					$questiondata[$qnum]["phonemessage"] = $postdata["/questions"]["question$srcnum-phonemessage"];
				if ($hasweb)
					$questiondata[$qnum]["webtext"] = $postdata["/questions"]["question$srcnum-webtext"];
				$srcnum++;
				$qnum++;
			}
		} else if ($questionnaire->id) {
			//otherwise see if we're editing something
			foreach ($questions as $index => $question) {
				$qnum = $index + 1;
				$questiondata[$qnum]["reportlabel"] = $question->reportlabel;
				$questiondata[$qnum]["validresponse"] = $question->validresponse;
				if ($hasphone)
					$questiondata[$qnum]["phonemessage"] = $question->phonemessageid ? '{"m": ' . $question->phonemessageid . '}' : "";
				if ($hasweb)
					$questiondata[$qnum]["webtext"] = $question->webmessage;
			}
		}

		//add new question section
		if (@$postdata['/questions']['newquestionaction'] == "add" || count($questiondata) == 0) {
			$qnum = count($questiondata) + 1;
			$questiondata[$qnum]["reportlabel"] = "";
			$questiondata[$qnum]["validresponse"] = $qnum > 1 ? $questiondata[$qnum-1]["validresponse"] : 5;
			if ($hasphone)
				$questiondata[$qnum]["phonemessage"] = "";
			if ($hasweb)
				$questiondata[$qnum]["webtext"] = "";
		}



		//do formdata for existing questions
		foreach ($questiondata as $qnum => $question) {
			$formdata[] = "Question $qnum";

			$formdata["question$qnum-reportlabel"] = array(
				"label" => _L('Report Label'),
				"fieldhelp" => _L('Enter an informative label for the question which will identify it in the report.'),
				"value" => $questiondata[$qnum]["reportlabel"],
				"transient" => true,
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 50),
				"helpstep" => $helpstepnum++
			);
			$helpsteps[] = _L('Enter a descriptive label for the question which will allow you to identify it in the survey report.');


			$formdata["question$qnum-validresponse"] = array(
				"label" => _L('Valid Response'),
				"fieldhelp" => _L('Select the number of possible responses. These values coordinate to the buttons your survey recipients will press.'),
				"value" => $questiondata[$qnum]["validresponse"],
				"transient" => true,
				"validators" => array(
					array("ValRequired"),
					array("ValNumber","min" => 2, "max" => 9)
				),
				"control" => array("SelectMenu","values" => $responseoptions),
				"helpstep" => $helpstepnum++
			);
			$helpsteps[] = _L('Choose the range of numbers survey recipients can press in response to the question. For example, if this is a yes/no question, select 1-2.');

			if ($hasphone) {
				$formdata["question$qnum-phonemessage"] = array(
					"label" => _L('Phone Question'),
					"fieldhelp" => _L('Enter the phone number where the system can call you to record your question.'),
					"value" => $questiondata[$qnum]["phonemessage"],
					"transient" => true,
					"validators" => array(
						array("ValRequired"),
						array("PhoneMessageRecorderValidator")
					),
					"control" => array("PhoneMessageRecorder"),
					"helpstep" => $helpstepnum++
				);
				$helpsteps[] = _L('Enter the number where the system can call you to record your question. It\'s a good idea to write down your questions before you begin. Also, make sure to explain the possible reponses to the recipient. For example, "Press 1 for yes or 2 for no."');

			}

			if ($hasweb) {
				$formdata["question$qnum-webtext"] = array(
				"label" => _L('Web Question Text'),
				"fieldhelp" => _L('Enter the question as you would like it to appear on the web version of the survey.'),
				"value" => $questiondata[$qnum]["webtext"],
				"transient" => true,
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 32000)
				),
				"control" => array("TextArea","rows"=>5,"cols"=>45),
				"helpstep" => $helpstepnum++
			);
			$helpsteps[] = _L('Enter the question as you would like it to appear on the web version of the survey. Make sure to explain what values the answers correspond to. If you\'ve selected the HTML option,  you can use HTML to format your question.');
			}


			$formdata["question$qnum-delete"] = array(
				"label" => _L('Tools'),
				"fieldhelp" => _L('To remove this question from your survey, click this button.'),
				"value" => "",
				"transient" => true,
				"validators" => array(),
				"control" => array("RemoveQuestionButton","name" => _L('Remove Question %1$s', $qnum),"qnum" => $qnum),
				"helpstep" => $helpstepnum++
			);
			$helpsteps[] = _L('You can remove this question from your survey by clicking the Remove Question button.');

		}

		$formdata["newquestionaction"] = array(
			"label" => _L('Add Question'),
			"fieldhelp" => _L('If you\'d like to add an additional question to your survey, click this button.'),
			"value" => "",
			"transient" => true,
			"validators" => array(),
			"control" => array("AddQuestionButton"),
			"helpstep" => $helpstepnum++
		);
		$helpsteps[] = _L('The Add Another Question button lets you add additional questions to your survey.');

		return new Form("phonesurvey", $formdata, $helpsteps);
	}
}

function getMessageIdForPhoneRecorder($value) {
	global $USER;

	$values = json_decode($value);

	if (isset($values->m))
		return $values->m;

	if (isset($values->af)) {
		//make a message for this audiofile and return the messageid
		$m = new Message();
		$m->userid = $USER->id;
		$m->name = "Survey Question";
		$m->description = "";
		$m->type = "phone";
		$m->subtype = "voice";
		$m->autotranslate = "none";
		$m->modifydate = date("Y-m-d H:i:s");
		$m->languagecode = Language::getDefaultLanguageCode();
		$m->deleted = 0;
		$m->create();

		$mp = new MessagePart();
		$mp->messageid = $m->id;
		$mp->type = "A";
		$mp->audiofileid = $values->af;
		$mp->sequence = 0;
		$mp->create();

		return $m->id;
	}

	return null;
}

/**************************** finisher ****************************/
class FinishSurveyTemplateWizard extends WizFinish {

	function finish ($postdata) {
		global $USER, $questionnaire, $questions, $emailmessage;

		Query("BEGIN");

		$surveytype = @$postdata['/settings']['surveytype'] ;
		$hasphone = $USER->authorize('sendphone') && $surveytype != "web"; //true if we can send phone, and type is not email only
		$hasweb = $USER->authorize('sendemail') && $surveytype != "phone"; //true if we can send emails, and type is not phone only

		//settings
		$questionnaire->userid = $USER->id;
		$questionnaire->name = $postdata['/settings']['name'];
		$questionnaire->description = $postdata['/settings']['description'];
		$questionnaire->hasphone = $hasphone ? 1 : 0;
		$questionnaire->hasweb = $hasweb ? 1 : 0;
		$questionnaire->dorandomizeorder = $postdata['/settings']['randomizeorder'] ? 1 : 0;


		//phone features/messages
		if ($hasphone && $postdata['/phonefeatures']['amsweringmachine'] == "message") {
			$questionnaire->machinemessageid = getMessageIdForPhoneRecorder($postdata['/phonemessages']['amsweringmachine']);
		} else {
			$questionnaire->machinemessageid = null;
		}

		if ($hasphone && $postdata['/phonefeatures']['intromessage'] == "message") {
			$questionnaire->intromessageid = getMessageIdForPhoneRecorder($postdata['/phonemessages']['intromessage']);
		} else {
			$questionnaire->intromessageid = null;
		}

		if ($hasphone && ($postdata['/phonefeatures']['goodbyemessage'] == "message" ||
						$postdata['/phonefeatures']['goodbyemessage'] == "reply")) {
			$questionnaire->exitmessageid = getMessageIdForPhoneRecorder($postdata['/phonemessages']['goodbyemessage']);
			$questionnaire->leavemessage = $postdata['/phonefeatures']['goodbyemessage'] == "reply" ? 1 : 0;
		} else {
			$questionnaire->exitmessageid = null;
			$questionnaire->leavemessage = 0;
		}

		//web features
		if ($hasweb ) {
			$questionnaire->webpagetitle = $postdata['/webfeatures']['webpagetitle'];
			$questionnaire->webexitmessage = $postdata['/webfeatures']['webexitmessage'];
			$questionnaire->usehtml = $postdata['/webfeatures']['usehtml'] == "true" ? 1 : 0;

			//emailmessage
			if (!$emailmessage) {
				$emailmessage = new Message();
				$emailmessage->userid = $USER->id;
				$emailmessage->name = "Survey Email";
				$emailmessage->description = "";
				$emailmessage->type = "email";
				$emailmessage->subtype = "plain";
				$emailmessage->autotranslate = "none";
				$emailmessage->languagecode = Language::getDefaultLanguageCode();
				$emailmessage->deleted = 0;
			}

			$emailmessage->subject = $postdata['/webfeatures']['subject'];
			$emailmessage->fromemail = $postdata['/webfeatures']['from'];
			$emailmessage->fromname = $postdata['/webfeatures']['fromname'];
			$emailmessage->stuffHeaders();
			$emailmessage->modifydate = date("Y-m-d H:i:s");
			$emailmessage->update();

			QuickUpdate("delete from messagepart where messageid=?", false, array($emailmessage->id));

			$mp = new MessagePart();
			$mp->messageid = $emailmessage->id;
			$mp->type = "T";
			$mp->txt = $postdata['/webfeatures']['message'];
			$mp->sequence = 0;
			$mp->create();

			$questionnaire->emailmessageid = $emailmessage->id;

		} else {
			$questionnaire->webpagetitle = null;
			$questionnaire->webexitmessage = null;
			$questionnaire->usehtml = 0;
		}

		$questionnaire->update();

		//do questions
		$qnum = 1;
		$srcnum = 1;
		while (isset($postdata["/questions"]["question$srcnum-reportlabel"])) {
			//check for a delete marker
			if (@$postdata["/questions"]["question$srcnum-delete"]) {
				$srcnum++;
				continue;
			}

			if (isset($questions[$qnum-1]))
				$question = $questions[$qnum-1];
			else
				$question = new SurveyQuestion();

			$question->questionnumber = $qnum-1;
			$question->questionnaireid = $questionnaire->id;
			$question->reportlabel = $postdata["/questions"]["question$srcnum-reportlabel"];
			$question->validresponse = $postdata["/questions"]["question$srcnum-validresponse"];
			$question->phonemessageid = $hasphone ? getMessageIdForPhoneRecorder($postdata["/questions"]["question$srcnum-phonemessage"]) : null;
			$question->webmessage = $hasweb ? $postdata["/questions"]["question$srcnum-webtext"] : null;

			$question->update();

			$srcnum++;
			$qnum++;
		}

		//remove any questions greater or equal the next questionnumber, in case user deleted some from existing survey
		QuickUpdate("delete from surveyquestion where questionnaireid=? and questionnumber >= ?", false, array($questionnaire->id, $qnum-1));

		Query("COMMIT");
	}

	function getFinishPage ($postdata) {
		global $USER, $questionnaire, $questions, $emailmessage;

		if ($questionnaire->id)
			return "<h1>Survey Template Modified</h1>";
		else
			return "<h1>Survey Template Created</h1>";
	}
}

/**************************** wizard setup ****************************/
$wizdata = array(
	"settings" => new SurveyTempleteWiz_settings(_L("Start")),
	"phonefeatures" => new SurveyTemplateWiz_phonefeatures(_L("Phone Features")),
	"phonemessages" => new SurveyTemplateWiz_phonemessages(_L("Phone Messages")),
	"webfeatures" => new SurveyTemplateWiz_webfeatures(_L("Web Features")),
	"questions" => new SurveyTemplateWiz_questions(_L("Questions"))
	);

$wizard = new Wizard("surveytemplatewiz", $wizdata, new FinishSurveyTemplateWizard(_L("Finish")));
$wizard->doneurl = "surveys.php";
$wizard->handleRequest();

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:survey";
$TITLE = "Survey Template Editor";

require_once("nav.inc.php");

// Load Custom Form Validators
?>
<script type="text/javascript">
<?
Validator::load_validators(array("PhoneMessageRecorderValidator"));
?>
</script>
<?


startWindow($wizard->getStepData()->title);

echo $wizard->render();

endWindow();

if (false) {
	startWindow("Wizard Data");
	echo "<pre>";
	var_dump($_SESSION['surveytemplatewiz']);
	//var_dump($_SERVER);
	echo "</pre>";
	endWindow();
}

require_once("navbottom.inc.php");
?>
