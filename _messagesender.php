<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/Phone.obj.php");

// DBMO
require_once("obj/JobType.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/MessagePart.obj.php");
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
require_once("obj/TextAreaPhone.val.php");
require_once("obj/TraslationItem.fi.php");
require_once("obj/CallerID.fi.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
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
			$audiofile = DBFind("AudioFile", "from audiofile where id = ? and userid = ?", false, array($afid, $USER->id));
			if (!$audiofile)
				return "$this->label "._L("has invalid or missing messages");
		}
		return true;
	}
}

class ValJobName extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $USER;
		$jobcount = QuickQuery("select count(id) from job where not deleted and userid=? and name=? and status in ('new','scheduled','processing','procactive','active')", false, array($USER->id, $value));
		if ($jobcount)
			return "$this->label: ". _L('There is already an active notification with this name. Please choose another.');
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

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$userjobtypes = JobType::getUserJobTypes(false);

// need to reserve some characters for the link url and the six byte code. (http://smalldomain.com/<code>)
$twitterreservedchars = mb_strlen(" http://". getSystemSetting("tinydomain"). "/") + 6;

//Get available languages
$translationlanguages = Voice::getTTSLanguageMap();
unset($translationlanguages['en']);

$formdata = array(
	"name" => array(
		"label" => "name",
		"value" => "",
		"validators" => array(
			array("ValJobName"),
			array("ValLength","max" => 30)
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"jobtype" => array(
		"label" => "jobtype",
		"value" => "",
		"validators" => array(
			array("ValInArray", "values" => array_keys($userjobtypes))
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	//=========================================================================================
	"LIST DATA",
	//=========================================================================================
	"addmephone" => array(
		"label" => "addmephone",
		"value" => "",
		"validators" => array(
			array("ValPhone")
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"addmeemail" => array(
		"label" => "addmeemail",
		"value" => "",
		"validators" => array(
			array("ValEmail")
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"addmesms" => array(
		"label" => "addmesms",
		"value" => "",
		"validators" => array(
			array("ValPhone")
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"listids" => array(
		"label" => "listids",
		"value" => "",
		"validators" => array(
			array("ValLists")
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	//=========================================================================================
	"PHONE MESSAGE",
	//=========================================================================================
	"phonemessagecallme" => array(
		"label" => "phonemessagecallme",
		"value" => "",
		"validators" => array(
			array("ValEasycall")
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"phonemessagetext" => array(
		"label" => "phonemessagetext",
		"value" => "",
		"validators" => array(
			array("ValTextAreaPhone"),
			array("ValTtsText"),
			array("ValLength","max" => 10000) // 10000 Characters is about 40 minutes of tts, considered to be more than enough
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"phonemessagetexttranslate" => array(
		"label" => "phonemessagetexttranslate",
		"value" => "",
		"validators" => array(
			array("ValTranslationCharacterLimit", "field" => "phonemessagetext")
		),
		"control" => array("TextField"),
		"helpstep" => 1
	)
);

foreach ($translationlanguages as $code => $language) {
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
	"emailmessagefromname" => array(
		"label" => "emailmessagefromname",
		"value" => "",
		"validators" => array(
			array("ValLength","max" => 50)
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"emailmessagefromemail" => array(
		"label" => "emailmessagefromemail",
		"value" => "",
		"validators" => array(
			array("ValLength","max" => 255),
			array("ValEmail", "domain" => getSystemSetting('emaildomain'))
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"emailmessagesubject" => array(
		"label" => "emailmessagesubject",
		"value" => "",
		"validators" => array(
			array("ValLength","max" => 255)
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"emailmessageattachment" => array(
		"label" => "emailmessageattachment",
		"value" => "",
		"validators" => array(
			array("ValEmailAttach")
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"emailmessagetext" => array(
		"label" => "emailmessagetext",
		"value" => "",
		"validators" => array(
			array("ValMessageBody"),
			array("ValLength","max" => 256000)
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"emailmessagetexttranslate" => array(
		"label" => "emailmessagetexttranslate",
		"value" => "",
		"validators" => array(
			array("ValTranslationCharacterLimit", "field" => "emailmessagetext")
		),
		"control" => array("TextField"),
		"requires" => array("emailmessagetext"),
		"helpstep" => 1
	)
));

foreach ($translationlanguages as $code => $language) {
	$formdata["phonemessageemailtranslate". $code. "text"] = array(
		"label" => "phonemessageemailtranslate". $code. "text",
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
	"SMS MESSAGE",
	//=========================================================================================
	"smsmessagetext" => array(
		"label" => "smsmessagetext",
		"value" => "",
		"validators" => array(
			array("ValLength","max"=>160),
			array("ValSmsText")
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	//=========================================================================================
	"SOCIAL MESSAGE",
	//=========================================================================================
	"socialmediafacebookmessage" => array(
		"label" => "socialmediafacebookmessage",
		"value" => "",
		"validators" => array(
			array("ValLength","max"=>420)
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"socialmediatwittermessage" => array(
		"label" => "socialmediatwittermessage",
		"value" => "",
		"validators" => array(
			array("ValLength","max"=>(140 - $twitterreservedchars))
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"socialmediafeedmessage" => array(
		"label" => "socialmediafeedmessage",
		"value" => "",
		"validators" => array(
			array("ValTextAreaAndSubjectWithCheckbox","requiresubject" => true),
			array("ValLength","max"=>32000)
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"socialmediafacebookpage" => array(
		"label" => "socialmediafacebookpage",
		"value" => "",
		"validators" => array(
			array("ValFacebookPage", "authpages" => getFbAuthorizedPages(), "authwall" => getSystemSetting("fbauthorizewall"))
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"socialmediafeedcategory" => array( // TODO: this is currently a multi-checkbox
		"label" => "socialmediafeedcategory",
		"value" => "",
		"validators" => array(
			array("ValInArray", "values" => array_keys(FeedCategory::getAllowedFeedCategories()))
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	//=========================================================================================
	"JOB OPTIONS",
	//=========================================================================================
	"optionmaxjobdays" => array(
		"label" => "optionmaxjobdays",
		"value" => "",
		"validators" => array(
			array("ValInArray", "values" => range(1,$ACCESS->getValue('maxjobdays', 7)))
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"optionleavemessage" => array(
		"label" => "optionleavemessage",
		"value" => "",
		"validators" => array(
			// NOTE: no validation, will be ignored if the user can't use this option
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"optionmessageconfirmation" => array(
		"label" => "optionmessageconfirmation",
		"value" => "",
		"validators" => array(
			// NOTE: no validation, will be ignored if the user can't use this option
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"optionskipduplicate" => array( // NOTE: using same setting for both skipduplicates and skipemailduplicates?
		"label" => "optionskipduplicate",
		"value" => "",
		"validators" => array(
			// NOTE: no validation, will be ignored if the user can't use this option
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"optioncallerid" => array(
		"label" => "optioncallerid",
		"value" => "",
		"validators" => array(
			array("ValLength","min" => 0,"max" => 20),
			array("ValPhone"),
			array("ValCallerID")
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	//=========================================================================================
	"SCHEDULE OPTIONS",
	//=========================================================================================
	"scheduledate" => array(
		"label" => "scheduledate",
		"value" => "",
		"validators" => array(
			// TODO: date validation
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"schedulecallearly" => array(
		"label" => "schedulecallearly",
		"value" => "",
		"validators" => array(
			array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
			array("ValTimeWindowCallEarly", "calllatefield" => "schedulecalllate")
		),
		"control" => array("TextField"),
		"requires" => array("schedulecalllate", "scheduledate"),
		"helpstep" => 1
	),
	"schedulecalllate" => array(
		"label" => "schedulecalllate",
		"value" => "",
		"validators" => array(
			array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
			array("ValTimeWindowCallLate", "callearlyfield" => "schedulecallearly"),
			array("ValTimePassed", "field" => "scheduledate")
		),
		"control" => array("TextField"),
		"requires" => array("schedulecallearly", "scheduledate"),
		"helpstep" => 1
	)
));


$buttons = array(submit_button(_L('Save'),"submit","tick"),
		icon_button(_L('Cancel'),"cross",null,"start.php"));
$form = new Form("msgsndr",$formdata,array(),$buttons, "vertical");

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
		Query("BEGIN");

		//save data here


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
$TITLE = _L('Message Sender');

include_once("nav.inc.php");

// Load Custom Form Validators
?>
<script type="text/javascript">
<?Validator::load_validators(array("ValInArray", "ValJobName", "ValHasMessage",
	"ValTextAreaPhone", "ValEasycall", "ValLists", "ValTranslation", "ValEmailAttach",
	"ValTimeWindowCallLate", "ValTimeWindowCallEarly", "ValSmsText", "valPhone",
	"ValMessageBody", "ValMessageGroup", "ValMessageTypeSelect", "ValFacebookPage",
	"ValTranslationCharacterLimit","ValTimePassed","ValTtsText","ValCallerID",
	"ValTextAreaAndSubjectWithCheckbox"));?>
</script>
<?

startWindow(_L('Message Sender'));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>