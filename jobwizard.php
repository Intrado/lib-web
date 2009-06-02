<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Wizard.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/SpecialTask.obj.php");
require_once("obj/ListForm.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

// Job step form data
require("jobwizard.inc.php");

$wizdata = array(
	"basic" => new JobWiz_basic(_L("Start")),
	"list" => new JobWiz_listChoose(_L("List")),
	"message" => new WizSection("Message",array(
		"pick" => new JobWiz_messageType(_L("Delivery Methods")),
		"select" => new JobWiz_messageSelect(_L("Message Source")),
		"phone"	=> new WizSection ("Phone",array(
			"pick" => new JobWiz_messagePhoneChoose(_L("Existing Message")),
			"text" =>	new JobWiz_messagePhoneText(_L("Text-to-speech")),
			"translate" => new JobWiz_messagePhoneTranslate(_L("Translations")),
			"callme" => new JobWiz_messagePhoneCallMe(_L("Record"))
		)),
		"email"	=> new WizSection ("Email",array(
			"pick" => new JobWiz_messageEmailChoose(_L("Existing Message")),
			"text"	 => new JobWiz_messageEmailText(_L("Compose Email")),
			"translate" => new JobWiz_messageEmailTranslate(_L("Translations")),
			"attachment" => new JobWiz_messageEmailAttachment(_L("Attachment"))
		)),
		"sms" => new WizSection ("Txt",array(
			"pick" => new JobWiz_messageSmsChoose(_L("Existing Message")),
			"text" => new JobWiz_messageSmsText(_L("Compose Txt"))
		))
	)),
	"schedule" => new WizSection ("Schedule",array(
		"options" => new JobWiz_scheduleOptions(_L("Schedule Options")),
		"date" => new JobWiz_scheduleDate(_L("Schedule Date")),
		"template" => new JobWiz_scheduleTemplate(_L("Template"))
	)),
	"submit" => new WizSection ("Confirm",array(
		"test" => new JobWiz_submitTest(_L("Test Notification")),
		"confirm" => new JobWiz_submitConfirm(_L("Review and Confirm"))
	))
);

$wizard = new Wizard("wizard_job",$wizdata, "???");
$wizard->handlerequest();

if ($wizard->isDone()) {
	exit("All Done!");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = _L("notifications").":"._L("jobs");
$TITLE = _L('Job Wizard');


require_once("nav.inc.php");

?>
<script>
<? Validator::load_validators(array("ValInArray","ValHasMessage","ValContactListMethod","ValCallMePhone","ValEasycall","ValLists"));// Included in jobwizard.inc.php ?>
</script>
<?

startWindow($wizard->getStepData()->title);
echo $wizard->render();
endWindow();
if (true) {
	startWindow("Wizard Data");
	var_dump($_SESSION['wizard_job']['data']);
	var_dump($_SESSION['wizard_job']['step']);
	//var_dump($_SERVER);
	endWindow();
}
require_once("navbottom.inc.php");

?>
