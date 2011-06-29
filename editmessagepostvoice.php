<?
// Needs some GET request arguments. Either:
//	 id, where id is the message id to be edited
// or:
//   mgid, where mgid is the messagegroup that will own this message

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
require_once("obj/Phone.obj.php");

// form items/validators
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/PhoneMessageEditor.fi.php");
require_once("obj/InpageSubmitButton.fi.php");

require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");
require_once("obj/PreviewButton.fi.php");
require_once("inc/appserver.inc.php");
require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once("inc/thrift.inc.php");
require_once $GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php';

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if ((!getSystemSetting('_hastwitter', false) || !$USER->authorize("twitterpost")) &&
		(!getSystemSetting('_hasfacebook', false) || !$USER->authorize("facebookpost")))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id']) && $_GET['id'] != "new") {
	// this is an edit for an existing message
	$_SESSION['editmessage'] = array("messageid" => $_GET['id']);
	redirect("editmessagepostvoice.php");
} else if (isset($_GET['mgid'])) {
	$_SESSION['editmessage'] = array("messagegroupid" => $_GET['mgid']);
	redirect("editmessagepostvoice.php");
}

// get the messagegroup and/or the message
if (isset($_SESSION['editmessage']['messageid']))
	$message = new Message($_SESSION['editmessage']['messageid']);
else
	$message = false;

// set the message bits
if ($message) {
	// if the user doesn't own this message, unauthorized!
	if (!userOwns("message", $message->id))
		redirect('unauthorized.php');
	
	// get the parent message group for this message
	$messagegroup = new MessageGroup($message->messagegroupid);
	
} else {
	// not editing an existing message, check session data for new message bits
	if (isset($_SESSION['editmessage']['messagegroupid']))
		$messagegroup = new MessageGroup($_SESSION['editmessage']['messagegroupid']);
	else // missing session data!
		redirect('unauthorized.php');
}

// if the user doesn't own the parent message group, unauthorized!
if (!userOwns("messagegroup", $messagegroup->id))
	redirect('unauthorized.php');

PreviewModal::HandleRequestWithPhoneText($messagegroup->id);

$text = "";
$gender = $messagegroup->preferredgender;
if ($message) {
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$text = Message::format($parts);
	// find the gender
	foreach ($parts as $part) {
		if ($part->voiceid) {
			$voice = new Voice($part->voiceid);
			$gender = $voice->gender;
			break;
		}
	}
}

// get user default gender selection if none assigned
if (!$gender)
	$gender = $USER->getSetting('defaultgender', "female");

// upload audio needs this session data
$_SESSION['messagegroupid'] = $messagegroup->id;

$formdata = array(
	$messagegroup->name. " (". _L("Voice Post"). ")",
	"message" => array(
		"label" => _L("Advanced Message"),
		"fieldhelp" => _L("Enter your voice message in this field. Click on the 'Guide' button for help with the different options which are available to you."),
		"value" => $text,
		"validators" => array(
			array("ValRequired"),
			array("ValMessageBody", "messagegroupid" => $messagegroup->id)),
		"control" => array("PhoneMessageEditor", "enablefieldinserts" => false, "messagegroupid" => $messagegroup->id),
		"helpstep" => 1
	),
	"gender" => array(
		"label" => _L("Gender"),
		"fieldhelp" => _L("Select the gender of the text-to-speech voice."),
		"value" => $gender,
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array("female", "male"))),
		"control" => array("RadioButton", "values" => array("female" => _L("Female"), "male" => _L("Male"))),
		"helpstep" => 2
	),
	"preview" => array(
		"label" => null,
		"value" => "",
		"validators" => array(),
		"control" => array("PreviewButton",
			"language" => Language::getDefaultLanguageCode(),
			"texttarget" => "message",
			"gendertarget" => "gender",
		),
		"helpstep" => 3
	)
);

$helpsteps = array(_L("<p>You can use a variety of techniques to build your message in this screen, but ideally you should ".
	"use this to assemble snippets of audio with dynamic data field inserts. You can use 'Call me to Record' to create your ".
	"audio snippets or upload pre-recorded audio from your computer. To record multiple audio snippets, you can use 'Call me ".
	"to Record' for each snippet. </p>"),
	_L("If your message contains pieces that will be read by a text-to-speech voice, such as data fields or other text, select ".
	"the gender of the text-to-speech voice. For best results, it's a good idea to select the same gender as the speaker in the audio files."),
	_L("Click the preview button to hear a preview of your message."));
		
$buttons = array(submit_button(_L('Done'),"submit","tick"));
$form = new Form("phoneadvanced",$formdata,$helpsteps,$buttons,"vertical");

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
		
		// if they didn't change anything, don't do anything
		if ($postdata['message'] == $text && $postdata['gender'] == $gender) {
			// DO NOT UPDATE MESSAGE!
		} else if ($button != 'inpagesubmit'){
			Query("BEGIN");
			
			// update usersetting and message group for default gender
			$USER->setSetting('defaultgender', $gender);
			$messagegroup->preferredgender = $postdata['gender'];
			$messagegroup->stuffHeaders();
			$messagegroup->modified = date("Y-m-d H:i:s", time());
			$messagegroup->update(array("data","modified"));
			
			// if this is an edit for an existing message
			if ($message) {
				// delete existing messages (not this one though)
				QuickUpdate("delete from message 
						where messagegroupid = ?
						and type = 'post'
						and subtype = 'voice'
						and id != ?", false, array($messagegroup->id, $message->id));
			} else {
				// delete existing messages
				QuickUpdate("delete from message 
						where messagegroupid = ?
						and type = 'post'
						and subtype = 'voice'", false, array($messagegroup->id));
				// new message
				$message = new Message();
			}
			
			$message->messagegroupid = $messagegroup->id;
			$message->type = "post";
			$message->subtype = "voice";
			$message->autotranslate = 'none';
			$message->name = $messagegroup->name;
			$message->description = Language::getName(Language::getDefaultLanguageCode());
			$message->userid = $USER->id;
			$message->modifydate = date("Y-m-d H:i:s");
			$message->languagecode = Language::getDefaultLanguageCode();
			$message->deleted = 0;
			
			if ($message->id)
				$message->update();
			else
				$message->create();
						
			// create the message parts
			$message->recreateParts($postdata['message'], null, $postdata['gender']);
					
			Query("COMMIT");
		}
		
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
$TITLE = "Advanced Phone Message Editor";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody")); ?>
</script>
<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
<script src="script/livepipe/window.js" type="text/javascript"></script>
<script src="script/modalwrapper.js" type="text/javascript"></script>
<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>

<?
PreviewModal::includePreviewScript();

startWindow($messagegroup->name);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>