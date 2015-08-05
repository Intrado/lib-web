<?
// Needs some GET request arguments. Either:
//	 id, where id is the message id to be edited
// or:
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
require_once("obj/Phone.obj.php");
require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");

// form items/validators
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/PhoneMessageEditor.fi.php");
require_once("obj/PreviewButton.fi.php");
require_once("obj/ValTtsText.val.php");

// appserver and thrift includes
require_once("inc/appserver.inc.php");

require_once("inc/editmessagecommon.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendphone")) {
	if (isset($_REQUEST['api'])) {
		header("HTTP/1.1 403 Forbidden");
		exit();
	}

	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
setEditMessageSession();

// get the messagegroup and/or the message
if (isset($_SESSION['editmessage']['messageid']))
	$message = new Message($_SESSION['editmessage']['messageid']);
else
	$message = false;

// set the message bits
if ($message) {
	// if the user doesn't own this message, unauthorized!
	if (!userOwns("message", $message->id)) {
		if (isset($_REQUEST['api'])) {
			header("Content-Type: application/json");
			exit(json_encode(Array("status" => "messageNotFound")));
		}

		redirect('unauthorized.php');
	}
	
	// get the parent message group for this message
	$messagegroup = new MessageGroup($message->messagegroupid);
	// use the message's language code
	$languagecode = $message->languagecode;
	
} else {
	// not editing an existing message, check session data for new message bits
	if (isset($_SESSION['editmessage']['messagegroupid']) && 
        isset($_SESSION['editmessage']['languagecode'])) {
		
		$messagegroup = new MessageGroup($_SESSION['editmessage']['messagegroupid']);
		$languagecode = $_SESSION['editmessage']['languagecode'];
	} else {
		if (isset($_REQUEST['api'])) {
			header('Content-Type: application/json');
			exit(json_encode(Array("status" => "resourceNotFound", "message" => "Message group not specified")));
		}

		// missing session data!
		redirect('unauthorized.php');
	}
}

// if the user doesn't own the parent message group, unauthorized!
if (!userOwns("messagegroup", $messagegroup->id) ||
	(!isset($_REQUEST["msgdel"]) && $messagegroup->deleted)) {
	if (isset($_REQUEST['api'])) {
		header("Content-Type: application/json");
		exit(json_encode(Array("status" => "messageGroupNotFound")));
	}

	redirect('unauthorized.php');
}

// invalid language code specified?
if (!in_array($languagecode, array_keys(Language::getLanguageMap()))) {
	if (isset($_REQUEST['api'])) {
		header("Content-Type: application/json");
		exit(json_encode(Array("status" => "invalidParameter", "message" => "Invalid language code " . $languagecode)));
	}

	redirect('unauthorized.php');
}

// no multi lingual and not default language code
if (!$USER->authorize("sendmulti") && $languagecode != Language::getDefaultLanguageCode()) {
	if (isset($_REQUEST['api'])) {
		header("HTTP/1.1 403 Forbidden");
		exit();
	}

	redirect('unauthorized.php');
}

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

$language = Language::getName($languagecode);

// get user default gender selection if none assigned
if (!$gender)
	$gender = $USER->getSetting('defaultgender', "female");

$formdata = array($messagegroup->name. " (". $language. ")");

// upload audio needs this session data
$_SESSION['messagegroupid'] = $messagegroup->id;


$formdata = array($messagegroup->name. " (". $language. ")");
	
$ttslanguages = Voice::getTTSLanguageMap();
if (!isset($ttslanguages[$languagecode])) {
	$html = _L('<ul>
				<li>This language does not support <i>Text To Speech</i>.</li>
				<li>Any text items inserted will be spoken in an <b>English</b> voice.</li>
				</ul>');
	$formdata["note"] = array(
		"label" => _L("Language Note"),
		"fieldhelp" => _L("This language does not support Text To Speech."),
		"control" => array("FormHtml", "html" => $html),
		"helpstep" => 1);
}

$formdata["message"] = array(
		"label" => _L("Advanced Message"),
		"fieldhelp" => _L("Enter your phone message in this field. Click on the 'Guide' button for help with the different options which are available to you."),
		"value" => $text,
		"validators" => array(
			array("ValRequired"),
			array("ValMessageBody", "messagegroupid" => $messagegroup->id),
			array("ValLength","max" => 10000), // 10000 Characters is about 40 minutes of tts, considered to be more than enough
			array("ValTtsText")
		),
		"control" => array("PhoneMessageEditor",
			"messagegroupid" => $messagegroup->id,
			"phone" => $USER->phone,
			"languages" => array($languagecode => Language::getName($languagecode)),
			"phonemindigits" => getCustomerSystemSetting("easycallmin", 10),
			"phonemaxdigits" => getCustomerSystemSetting("easycallmax", 10)
		),
		"helpstep" => 1);
$formdata["gender"] = array(
		"label" => _L("Gender"),
		"fieldhelp" => _L("Select the gender of the text-to-speech voice. Some languages are only available in one gender. In those cases, selecting a different gender will result in the same message playback."),
		"value" => $gender,
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array("female", "male"))),
		"control" => array("RadioButton", "values" => array("female" => _L("Female"), "male" => _L("Male"))),
		"helpstep" => 2);
