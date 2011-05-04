<?
// Needs some GET request arguments:
//   mgid, where mgid is the messagegroup that will own this message
//   languagecode, where languagecode is the language of the message to be created

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/Phone.obj.php");

// form items/validators
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/PhoneMessageRecorder.fi.php");
require_once("obj/PhoneMessageRecorder.val.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendphone") || !$USER->authorize("starteasy"))
	redirect('unauthorized.php');


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['languagecode']) && isset($_GET['mgid'])) {
	$_SESSION['editmessage'] = array(
		"messagegroupid" => $_GET['mgid'],
		"languagecode" => $_GET['languagecode']);
	redirect("editmessagerecord.php");
}

// set the message bits
if (isset($_SESSION['editmessage']['messagegroupid']) && 
		isset($_SESSION['editmessage']['languagecode'])) {
	
	$messagegroup = new MessageGroup($_SESSION['editmessage']['messagegroupid']);
	$languagecode = $_SESSION['editmessage']['languagecode'];
} else {
	// missing session data!
	redirect('unauthorized.php');
}

// if the user doesn't own the parent message group, unauthorized!
if (!userOwns("messagegroup", $messagegroup->id))
	redirect('unauthorized.php');

// invalid language code specified?
if (!in_array($languagecode, array_keys(Language::getLanguageMap())))
	redirect('unauthorized.php');

$language = Language::getName($languagecode);

$formdata = array($messagegroup->name. " (". $language. ")");

$formdata["message"] = array(
	"label" => _L("Voice Recording"),
	"fieldhelp" => _L("TODO: field help"),
	"value" => "",
	"validators" => array(
		array("ValRequired"),
		array("PhoneMessageRecorderValidator")
	),
	"control" => array( "PhoneMessageRecorder", "phone" => $USER->phone, "name" => $language),
	"helpstep" => 1
);

$helpsteps[] = _L("The system will call you at the number you enter in this form and guide you through a series of prompts to record your message. The default message is always required and will be sent to any recipients who do not have a language specified.<br><br>
Choose which language you will be recording in and enter the phone number where the system can reach you. Then click \"Call Me to Record\" to get started. Listen carefully to the prompts when you receive the call. You may record as many different langauges as you need.
");

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"mgeditor.php?id=".$messagegroup->id));
$form = new Form("phonerecord",$formdata,$helpsteps,$buttons);

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
		
		// get an existing message to overwrite, if one exists
		$message = DBFind("Message", "from message
									where messagegroupid = ?
									and autotranslate in ('overridden', 'none', 'translated')
									and type = 'phone'
									and languagecode = ?", false, array($messagegroup->id, $languagecode));
		
		// if there is an existing message in the DB, must remove it's parts
		if ($message) {
			QuickUpdate("delete from messagepart where messageid = ?", false, array($message->id));
			// delete existing messages
			QuickUpdate("update message set deleted = 1 
						where messagegroupid = ?
						and type = 'phone'
						and languagecode = ?", false, array($messagegroup->id, $languagecode));
			
		} else {
			// no message, create a new one!
			$message = new Message();
		}
			
		$message->messagegroupid = $messagegroup->id;
		$message->type = 'phone';
		$message->subtype = 'voice';
		$message->autotranslate = 'none';
		$message->name = $messagegroup->name;
		$message->description = Language::getName($languagecode);
		$message->userid = $USER->id;
		$message->modifydate = date("Y-m-d H:i:s");
		$message->languagecode = $languagecode;
		$message->deleted = 0;
		
		if (!$message->id)
			$message->create();
		else
			$message->update();
		
		// pull the audiofileid from post data
		$audiofileidmap = json_decode($postdata["message"]);
		$audiofileid = $audiofileidmap->af;
			
		$part = new MessagePart();
		$part->messageid = $message->id;
		$part->type = "A";
		$part->audiofileid = $audiofileid;
		$part->sequence = 0;
		$part->create();
		
		Query("COMMIT");
		
		// remove the editors session data
		unset($_SESSION['editmessage']);
		
		if ($ajax)
			$form->sendTo("mgeditor.php?id=".$messagegroup->id);
		else
			redirect("mgeditor.php?id=".$messagegroup->id);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = "Phone Message Recorder";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("PhoneMessageRecorderValidator")); ?>
</script>
<?

startWindow($messagegroup->name);
?><pre><?
//var_dump($parts);
?></pre><?
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>