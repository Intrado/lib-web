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
require_once("obj/EmailAttach.val.php");
require_once("obj/PhoneMessageRecorder.fi.php");
require_once("obj/PhoneMessageRecorder.val.php");
require_once("obj/EmailAttach.fi.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/EmailMessageEditor.fi.php");
require_once("obj/PhoneMessageEditor.fi.php");
require_once("obj/InpageSubmitButton.fi.php");


if (isset($_GET['id']) && $_GET['id'] != "new")
	$message = new Message($_GET['id']);
else
	$message = false;

if (isset($_GET['mgid']))
	$messagegroup = new MessageGroup($_GET['mgid']);
else
	$messagegroup = false;

// MUST have one or both of the following	
if (!$messagegroup && !$message)
	redirect('unauthorized.php');

// if the message is set, make sure the user is authorized to edit it
if ($message && !userOwns("message", $message->id))
	redirect('unauthorized.php');

// if the message group is set, make sure the user is authorized to edit it
if ($messagegroup && !userOwns("messagegroup", $messagegroup->id))
	redirect('unauthorized.php');

$languagecode = false;
$type = false;
$subtype = "";
if ($message) {
	// use the message's message group if it's set
	$messagegroup = new MessageGroup($message->messagegroupid);
	// use the message's language code
	$languagecode = $message->languagecode;
	// discover the type if the message is set.
	$type = $message->type;
	// emails need a subtype to tell if it's plain or html
	$subtype = $message->subtype;
} else {
	if (isset($_GET['type']))
		$type = $_GET['type'];
	if (isset($_GET['subtype']))
		$subtype = $_GET['subtype'];
	if (isset($_GET['languagecode']))
		$languagecode = $_GET['languagecode'];
}

// invalid language code specified?
if (!in_array($languagecode, array_keys(Language::getLanguageMap())))
	redirect('unauthorized.php');

// load up the appropriate editor
switch ($type) {
	case "record":
		require_once("inc/editmessageRecord.inc.php");
		$TITLE = "Call Me To Record";
		break;
	case "phone":
		require_once("inc/editmessagePhone.inc.php");
		$TITLE = "Advanced Phone Message Editor";
		break;
	case "email":
		require_once("inc/editmessageEmail.inc.php");
		if ($subtype == "plain")
			$TITLE = "Plain Email Editor";
		else
			$TITLE = "Advanced Email Editor";
		break;
	case "sms":
		require_once("inc/editmessageSms.inc.php");
		$TITLE = "SMS/Text Message";
		break;
	default:
		error_log("Unknown message type: ". $type);
		// unknown message type? bail...
		redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody", "ValEmailAttach", "PhoneMessageRecorderValidator")); ?>
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