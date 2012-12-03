<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("inc/translate.inc.php");
require_once("inc/facebook.php");
require_once("inc/facebookEnhanced.inc.php");
require_once("inc/facebook.inc.php");
require_once("obj/TwitterAuth.fi.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/ListForm.obj.php");
require_once("obj/Twitter.obj.php");
require_once("inc/help.inc.php");

// DBMO
require_once("obj/Content.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/FeedCategory.obj.php");

// Validators
require_once("obj/Validator.obj.php");
require_once("obj/ValSmsText.val.php");
require_once("obj/ValTtsText.val.php");
require_once("obj/TextAreaAndSubjectWithCheckbox.val.php");
require_once("obj/ValFacebookPage.val.php");
require_once("obj/ValLists.val.php");
require_once("obj/ValTimeWindowCallEarly.val.php");
require_once("obj/ValTimeWindowCallLate.val.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/ValMessageGroup.val.php");
require_once("obj/EmailAttach.val.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/TraslationItem.fi.php");
require_once("obj/CallerID.fi.php");
require_once("obj/ValDuplicateNameCheck.val.php");

// Preview
require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");
require_once("inc/appserver.inc.php");
PreviewModal::HandleRequestWithPhoneText();
PreviewModal::HandleRequestWithEmailText();

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

// if initialization requires new uuid...
if (!isset($_SESSION['_messagesender']['uuid'])) {
	$_SESSION['_messagesender'] = array();
		$_SESSION['_messagesender']['uuid'] = "ms".uniqid();
}
if (isset($_GET['template']) && $_GET['template'] && isset($_GET['subject']) && $_GET['subject']) {
	$_SESSION['message_sender'] = array();
	$_SESSION['message_sender']['template'] = array(
		"subject" => $_GET['subject'],
		"lists" => (isset($_GET['lists'])?$_GET['lists']:"[]"),
		"jobtypeid" => (isset($_GET['jobtypeid'])?$_GET['jobtypeid']:0),
		"messagegroupid" => (isset($_GET['messagegroupid'])?$_GET['messagegroupid']:0));
	redirect();
} else if (isset($_GET['new'])) {
	unset($_SESSION['message_sender']);
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Form
////////////////////////////////////////////////////////////////////////////////
class MessageSenderForm extends Form {
	// Override
	function preValidation() {
		global $USER;

		// people to contact
		if (isset($this->formdata["addme"]["value"]) && $this->formdata["addme"]["value"]) {
			// TODO: addme needs to require some data
		} else {
			// no addme, must have a list then
			$this->markRequired("listids");
		}

		// TODO: Must have one of phone, email or sms to be valid!

		// has phone message data?
		if (isset($this->formdata["hasphone"]["value"]) && $this->formdata["hasphone"]["value"]) {
			switch ($this->formdata["phonemessagetype"]["value"]) {
				case "callme":
					$this->markRequired("phonemessagecallme");
					break;
				default:
					$this->markRequired("phonemessagetext");
			}
			// callerid required if using a phone message
			if (!getSystemSetting('_hascallback', false) && (getSystemSetting("requireapprovedcallerid",false) || $USER->authorize('setcallerid'))) {
				$this->markRequired("optioncallerid");
			}
			$this->markRequired("optionmaxjobdays");
		}

		// has email message data?
		if (isset($this->formdata["hasemail"]["value"]) && $this->formdata["hasemail"]["value"]) {
			$this->markRequired("emailmessagefromname");
			$this->markRequired("emailmessagefromemail");
			$this->markRequired("emailmessagesubject");
			$this->markRequired("emailmessagetext");
		}

		// has sms message data?
		if (isset($this->formdata["hassms"]["value"]) && $this->formdata["hassms"]["value"]) {
			$this->markRequired("smsmessagetext");
		}

		// has facebook message data?
		if (isset($this->formdata["hasfacebook"]["value"]) && $this->formdata["hasfacebook"]["value"]) {
			$this->markRequired("socialmediafacebookmessage");
			// FIXME: message_sender javascript doesn't require facebook page to be set
			//$this->markRequired("socialmediafacebookpage");
		}

		// has twitter message data?
		if (isset($this->formdata["hastwitter"]["value"]) && $this->formdata["hastwitter"]["value"]) {
			$this->markRequired("socialmediatwittermessage");
		}

		// has feed message data?
		if (isset($this->formdata["hasfeed"]["value"]) && $this->formdata["hasfeed"]["value"]) {
			$this->markRequired("socialmediafeedmessage");
			// FIXME: message_sender javascript doesn't require feed catetory to be set
			//$this->markRequired("socialmediafeedcategory");
		}

		// save this message?
		if (isset($this->formdata["optionsavemessage"]["value"]) && $this->formdata["optionsavemessage"]["value"]) {
			$this->markRequired("optionsavemessagename");
		}
		
		// if this job will be sent "now", remove the schedule date/time validators
		if ($this->formdata["scheduletype"]["value"] == "now") {
			$this->formdata["scheduledate"]['validators'] = array();
			$this->formdata["schedulecallearly"]['validators'] = array();
			$this->formdata["schedulecalllate"]['validators'] = array();
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////

class ValEasycall extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize("starteasy"))
			return "$this->label "._L("is not allowed for this user account");
		$values = json_decode($value);
		if (!$values || $values == json_decode("{}"))
			return "$this->label "._L("has messages that are not recorded");
		foreach ($values as $langcode => $afid) {
			$audiofile = DBFind("AudioFile", "from audiofile a where a.id = ? and (a.userid = ?
						or exists (select 1 from publish p where p.userid = ? and p.action = 'subscribe' and p.type = 'messagegroup' and p.messagegroupid = a.messagegroupid))
						", "a", array($afid, $USER->id,$USER->id));
			if (!$audiofile)
				return "$this->label "._L("has invalid or missing messages");
		}
		return true;
	}
}

class ValHasMessage extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $USER;
		if ($value == 'pick') {
			// find if there are any message groups the user owns or subscribes to
			$hasowned = QuickQuery("
					select 1
					from messagegroup mg
					where not mg.deleted and mg.userid = ?
					limit 1", false, array($USER->id));
			$hassubscribed = QuickQuery("
					select 1
					from publish p
					where p.userid = ? and action = 'subscribe' and type = 'messagegroup'
					limit 1", false, array($USER->id));
			if (!$hasowned && !$hassubscribed)
				return "$this->label: ". _L('You have no saved or subscribed messages.');
		}
		return true;
	}
}

class ValMessageTypeSelect extends Validator {

	function validate ($value, $args) {
		// MUST contain one of phone, email or sms
		if (!array_intersect(array('phone','email','sms'), $value))
			return "$this->label ". _L('requires one message of type Phone, Email or SMS Text.');
		return true;
	}

	function getJSValidator () {
		return '
		function (name, label, value, args) {
		var isvalid = false;
		$A(value).each(function (val) {
		if (val == "phone" || val == "email" || val == "sms")
		isvalid = true;
	});
	if (!isvalid)
	return label + " '. _L("requires one message of type Phone, Email or SMS Text.") .'";
	return true;
	}
	';
	}
}

class ValTranslationCharacterLimit extends Validator {
	function validate ($value, $args, $requiredvalues) {
		$textlength = strlen($requiredvalues[$args['field']]);
		if ($textlength > 5000)
			return "$this->label is unavalable if the message is more than 5000 characters. The message is currently $textlength characters.";
		return true;
	}
	function getJSValidator () {
		return
		'function (name, label, value, args, requiredvalues) {
		//alert("valLength");
		var textlength = requiredvalues[args["field"]].length;
		if (textlength > 5000)
		return this.label +" is unavalable if the message is more than 5000 characters. The message is currently " + textlength + " characters.";
		return true;
	}';
	}
}

