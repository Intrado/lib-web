<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/securityhelper.inc.php");
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
require_once("inc/translate.inc.php");
require_once("obj/traslationitem.obj.php");
require_once("obj/EmailAttach.fi.php");
require_once("obj/EmailAttach.val.php");
require_once("obj/Voice.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/FieldMap.obj.php");
// Job step form data
require_once("jobwizard.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

$wizdata = array(
	"start" => new JobWiz_start(_L("Welcome")),
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
			"translate" => new JobWiz_messageEmailTranslate(_L("Translations"))
		)),
		"sms" => new WizSection ("Txt",array(
			"pick" => new JobWiz_messageSmsChoose(_L("Existing Message")),
			"text" => new JobWiz_messageSmsText(_L("Compose Txt"))
		))
	)),
	"schedule" => new WizSection ("Schedule",array(
		"options" => new JobWiz_scheduleOptions(_L("Schedule Options")),
		"date" => new JobWiz_scheduleDate(_L("Schedule Date/Time")),
		"template" => new JobWiz_scheduleTemplate(_L("Template"))
	)),
	"submit" => new WizSection ("Confirm",array(
		"test" => new JobWiz_submitTest(_L("Test Notification")),
		"confirm" => new JobWiz_submitConfirm(_L("Review and Confirm"))
	))
);

class FinishJobWizard extends WizFinish {
	
	function finish ($postdata) {
		//TODO save data
		error_log("SAVING WIZARD DATA");
	}
	
	function getFinishPage ($postdata) {
		return "<h1>This is the finish page!</h1>";
	}
}

$wizard = new Wizard("wizard_job",$wizdata, new FinishJobWizard("Finish"));
$wizard->doneurl = "start.php";
$wizard->handlerequest();

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = _L("notifications").":"._L("jobs");
$TITLE = _L('Job Wizard');


require_once("nav.inc.php");

?>
<script type="text/javascript">	
<? Validator::load_validators(array("ValInArray","ValHasMessage","ValTextAreaPhone","ValEasycall","ValLists","ValTranslation","ValEmailAttach"));// Included in jobwizard.inc.php ?>
</script>
<?

startWindow($wizard->getStepData()->title);
echo $wizard->render();
endWindow();
if (true) {
	startWindow("Wizard Data");
	echo "<pre>";
	var_dump($_SESSION['wizard_job']);
	//var_dump($_SERVER);
	echo "</pre>";
	endWindow();
}
require_once("navbottom.inc.php");

?>
