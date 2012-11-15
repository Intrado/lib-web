<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Job.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Voice.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging'))
	redirect("unauthorized.php");

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$job = DBFind("Job", "from job where type = 'alert' and status = 'repeating'", false, array());

if (!$job)
	redirect("classroommessagetemplate.php");



// get this jobs messagegroup and it's messages
$messagesbylangcode = array();

$messagegroup = DBFind("MessageGroup", "from messagegroup where id = ? and type = 'classroomtemplate'", false, array($job->messagegroupid));

if (!$messagegroup)
	redirect("classroommessagetemplate.php");

$messages = DBFindMany("Message", "from message where messagegroupid = ? and type='phone'", false, array($messagegroup->id));
if ($messages) {
	foreach ($messages as $id => $message) {
		$messagesbylangcode[$message->languagecode] = $message;
	}
}



// get the customer default language data
$defaultcode = Language::getDefaultLanguageCode();
$defaultlanguage = Language::getName(Language::getDefaultLanguageCode());
$languagemap = Language::getLanguageMap();

// get the message parts for this message if it exists
$message = false;
if (isset($messagesbylangcode[$defaultcode])) {
	$message = $messagesbylangcode[$defaultcode];
	$parts = DBFindMany("MessagePart","from messagepart where messageid=? order by sequence", false, array($message->id));
}

// set the default language first and make it required
$formdata[] = _L("Default Template");
$formdata[$defaultcode . "-body"] = array(
	"label" => $defaultlanguage,
	"fieldhelp" => _L("Enter a default template message which Classroom Messages will be appended to."),
	"value" => ($message)?$message->format($parts):"",
	"validators" => array(
		array("ValRequired")),
	"control" => array("TextArea", "rows" => 10, "cols" => 60),
	"helpstep" => 1
);

// unset the default language so it doesn't get overwritten below
if (isset($languagemap[$defaultcode]))
	unset($languagemap[$defaultcode]);

$formdata[] = _L("Language Templates");

// create form items for all the customer's languages
foreach ($languagemap as $code => $language) {
	// get the message parts for this message if it exists
	$message = false;
	if (isset($messagesbylangcode[$code])) {
		$message = $messagesbylangcode[$code];
		$parts = DBFindMany("MessagePart","from messagepart where messageid=? order by sequence", false, array($message->id));
	}
	$formdata[$code . "-body"] = array(
		"label" => $language,
		"fieldhelp" => _L("Enter a %s template message which Classroom Messages will be appended to.",$language),
		"value" => ($message)?$message->format($parts):"",
		"validators" => array(),
		"control" => array("TextArea", "rows" => 10, "cols" => 60),
		"helpstep" => 2
	);
}

$helpsteps = array (
	_L('In the %s Template section, enter a tempate which Classroom Messages will be appended to.', $defaultlanguage),
	_L('For each lanugate, enter a template message to enable language specific Classroom Messages. If left empty the %s template will be used.',$defaultlanguage)		
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"classroommessagetemplate.php"));
$form = new Form("phonetemplateform",$formdata,$helpsteps,$buttons);

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
		
		$messagegroup->modified = date("Y-m-d H:i:s");
		$messagegroup->update();
		
		// get the default language code
		$defaultcode = Language::getDefaultLanguageCode();
		
		// update, create or orphan all the message parts.
		foreach(Language::getLanguageMap() as $code => $language) {
			// if there is a message body specified for this language code, create/update a message
			if ($postdata[$code . "-body"]) {
				// if the message is already associated, reuse it. otherwise create a new one
				if (isset($messagesbylangcode[$code])) {
					$message = $messagesbylangcode[$code];
				} else {
					$message = new Message();
				}
				$message->messagegroupid = $messagegroup->id;
				$message->userid = $job->userid;
				$message->name = $messagegroup->name;
				$message->description = $messagegroup->description;
				$message->type = 'phone';
				$message->subtype = 'voice';
				$message->autotranslate = 'none';
				$message->modifydate = date("Y-m-d H:i:s");
				$message->languagecode = $code;
				$message->recreateParts($postdata[$code . "-body"], null, "female");
				if ($message->id)
					$message->update();
				else
					$message->create();
			// if no body, orphan any existing message for this language code
			} else {
				if (isset($messagesbylangcode[$code])) {
					$messagesbylangcode[$code]->deleted = 1;
					$messagesbylangcode[$code]->update();
					QuickUpdate("update message set messagegroupid = null where id = ?", false, array($messagesbylangcode[$code]->id));
				}
			}
		}
		
		Query("COMMIT");
		
		if ($ajax)
			$form->sendTo("classroommessagetemplate.php");
		else
			redirect("classroommessagetemplate.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "";

include_once("nav.inc.php");

$TITLE = _L('Classroom Messaging Phone Template');
startWindow($TITLE);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>