class ValTimePassed extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		$timediff = (time() - strtotime($requiredvalues[$args['field']] . " " . $value));
		if ($timediff > 0)
			return "$this->label: ". _L('Must be in the future.');
		return true;
	}
}

class ValConditionalOnValue extends Validator {
	var $conditionalrequired = true;
	function validate ($value, $args, $requiredvalues) {
		$required = true;
		foreach ($args['fields'] as $field => $testvalue) {
			if ($requiredvalues[$field] != $testvalue)
				$required = false;
		}
		if ($required && !$value)
			return "$this->label is required.";
		
		return true;
	}
	
	function getJSValidator () {
		return '
			function (name, label, value, args, requiredvalues) {
				var required = true;
				$H(args.fields).each(function (field, testvalue) {
					if (requiredvalues[field] != testvalue)
						required = false;
				});
				if (required && !value)
					return this.label + " is required.";
				
				return true;
			}';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

PreviewModal::HandleRequestWithPhoneText();
PreviewModal::HandleRequestWithEmailText();

// get the user requested schedule out of postdata
function getSchedule($postdata) {
	global $ACCESS;
	global $USER;
	
	$type = $postdata["scheduletype"];
	$maxjobdays = $postdata["optionmaxjobdays"];
	$callearly = $postdata['schedulecallearly'];
	$calllate = $postdata['schedulecalllate'];
	$date = $postdata['scheduledate'];
	
	$schedule = array();
	
	if (!$maxjobdays)
		$maxjobdays = 1;
	
	switch ($type) {
		case "now":
			//get the callearly and calllate defaults
			$callearly = date("g:i a");
			$calllate = $USER->getCallLate();
				
			//get access profile settings
			$accessCallearly = $ACCESS->getValue("callearly");
			if (!$accessCallearly)
				$accessCallearly = "12:00 am";
			$accessCalllate = $ACCESS->getValue("calllate");
			if (!$accessCalllate)
				$accessCalllate = "11:59 pm";
				
			//convert everything to timestamps for comparisons
			$callearlysec = strtotime($callearly);
			$calllatesec = strtotime($calllate);
			$accessCallearlysec = strtotime($accessCallearly);
			$accessCalllatesec = strtotime($accessCalllate);

			// make sure the calculated callearly is not before access profile
			if ($callearlysec  < $accessCallearlysec)
				$callearlysec = $accessCallearlysec;

			// try to ensure calllate is at least an hour after start, up to access restriction
			if ($callearlysec + 3600 > $calllatesec)
				$calllatesec = $callearlysec + 3600;
				
			//make sure the calculated calllate is not past access profile
			if ($calllatesec  > $accessCalllatesec)
				$calllatesec = $accessCalllatesec;

			$callearly = date("g:i a", $callearlysec);
			$calllate = date("g:i a", $calllatesec);
				
			$schedule = array(
					"maxjobdays" => $maxjobdays,
					"date" => date('m/d/Y'),
					"callearly" => $callearly,
					"calllate" => $calllate
			);
			break;
		case "schedule":
			$schedule = array(
			"maxjobdays" => $maxjobdays,
			"date" => date('m/d/Y', strtotime($date)),
			"callearly" => $callearly,
			"calllate" => $calllate
			);
			break;
		default:
			break;
	}
	return $schedule;
}

$userjobtypes = JobType::getUserJobTypes(false);
$jobtypes = array();
foreach ($userjobtypes as $id => $jobtype) {
	$jobtypes[$id] = $jobtype->name;
}

// need to reserve some characters for the link url and the six byte code. (http://smalldomain.com/<code>)
$twitterreservedchars = mb_strlen(" http://". getSystemSetting("tinydomain"). "/") + 6;

//Get available tts languages
$ttslanguages = Voice::getTTSLanguageMap();
unset($ttslanguages['en']);

// get available translation languages
global $TRANSLATIONLANGUAGECODES;
$alllanguages = QuickQueryList("select code, name from language", true);
$translationlanguages = array_intersect_key($alllanguages, array_flip($TRANSLATIONLANGUAGECODES));
unset($translationlanguages['en']);

$fcids = array_keys(FeedCategory::getAllowedFeedCategories());
$feedcategoryids = array();
foreach ($fcids as $fcid)
	$feedcategoryids[$fcid] = $fcid;

$formdata = array(
		"uuid" => array(
				"label" => "uuid",
				"value" => $_SESSION['_messagesender']['uuid'],
				"control" => array("HiddenField"),
				"helpstep" => 1
		),
		"name" => array(
				"label" => "Subject",
				"value" => "",
				"validators" => array(
						array("ValRequired"),
						array("ValDuplicateNameCheck", "type" => "job"),
						array("ValLength","max" => 50)
				),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"jobtype" => array(
				"label" => "Type",
				"value" => "",
				"validators" => array(
						array("ValRequired"),
						array("ValInArray", "values" => array_keys($jobtypes))
				),
				"control" => array("SelectMenu", "values" => $jobtypes),
				"helpstep" => 1
		),
		//=========================================================================================
		"LIST DATA",
		//=========================================================================================
		"addme" => array(
				"label" => "Add Myself",
				"value" => "",
				"validators" => array(// None, just toggles logic for addme fields
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"addmephone" => array(
				"label" => "Phone",
				"value" => "",
				"validators" => array(
						array("ValPhone")
				),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"addmeemail" => array(
				"label" => "Email",
				"value" => "",
				"validators" => array(
						array("ValEmail")
				),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"addmesms" => array(
				"label" => "SMS",
				"value" => "",
				"validators" => array(
						array("ValPhone")
				),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"listids" => array(
				"label" => "List(s)",
				"value" => "",
				"validators" => array(
						array("ValLists", "allowempty" => true)
				),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		//=========================================================================================
		"PHONE MESSAGE",
		//=========================================================================================
		"hasphone" => array(
				"label" => "Phone",
				"value" => "",
				"validators" => array(
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"phonemessagetype" => array(
				"label" => "Phone Type",
				"value" => "",
				"validators" => array(
						array("ValInArray", "values" => array("callme","text"))
				),
				"control" => array("RadioButton", "values" => array("callme" => "callme", "text" => "text")),
				"helpstep" => 1
		),
		"phonemessagepost" => array(
				"label" => "Audio Link",
				"value" => "",
				"validators" => array(// NOTE: Will need complicated validation based on user permissions and message contents (has dynamic parts?)
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"phonemessagecallme" => array(
				"label" => "Call Me",
				"value" => "",
				"validators" => array(
						array("ValConditionalOnValue", "fields" => array("hasphone" => "on", "phonemessagetype" => "callme")),
						array("ValEasycall")
				),
				"requires" => array("hasphone","phonemessagetype"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"phonemessagetext" => array(
				"label" => "Message",
				"value" => "",
				"validators" => array(
						array("ValConditionalOnValue", "fields" => array("hasphone" => "on", "phonemessagetype" => "text")),
						array("ValMessageBody"),
						array("ValTtsText"),
						array("ValLength","max" => 10000) // 10000 Characters is about 40 minutes of tts, considered to be more than enough
				),
				"requires" => array("hasphone","phonemessagetype"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"phonemessagetexttranslate" => array(
				"label" => "Translate",
				"value" => "",
				"validators" => array(
						array("ValTranslationCharacterLimit", "field" => "phonemessagetext")
				),
				"control" => array("CheckBox"),
				"requires" => array("phonemessagetext"),
				"helpstep" => 1
		)
);

foreach ($ttslanguages as $code => $language) {
	$formdata["phonemessagetexttranslate". $code. "text"] = array(
			"label" => "phonemessagetexttranslate". $code. "text",
			"value" => "",
			"validators" => array(
					array("ValTranslation")
			),
			"control" => array("TextField"),
			"helpstep" => 1
	);
}

$formdata = array_merge($formdata, array(
		//=========================================================================================
		"EMAIL MESSAGE",
		//=========================================================================================
		"hasemail" => array(
				"label" => "Email",
				"value" => "",
				"validators" => array(
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"emailmessagefromname" => array(
				"label" => "Name",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hasemail"),
						array("ValLength","max" => 50)
				),
				"requires" => array("hasemail"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"emailmessagefromemail" => array(
				"label" => "Email",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hasemail"),
						array("ValLength","max" => 255),
						array("ValEmail", "domain" => getSystemSetting('emaildomain'))
				),
				"requires" => array("hasemail"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"emailmessagesubject" => array(
				"label" => "Subject",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hasemail"),
						array("ValLength","max" => 255)
				),
				"requires" => array("hasemail"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"emailmessageattachment" => array(
				"label" => "Attachment(s)",
				"value" => "",
				"validators" => array(
						array("ValEmailAttach")
				),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"emailmessagetext" => array(
				"label" => "Body",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hasemail"),
						array("ValMessageBody"),
						array("ValLength","max" => 256000)
				),
				"requires" => array("hasemail"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"emailmessagetexttranslate" => array(
				"label" => "Translate",
				"value" => "",
				"validators" => array(
						array("ValTranslationCharacterLimit", "field" => "emailmessagetext")
				),
				"control" => array("CheckBox"),
				"requires" => array("emailmessagetext"),
				"helpstep" => 1
		)
));

foreach ($translationlanguages as $code => $language) {
	$formdata["emailmessagetexttranslate". $code. "text"] = array(
			"label" => "emailmessagetexttranslate". $code. "text",
			"value" => "",
			"validators" => array(
					array("ValTranslation") // NOTE: I "think" this will work for email. May need to write a new validator...
			),
			"control" => array("TextField"),
			"helpstep" => 1
	);
}

$formdata = array_merge($formdata, array(
		//=========================================================================================
		"SMS MESSAGE",
		//=========================================================================================
		"hassms" => array(
				"label" => "SMS",
				"value" => "",
				"validators" => array(
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"smsmessagetext" => array(
				"label" => "Message",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hassms"),
						array("ValLength","max"=>160),
						array("ValSmsText")
				),
				"requires" => array("hassms"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		//=========================================================================================
		"SOCIAL MESSAGE",
		//=========================================================================================
		"hasfacebook" => array(
				"label" => "Facebook",
				"value" => "",
				"validators" => array(
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"socialmediafacebookmessage" => array(
				"label" => "Message",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hasfacebook"),
						array("ValLength","max"=>420)
				),
				"requires" => array("hasfacebook"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"hastwitter" => array(
				"label" => "Twitter",
				"value" => "",
				"validators" => array(
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"socialmediatwittermessage" => array(
				"label" => "Message",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hastwitter"),
						array("ValLength","max"=>(140 - $twitterreservedchars))
				),
				"requires" => array("hastwitter"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"hasfeed" => array(
				"label" => "Feed",
				"value" => "",
				"validators" => array(
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"socialmediafeedmessage" => array(
				"label" => "Message",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hasfeed"),
						array("ValTextAreaAndSubjectWithCheckbox","requiresubject" => true),
						array("ValLength","max"=>32000)
				),
				"requires" => array("hasfeed"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"socialmediafacebookpage" => array(
				"label" => "Page(s)",
				"value" => "",
				"validators" => array(
						//array("ValConditionallyRequired", "field" => "hasfacebook"),
						array("ValFacebookPage", "authpages" => getFbAuthorizedPages(), "authwall" => getSystemSetting("fbauthorizewall"))
				),
				//"requires" => array("hasfacebook"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"socialmediafeedcategory" => array( // TODO: this is currently a multi-checkbox
				"label" => "Category",
				"value" => "",
				"validators" => array(
						//array("ValConditionallyRequired", "field" => "hasfeed"),
						array("ValInArray", "values" => array_keys($feedcategoryids))
				),
				//"requires" => array("hasfeed"),
				"control" => array("MultiCheckBox", "values" => $feedcategoryids),
				"helpstep" => 1
		),
		//=========================================================================================
		"JOB OPTIONS",
		//=========================================================================================
		"optionmaxjobdays" => array(
				"label" => "Day(s)",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hasphone"),
						array("ValInArray", "values" => range(1,$ACCESS->getValue('maxjobdays', 7)))
				),
				"requires" => array("hasphone"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"optionautoreport" => array(
				"label" => "Auto Report",
				"value" => "",
				"validators" => array(
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"optionleavemessage" => array(
				"label" => "Response",
				"value" => "",
				"validators" => array(// NOTE: no validation, will be ignored if the user can't use this option
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"optionmessageconfirmation" => array(
				"label" => "Confirmation",
				"value" => "",
				"validators" => array(// NOTE: no validation, will be ignored if the user can't use this option
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"optionskipduplicate" => array( // NOTE: using same setting for both skipduplicates and skipemailduplicates?
				"label" => "Skip Duplicate",
				"value" => "",
				"validators" => array(// NOTE: no validation, will be ignored if the user can't use this option
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"optioncallerid" => array(
				"label" => "Caller ID",
				"value" => "",
				"validators" => array(
						array("ValConditionallyRequired", "field" => "hasphone"),
						array("ValLength","min" => 0,"max" => 20),
						array("ValPhone"),
						array("ValCallerID")
				),
				"requires" => array("hasphone"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"optionsavemessage" => array(
				"label" => "Save Message",
				"value" => "",
				"validators" => array(// NOTE: no validation. just toggles save message mode
				),
				"control" => array("CheckBox"),
				"helpstep" => 1
		),
		"optionsavemessagename" => array(
				"label" => "Name",
				"value" => "",
				"validators" => array(
						array("ValDuplicateNameCheck", "type" => "messagegroup"),
						array("ValLength","max" => 30)
				),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		//=========================================================================================
		"SCHEDULE OPTIONS",
		//=========================================================================================
		"scheduletype" => array(
				"label" => "Schedule type",
				"value" => "",
				"validators" => array(
						array("ValInArray", "values" => array("now","schedule"))
				),
				"control" => array("RadioButton", "values" => array("now" => "now", "schedule" => "schedule")),
				"helpstep" => 1
		),
		"scheduledate" => array(
				"label" => "Date",
				"value" => "",
				"validators" => array(
						array("ValConditionalOnValue", "fields" => array("scheduletype" => "schedule")),
						array("ValDate", "min" => date("m/d/Y"))
				),
				"requires" => array("scheduletype"),
				"control" => array("TextField"),
				"helpstep" => 1
		),
		"schedulecallearly" => array(
				"label" => "Start",
				"value" => "",
				"validators" => array(
						array("ValConditionalOnValue", "fields" => array("scheduletype" => "schedule")),
						array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
						array("ValTimeWindowCallEarly", "calllatefield" => "schedulecalllate")
				),
				"control" => array("TextField"),
				"requires" => array("scheduletype", "schedulecalllate", "scheduledate"),
				"helpstep" => 1
		),
		"schedulecalllate" => array(
				"label" => "End",
				"value" => "",
				"validators" => array(
						array("ValConditionalOnValue", "fields" => array("scheduletype" => "schedule")),
						array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
						array("ValTimeWindowCallLate", "callearlyfield" => "schedulecallearly"),
						array("ValTimePassed", "field" => "scheduledate")
				),
				"control" => array("TextField"),
				"requires" => array("scheduletype", "schedulecallearly", "scheduledate"),
				"helpstep" => 1
		)
));

$displayingCallerid = !getSystemSetting('_hascallback', false) && (getSystemSetting("requireapprovedcallerid",false) || $USER->authorize('setcallerid'));
if(!$displayingCallerid) {
	$formdata["optioncallerid"]["validators"] = array();
}

$buttons = array(submit_button(_L('Save'),"submit","tick"),
		icon_button(_L('Cancel'),"cross",null,"start.php"));
$form = new MessageSenderForm("msgsndr",$formdata,array(),$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}

		// invalidate the current messagesender so this form can't be re-submitted multiple times
		unset($_SESSION['_messagesender']);

		Query("BEGIN");

		// ============================================================================================================================
		// Job, create a new one
		// ============================================================================================================================
		$job = Job::jobWithDefaults();

		$job->userid = $USER->id;
		$job->jobtypeid = $postdata["jobtype"];
		$job->name = removeIllegalXmlChars($postdata["name"]);
		$job->description = "Created with MessageSender";

		$job->type = 'notification';
		$job->modifydate = $job->createdate = date("Y-m-d H:i:s");

		$schedule = getSchedule($postdata);
		
		$job->scheduleid = null;
		if ($schedule['date'])
			$job->startdate = date("Y-m-d", strtotime($schedule['date']));
		else
			$job->startdate = date("Y-m-d");
		$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($schedule["maxjobdays"] - 1) * 86400));
		$job->starttime = ($schedule['callearly'])?date("H:i", strtotime($schedule['callearly'])):date("H:i", strtotime($USER->getCallEarly()));
		$job->endtime = ($schedule['calllate'])?date("H:i", strtotime($schedule['calllate'])):date("H:i", strtotime($USER->getCallLate()));
		$job->finishdate = null;
		$job->status = "new";
		$job->create();

		$job->setSetting('translationexpire', date("Y-m-d", strtotime("+15 days"))); // now plus 15 days

		// for all the job settings on the "Advanced" step. set some advanced options that will get stuffed into the job
		$job->setSetting("leavemessage", (isset($postdata["optionleavemessage"]) && $postdata["optionleavemessage"])?1:0);
		$job->setSetting("sendreport", (isset($postdata["optionautoreport"]) && $postdata["optionautoreport"])?1:0);
		$job->setSetting("messageconfirmation", (isset($postdata["optionmessageconfirmation"]) && $postdata["optionmessageconfirmation"])?1:0);
		$job->setSetting("skipduplicates", (isset($postdata["optionskipduplicate"]) && $postdata["optionskipduplicate"])?1:0);
		$job->setSetting("skipemailduplicates", (isset($postdata["optionskipduplicate"]) && $postdata["optionskipduplicate"])?1:0);

		// set jobsetting 'callerid'
		if ($displayingCallerid && $postdata["optioncallerid"])
			$job->setSetting('callerid', $postdata["optioncallerid"]);

		$job->update();

		// ============================================================================================================================
		// Lists, get listids and create an addme list
		// ============================================================================================================================
		$joblists = json_decode($postdata["listids"]);

		// if there is "addme" data in the list selection, create a person and list with the contact details
		if (isset($postdata['addme']) && $postdata['addme']) {
			$addmelist = new PeopleList(null);
			$addmelist->userid = $USER->id;
			$addmelist->name = _L("Me");
			$addmelist->description = _L("JobWizard, addme");
			$addmelist->deleted = 1;
			$addmelist->create();
				
			// List was created, so add the "addme" person to it
			if ($addmelist->id) {
				// Constants
				$langfield = FieldMap::getLanguageField();
				$fnamefield = FieldMap::getFirstNameField();
				$lnamefield = FieldMap::getLastNameField();

				// New Person
				$person = new Person();
				$person->userid = $USER->id;
				$person->deleted = 0; // NOTE: This person must not be set as deleted, otherwise the list will not include him/her.
				$person->type = "manualadd";
				$person->$fnamefield = $USER->firstname;
				$person->$lnamefield = $USER->lastname;
				$person->$langfield = "en";
				$person->create();

				// get the contact details out of postdata
				$deliveryTypes = array();
				if (isset($postdata['addmephone']) && $postdata['addmephone']) {
					$deliveryTypes["phone"] = new Phone();
					$deliveryTypes["phone"]->phone = Phone::parse($postdata['addmephone']);
				}
				if (isset($postdata['addmeemail']) && $postdata['addmeemail']) {
					$deliveryTypes["email"] = new Email();
					$deliveryTypes["email"]->email = $postdata['addmeemail'];
				}
				if (isset($postdata['addmesms']) && $postdata['addmesms']) {
					$deliveryTypes["sms"] = new Sms();
					$deliveryTypes["sms"]->sms = Phone::parse($postdata['addmesms']);
				}

				// Delivery Types and Job Types
				foreach ($deliveryTypes as $deliveryTypeName => $deliveryTypeObject) {
					$deliveryTypeObject->personid = $person->id;
					$deliveryTypeObject->sequence = 0;
					$deliveryTypeObject->editlock = 0;
					$deliveryTypeObject->update();
						
					// NOTE: getUserJobTypes() automatically applies user jobType restrictions
					$jobTypes = JobType::getUserJobTypes(false);
						
					// For each job type, assume sequence = 0, enabled = 1
					foreach ($jobTypes as $jobType) {
						// NOTE: $person->id is assumed to be a new id, so no need to worry about duplicate keys
						$query = "insert into contactpref (personid, jobTypeid, type, sequence, enabled) values (?, ?, ?, 0, 1)";
						QuickUpdate($query, false, array($person->id, $jobType->id, $deliveryTypeName));
					}
				}

				// New List Entry
				$le = new ListEntry();
				$le->type = "add";
				$le->listid = $addmelist->id;
				$le->personid = $person->id;
				$le->create();

				// Include this single-person list in the job.
				$joblists[] = $addmelist->id;
			}
		} //end addme

		// store job lists
		foreach ($joblists as $listid)
			QuickUpdate("insert into joblist (jobid,listid) values (?,?)", false, array($job->id, $listid));

		// create a new message group
		$messagegroup = new MessageGroup();
		$messagegroup->userid = $USER->id;
		if (isset($postdata["optionsavemessage"]) && $postdata["optionsavemessage"]) {
			$messagegroup->name = $postdata["optionsavemessagename"];
			$messagegroup->deleted = 0;
		} else {
			$messagegroup->name = $job->name;
			$messagegroup->deleted = 1;
		}
		$messagegroup->description = "Created in MessageSender";
		$messagegroup->modified = $job->modifydate;
		$messagegroup->create();

		$job->messagegroupid = $messagegroup->id;
		$job->update();

		// keep track of the text message data we are going to create messages for
		// format $messages[<type>][<subtype>][<langcode>][<autotranslate>] => array($msgdata)
		$messages = array(
				'phone' => array(),
				'email' => array(),
				'sms' => array(),
				'post' => array()
		);
		
		// ============================================================================================================================
		// Phone Message (callme, text, translations)
		// ============================================================================================================================
		$jobpostmessage = array();
		if (isset($postdata["hasphone"]) && $postdata["hasphone"] && $USER->authorize("sendphone")) {
			switch ($postdata["phonemessagetype"]) {
				case "callme":
					$audiofileidmap = json_decode($postdata["phonemessagecallme"]);
					foreach ($audiofileidmap as $langcode => $audiofileid) {
						$message = new Message();
						$message->messagegroupid = $messagegroup->id;
						$message->type = 'phone';
						$message->subtype = 'voice';
						$message->autotranslate = 'none';

						$message->name = $messagegroup->name;
						$message->description = Language::getName($langcode);
						$message->userid = $USER->id;
						$message->modifydate = $messagegroup->modified;
						$message->languagecode = $langcode;
						$message->stuffHeaders();
						$message->create();

						$part = new MessagePart();
						$part->messageid = $message->id;
						$part->type = "A";
						$part->audiofileid = $audiofileid;
						$part->sequence = 0;
						$part->create();
					}
					// create a post voice message (if it's enabled)
					if (isset($postdata["phonemessagepost"]) && $postdata["phonemessagepost"]) {
						$message = new Message();
						$message->messagegroupid = $messagegroup->id;
						$message->type = 'post';
						$message->subtype = 'voice';
						$message->autotranslate = 'none';

						$message->name = $messagegroup->name;
						$message->description = Language::getName($langcode);
						$message->userid = $USER->id;
						$message->modifydate = $messagegroup->modified;
						$message->languagecode = "en";
						$message->stuffHeaders();
						$message->create();

						$part = new MessagePart();
						$part->messageid = $message->id;
						$part->type = "A";
						$part->audiofileid = $audiofileidmap->en;
						$part->sequence = 0;
						$part->create();

						$jobpostmessage[] = "voice";
					}
					break;
				case "text":
					$sourcemessage = json_decode($postdata["phonemessagetext"]);

					// this is the default 'en' message so it's autotranslate value is 'none'
					$messages['phone']['voice']['en']['none']['text'] = $sourcemessage->text;
					$messages['phone']['voice']['en']['none']['gender'] = $sourcemessage->gender;

					//also set the messagegroup preferred gender
					$messagegroup->preferredgender = $sourcemessage->gender;
					$messagegroup->stuffHeaders();
					$messagegroup->update(array("data"));

					// create a post voice (provided that's enabled)
					if (isset($postdata["phonemessagepost"]) && $postdata["phonemessagepost"])
						$messages['post']['voice']['en']['none'] = $messages['phone']['voice']['en']['none'];
						
					// check for and retrieve translations
					foreach ($ttslanguages as $code => $language) {
						if (isset($postdata["phonemessagetexttranslate". $code. "text"]))
							$translatedmessage = json_decode($postdata["phonemessagetexttranslate". $code. "text"], true);
						if ($translatedmessage["enabled"]) {
							// if the translation text is overridden, don't attach a source message
							// it isn't applicable since we have no way to know what they changed the text to.
							if ($translatedmessage["override"]) {
								$messages['phone']['voice'][$code]['overridden']['text'] = $translatedmessage["text"];
								$messages['phone']['voice'][$code]['overridden']['gender'] = $translatedmessage["gender"];
							} else {
								$messages['phone']['voice'][$code]['translated']['text'] = $translatedmessage["text"];
								$messages['phone']['voice'][$code]['translated']['gender'] = $translatedmessage["gender"];
								$messages['phone']['voice'][$code]['source'] = $messages['phone']['voice']['en']['none'];
							}
						}
					}
			} // end switch phone message type
		} // end if hasphone

		// ============================================================================================================================
		// Email Message (text, translations)
		// ============================================================================================================================
		if (isset($postdata["hasemail"]) && $postdata["hasemail"] && $USER->authorize("sendemail")) {
			// this is the default 'en' message so it's autotranslate value is 'none'
			$messages['email']['html']['en']['none']['text'] = $postdata["emailmessagetext"];
			$messages['email']['html']['en']['none']["fromname"] = $postdata["emailmessagefromname"];
			$messages['email']['html']['en']['none']["from"] = $postdata["emailmessagefromemail"];
			$messages['email']['html']['en']['none']["subject"] = $postdata["emailmessagesubject"];
			$attachments = isset($postdata["emailmessageattachment"])?json_decode($postdata["emailmessageattachment"]):array();
			$messages['email']['html']['en']['none']['attachments'] = $attachments;
				
			// check for and retrieve translations
			if (isset($postdata["emailmessagetexttranslate"]) && $postdata["emailmessagetexttranslate"]) {
				foreach ($translationlanguages as $code => $language) {
					if (isset($postdata["emailmessagetexttranslate". $code. "text"])) {
						$translatedmessage = json_decode($postdata["emailmessagetexttranslate". $code. "text"], true);
						if ($translatedmessage["enabled"]) {
							// if the translation text is overridden, don't attach a source message
							// it isn't applicable since we have no way to know what they changed the text to.
							if ($translatedmessage["override"]) {
								// initially set the email to the english version, then overwrite the text. All other data is shared
								$messages['email']['html'][$code]['overridden'] = $messages['email']['html']['en']['none'];
								$messages['email']['html'][$code]['overridden']['text'] = $translatedmessage["text"];
							} else {
								// initially set the email to the english version, then overwrite the text. All other data is shared
								$messages['email']['html'][$code]['translated'] = $messages['email']['html']['en']['none'];
								$messages['email']['html'][$code]['translated']['text'] = $translatedmessage["text"];
								$messages['email']['html'][$code]['source'] = $messages['email']['html']['en']['none'];
							}
						}
					}
				}
			}
		}

		// ============================================================================================================================
		// SMS Message
		// ============================================================================================================================
		if (isset($postdata["hassms"]) && $postdata["hassms"] && $USER->authorize("sendsms"))
			$messages['sms']['plain']['en']['none']['text'] = $postdata["smsmessagetext"];

		// ============================================================================================================================
		// Social Media Message(s)
		// ============================================================================================================================
		if (isset($postdata["hasfacebook"]) && $postdata["hasfacebook"] && $USER->authorize("facebookpost"))
			$messages['post']['facebook']['en']['none']['text'] = $postdata["socialmediafacebookmessage"];

		if (isset($postdata["hastwitter"]) && $postdata["hastwitter"] && $USER->authorize("twitterpost"))
			$messages['post']['twitter']['en']['none']['text'] = $postdata["socialmediatwittermessage"];

		if (isset($postdata["hasfeed"]) && $postdata["hasfeed"] && $USER->authorize("feedpost")) {
			$feeddata = json_decode($postdata["socialmediafeedmessage"], true);
			$messages['post']['feed']['en']['none']['subject'] = $feeddata["subject"];
			$messages['post']['feed']['en']['none']['text'] = $feeddata["message"];
		}


		// #################################################################
		// create a message for each type/subtype/languagecode
		// for each message type
		foreach ($messages as $type => $msgdata) {
			// for each subtype
			foreach ($msgdata as $subtype => $msglang) {
				// for each language code
				foreach ($msglang as $langcode => $autotranslatevalues) {
					// for each autotranslate value
					foreach ($autotranslatevalues as $autotranslate => $data) {
						if ($data["text"]) {
							$message = new Message();
							$message->messagegroupid = $messagegroup->id;
							$message->type = $type;
							$message->subtype = $subtype;
							$message->autotranslate = $autotranslate;
							$message->name = $messagegroup->name;
							$message->description = Language::getName($langcode);
							$message->userid = $USER->id;

							// if this is an autotranslated message and an email. set the modify date in the past
							// this way re-translate will populate the message parts for us
							if ($autotranslate == 'translated' && $type == 'email')
								$message->modifydate = date("Y-m-d H:i:s", '1');
							else
								$message->modifydate = $messagegroup->modified;

							$message->languagecode = $langcode;

							if ($type == 'email' || ($type == "post" && $subtype == 'feed'))
								$message->subject = $data["subject"];
							if ($type == 'email') {
								$message->fromname = $data["fromname"];
								$message->fromemail = $data["from"];
							}

							$message->stuffHeaders();
							$message->create();

							// keep track of any post type messages for adding to jobpost table later
							if ($type == "post")
								$jobpostmessage[] = $subtype;

							// create the message parts
							$message->recreateParts($data['text'], null, isset($data['gender'])?$data['gender']:false);

							// if there are message attachments, attach them
							if (isset($data['attachments']) && $data['attachments']) {
								foreach ($data['attachments'] as $cid => $details) {
									$msgattachment = new MessageAttachment();
									$msgattachment->messageid = $message->id;
									$msgattachment->contentid = $cid;
									$msgattachment->filename = $details->name;
									$msgattachment->size = $details->size;
									$msgattachment->create();
								}
							} // end if there are attachments
						} // end if this message has a body
					} // end for each autotranslate value
				} // for each language code
			} // for each subtype
		} // for each message type

		// ============================================================================================================================
		// Job Post Destinations (facebook, twitter, feed, page)
		// ============================================================================================================================
		// store the jobpost messages
		$createdpostpage = false;
		foreach ($jobpostmessage as $subtype) {
			switch ($subtype) {
				case "facebook":
					// get the destinations for facebook
					if ($postdata["socialmediafacebookpage"]) {
						$fbpages = array();
						foreach (json_decode($postdata["socialmediafacebookpage"]) as $pageid) {
							if ($pageid == "me")
								$pageid = $USER->getSetting("fb_user_id");
							$fbpages[] = $pageid;
						}
						$job->updateJobPost("facebook", $fbpages);
					}
					break;
				case "twitter":
					$twitterauth = json_decode($USER->getSetting("tw_access_token"));
					$job->updateJobPost("twitter", $twitterauth->user_id);
					break;
				case "feed":
					if ($postdata["socialmediafeedcategory"] && count($postdata["socialmediafeedcategory"]))
						$job->updateJobPost("feed", $postdata["socialmediafeedcategory"]);
					break;
				case "page":
				case "voice":
					if (!$createdpostpage) {
						$createdpostpage = true;
						$job->updateJobPost("page", "");
					}
			}
		}

		// run the job
		$job->runNow();

		Query("COMMIT");
		if ($ajax)
			$form->sendTo("start.php");
		else
			redirect("start.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:jobs";
$TITLE = "";
$MESSAGESENDER = true;

include("nav.inc.php");
// Load Custom Form Validators
?>
<script type="text/javascript">
<?Validator::load_validators(array("ValInArray", "ValDuplicateNameCheck", "ValHasMessage",
	"ValMessageBody", "ValEasycall", "ValLists", "ValTranslation", "ValEmailAttach",
	"ValTimeWindowCallLate", "ValTimeWindowCallEarly", "ValSmsText", "valPhone",
	"ValMessageBody", "ValMessageGroup", "ValMessageTypeSelect", "ValFacebookPage",
	"ValTranslationCharacterLimit","ValTimePassed","ValTtsText","ValCallerID",
	"ValTextAreaAndSubjectWithCheckbox", "ValConditionalOnValue"));?>

	// get php data into js vars
	var userid = <? print_r($_SESSION['user']->id); ?>;
	var fbAppId = <? print_r($SETTINGS['facebook']['appid']); ?>;
	var twitterReservedChars = <? print_r(mb_strlen(" http://". getSystemSetting("tinydomain"). "/") + 6); ?>;
	
	// get template settings (if loading from template, they will be set in session data)
	var subject = <?echo (isset($_SESSION['message_sender']['template']['subject'])?("'". str_replace("'", "\'", $_SESSION['message_sender']['template']['subject']). "'"):"''")?>;
	var lists = <?echo (isset($_SESSION['message_sender']['template']['lists'])?$_SESSION['message_sender']['template']['lists']:'[]')?>;
	var jtid = <?echo (isset($_SESSION['message_sender']['template']['jobtypeid'])?$_SESSION['message_sender']['template']['jobtypeid']:0)?>;
	var mgid = <?echo (isset($_SESSION['message_sender']['template']['messagegroupid'])?$_SESSION['message_sender']['template']['messagegroupid']:0)?>;
	
</script>

<?PreviewModal::includePreviewScript();?>

<form id="<?=$form->name?>" name="<?=$form->name?>" action="/default/message_sender.php?form=msgsndr" method="POST" ><?

include("message_sender/index.php");

// cheat and create the hidden translation fields now
?><div style="display:none"><?
foreach ($ttslanguages as $code => $language) {
	$fieldname = "msgsndr_phonemessagetexttranslate". $code. "text";
	?><input type="hidden" name="<?=$fieldname?>" id="<?=$fieldname?>"><?
}
foreach ($translationlanguages as $code => $language) {
	$fieldname = "msgsndr_emailmessagetexttranslate". $code. "text";
	?><input type="hidden" name="<?=$fieldname?>" id="<?=$fieldname?>"><?
}
?></div>
</form><?

$posturl = $_SERVER['REQUEST_URI'];
$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
$posturl .= "form=". $form->name;

// render the items to get form data, but discard the html
$discard = $form->renderFormItems();
$discard = "";
// render form javascript
echo $form->renderJavascriptLibraries();
echo $form->renderFormJavascript($posturl);
echo $form->renderJavascript();

include("navbottom.inc.php"); ?>