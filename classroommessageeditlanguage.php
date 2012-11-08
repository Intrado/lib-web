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
require_once("obj/TargetedMessage.obj.php");

// appserver and thrift includes
require_once("inc/appserver.inc.php");

require_once("inc/editmessagecommon.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
setEditMessageSession();

// get the messagegroup and/or the message
if (isset($_SESSION['editmessage']['messagegroupid']) && 
		isset($_SESSION['editmessage']['languagecode'])) {
	
	$messagegroup = new MessageGroup($_SESSION['editmessage']['messagegroupid']);
	$languagecode = $_SESSION['editmessage']['languagecode'];
	$phonemessage = DBFind("Message", "from message where messagegroupid=? and languagecode=? and type='phone' and subtype='voice'",false,array($messagegroup->id,$languagecode));
	$emailmessage = DBFind("Message", "from message where messagegroupid=? and languagecode=? and type='email' and subtype='plain'",false,array($messagegroup->id,$languagecode));
} else {
	// missing session data!
	redirect('unauthorized.php');
}


$targetedmessage = DBFind("TargetedMessage", "from targetedmessage where overridemessagegroupid=?",false,array($messagegroup->id));
// Message group must be connected to a targeted message to be able to edit here since we can not use userowns
if (!$targetedmessage)
	redirect('unauthorized.php');

// invalid language code specified?
if (!in_array($languagecode, array_keys(Language::getLanguageMap())))
	redirect('unauthorized.php');

// no multi lingual and not default language code
if (!$USER->authorize("sendmulti") && $languagecode != Language::getDefaultLanguageCode())
	redirect('unauthorized.php');

PreviewModal::HandleRequestWithPhoneText($messagegroup->id);

$phonemessagetext = "";
$emailmessagetext = "";

$gender = $messagegroup->preferredgender;
if ($phonemessage) {
	$phonemessageparts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($phonemessage->id));
	$phonemessagetext = Message::format($phonemessageparts);
	// find the gender
	foreach ($phonemessageparts as $part) {
		if ($part->voiceid) {
			$voice = new Voice($part->voiceid);
			$gender = $voice->gender;
			break;
		}
	}
}

if ($emailmessage) {
	$emailmessageparts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($emailmessage->id));
	$emailmessagetext = Message::format($emailmessageparts);
}

$language = Language::getName($languagecode);

// get user default gender selection if none assigned
if (!$gender)
	$gender = $USER->getSetting('defaultgender', "female");


// upload audio needs this session data
$_SESSION['messagegroupid'] = $messagegroup->id;


$formdata = array();

$formdata[] = _L("Email");
$formdata["emailmessage"] = array(
		"label" => _L("Message"),
		"fieldhelp" => _L("Enter your phone message in this field. Click on the 'Guide' button for help with the different options which are available to you."),
		"value" => $emailmessagetext,
		"validators" => array(
				array("ValRequired")
		),
		"control" => array("TextArea","rows" => 3),
		"helpstep" => 1);

$formdata[] = _L("Phone");

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

$formdata["phonemessage"] = array(
		"label" => _L("Message"),
		"fieldhelp" => _L("Enter your phone message in this field. Click on the 'Guide' button for help with the different options which are available to you."),
		"value" => $phonemessagetext,
		"validators" => array(
			array("ValRequired"),
			array("ValMessageBody", "messagegroupid" => $messagegroup->id),
			array("ValLength","max" => 10000), // 10000 Characters is about 40 minutes of tts, considered to be more than enough
			array("ValTtsText")
		),
		"control" => array("PhoneMessageEditor", "langcode" => $languagecode, "messagegroupid" => $messagegroup->id),
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
			"texttarget" => "phonemessage",
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
		
$buttons = array(submit_button(_L('Save'),"submit","tick"),icon_button(_L("Cancel"), "cross",null,"classroommessageedit.php"));
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
		if ($postdata['phonemessage'] == $phonemessagetext && 
			$postdata['emailmessage'] == $emailmessagetext &&
			$postdata['gender'] == $gender) {
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
			if ($phonemessage) {
				// delete existing messages
				QuickUpdate("delete from message 
						where messagegroupid = ?
						and type = 'phone'
						and languagecode = ?
						and id != ?", false, array($messagegroup->id, $languagecode, $phonemessage->id));
			} else {
				// new message
				$phonemessage = new Message();
			}
			
			$phonemessage->messagegroupid = $messagegroup->id;
			$phonemessage->type = "phone";
			$phonemessage->subtype = "voice";
			$phonemessage->autotranslate = ($languagecode == "en")?'none':'overridden';
			$phonemessage->name = $messagegroup->name;
			$phonemessage->description = Language::getName($languagecode);
			$phonemessage->userid = $USER->id;
			$phonemessage->modifydate = date("Y-m-d H:i:s");
			$phonemessage->languagecode = $languagecode;
			
			if ($phonemessage->id)
				$phonemessage->update();
			else
				$phonemessage->create();
						
			// create the message parts
			$audiofileids = MessageGroup::getReferencedAudioFileIDs($messagegroup->id);
				
			$phonemessage->recreateParts($postdata['phonemessage'], null, $postdata['gender'],$audiofileids);
			
			// Hack to correct the voice id for non tts languages
			if (!isset($ttslanguages[$languagecode])) {
				// get all T and V message parts, update the voiceid to represent the selected gender
				$phonemessageparts = DBFindMany("MessagePart", "from messagepart where messageid = ? and type in ('V','T')", false, array($phonemessage->id));
				foreach ($phonemessageparts as $part) {
					$part->voiceid = Voice::getPreferredVoice("en", $postdata['gender']);
					$part->update();
				}
			}
			
			// if this is an edit for an existing message
			if ($emailmessage) {
				// delete existing messages
				QuickUpdate("delete from message
						where messagegroupid = ?
						and type = 'email' 
						and subtype = 'plain' 
						and languagecode = ?
						and id != ?", false, array($messagegroup->id, $languagecode, $emailmessage->id));
			} else {
				// new message
				$emailmessage = new Message();
			}
				
			$emailmessage->messagegroupid = $messagegroup->id;
			$emailmessage->type = "email";
			$emailmessage->subtype = "plain";
			$emailmessage->autotranslate = ($languagecode == "en")?'none':'overridden';
			$emailmessage->name = $messagegroup->name;
			$emailmessage->description = Language::getName($languagecode);
			$emailmessage->userid = $USER->id;
			$emailmessage->modifydate = date("Y-m-d H:i:s");
			$emailmessage->languagecode = $languagecode;
				
			if ($emailmessage->id)
				$emailmessage->update();
			else
				$emailmessage->create();
			
			// create the message parts
			$emailmessage->recreateParts($postdata['emailmessage'], null, false);
			
			$messagegroup->updateDefaultLanguageCode();
			
			Query("COMMIT");
		}
		
		// remove the editors session data
		unset($_SESSION['editmessage']);
		
		if ($ajax)
			$form->sendTo(getEditMessageSendTo($messagegroup->id));
		else
			redirect(getEditMessageSendTo($messagegroup->id));
	}
}




////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "Classroom Message Editor";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody","ValTtsText")); ?>
</script>
<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>

<?
PreviewModal::includePreviewScript();

startWindow("{$messagegroup->name} ({$language})");
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>