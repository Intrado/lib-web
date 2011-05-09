<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/Wizard.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/SpecialTask.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/Voice.obj.php");
require_once("inc/translate.inc.php");
require_once("obj/Sms.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/MessageAttachment.obj.php");

// form items
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/HtmlRadioButtonBigCheck.fi.php");
require_once("obj/PhoneMessageRecorder.fi.php");
require_once("obj/PhoneMessageRecorder.val.php");
require_once("obj/TextAreaPhone.fi.php");
require_once("obj/TextAreaPhone.val.php");
require_once("obj/EmailAttach.fi.php");
require_once("obj/EmailAttach.val.php");
require_once("obj/HtmlTextArea.fi.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/traslationitem.obj.php");
require_once("obj/CheckBoxWithHtmlPreview.fi.php");
require_once("obj/PhoneMessageEditor.fi.php");
require_once("obj/EmailMessageEditor.fi.php");
require_once("obj/InpageSubmitButton.fi.php");

// Message step form data
require_once("inc/messagewizard.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendphone") || !$USER->authorize("sendemail") || !$USER->authorize("sendsms"))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Passed parameter checking
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['mgid']) && $_GET['mgid']) {
	if (!userOwns('messagegroup', $_GET['mgid']))
		redirect('unauthorized.php');
	
	$_SESSION['wizard_message_mgid'] = ($_GET['mgid'] + 0);		
}

if (isset($_GET['debug']))
	$_SESSION['wizard_message']['debug'] = true;

$wizdata = array(
	"start" => new MsgWiz_start(_L("Type")),
	"method" => new MsgWiz_method(_L("Method")),
	"create" => new WizSection ("Create",array(
		"language" => new MsgWiz_language(_L("Language")),
		"phonetext" => new MsgWiz_phoneText(_L("Text-to-speech")),
		"callme" => new MsgWiz_phoneEasyCall(_L("Record")),
		"phoneadvanced" => new MsgWiz_phoneAdvanced(_L("Advanced")),
		"emailtext" => new MsgWiz_emailText(_L("Compose Email")),
		"translatepreview" => new MsgWiz_translatePreview(_L("Translations")),
		"smstext" => new MsgWiz_smsText(_L("SMS Text"))
	)),
	"submit" => new WizSection ("Confirm",array(
		"confirm" => new MsgWiz_submitConfirm(_L("To be Overwritten"))
	))
);

$wizard = new Wizard("wizard_message",$wizdata, new FinishMessageWizard("Finish"));
$wizard->doneurl = "mgeditor.php";
$wizard->handlerequest();

// After reload check session data for job type information
if (isset($_SESSION['wizard_message_mgid'])) {
	$_SESSION['wizard_message']['mgid'] = $_SESSION['wizard_message_mgid'];
	unset($_SESSION['wizard_message_mgid']);
	
	// check that this is a valid message group
	$messagegroup = new MessageGroup($_SESSION['wizard_message']['mgid']);
	if (!userOwns("messagegroup", $messagegroup->id) || $messagegroup->deleted)
		redirect('unauthorized.php');
}

// if the message group id isn't set in session data, redirect to unauth
if (!isset($_SESSION['wizard_message']['mgid']))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = _L("notifications").":"._L("messages");
$TITLE = false;

require_once("nav.inc.php");

?>
<script type="text/javascript">
<?	Validator::load_validators(array("PhoneMessageRecorderValidator", "ValTextAreaPhone", "ValMessageBody"));?>
</script>
<?

startWindow(_L("Add Content Wizard"));
echo $wizard->render();
endWindow();
if (isset($_SESSION['wizard_message']['debug']) && $_SESSION['wizard_message']['debug']) {
	startWindow("Wizard Data");
	echo "<pre>";
	var_dump($_SESSION['wizard_message']);
	echo "</pre>";
	endWindow();
}
require_once("navbottom.inc.php");

?>