$formdata["preview"] = array(
		"label" => null,
		"value" => "",
		"validators" => array(),
		"control" => array("PreviewButton",
			"language" => $languagecode,
			"texttarget" => "message",
			"gendertarget" => "gender",
		),
		"helpstep" => 3);

$helpsteps = array(_L("<p>You can use a variety of techniques to build your message in this screen, but ideally you should ".
	"use this to assemble snippets of audio with dynamic data field inserts. You can use 'Call me to Record' to create your ".
	"audio snippets or upload pre-recorded audio from your computer. To record multiple audio snippets, you can use 'Call me ".
	"to Record' for each snippet. </p><p>To insert data fields, set the cursor where the data should appear. Be careful to not ".
	"delete any of the brackets that appear around audio snippets or other data fields. Select the data field you wish to ".
	"insert and enter a default value which will display if a recipient does not have data in the chosen field. Click the ".
	"'Insert' button to add the data field to your message.</p>"),
	_L("If your message contains pieces that will be read by a text-to-speech voice, such as data fields or other text, select ".
	"the gender of the text-to-speech voice. For best results, it's a good idea to select the same gender as the speaker in the audio files.".
	"<br><br><i><b>Note:</b> Some languages are only available in one gender. In those cases, selecting a different gender will result in the same message playback.</i>"),
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
				// delete existing messages
				QuickUpdate("delete from message 
						where messagegroupid = ?
						and type = 'phone'
						and languagecode = ?
						and id != ?", false, array($messagegroup->id, $languagecode, $message->id));
			} else {
				// new message
				$message = new Message();
			}
			
			$message->messagegroupid = $messagegroup->id;
			$message->type = "phone";
			$message->subtype = "voice";
			$message->autotranslate = ($languagecode == "en")?'none':'overridden';
			$message->name = $messagegroup->name;
			$message->description = Language::getName($languagecode);
			$message->userid = $USER->id;
			$message->modifydate = date("Y-m-d H:i:s");
			$message->languagecode = $languagecode;
			
			if ($message->id)
				$message->update();
			else
				$message->create();
						
			// create the message parts
			$message->recreateParts($postdata['message'], null, $postdata['gender']);
			
			// Hack to correct the voice id for non tts languages
			if (!isset($ttslanguages[$languagecode])) {
				// get all T and V message parts, update the voiceid to represent the selected gender
				$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? and type in ('V','T')", false, array($message->id));
				foreach ($parts as $part) {
					$part->voiceid = Voice::getPreferredVoice("en", $postdata['gender']);
					$part->update();
				}
			}
			
			$messagegroup->updateDefaultLanguageCode();
			
			Query("COMMIT");
		}

		// remove the editors session data
		unset($_SESSION['editmessage']);
		
		if ($ajax)
			$form->sendTo(getEditMessageSendTo($messagegroup->id), Array("message" => Array("id" => (int)$message->id)));
		else
			redirect(getEditMessageSendTo($messagegroup->id));
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
<? Validator::load_validators(array("ValMessageBody","ValTtsText")); ?>
</script>
<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>

<?
PreviewModal::includePreviewScript();

startWindow($messagegroup->name);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>