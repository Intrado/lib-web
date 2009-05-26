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
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

// Job step form data
require("jobwizard.inc.php");

$wizdata = array(
	"basic" => new JobWiz_basic(_L("New Job Wizard")),
	"list" => array(
		"pick" => new JobWiz_listChoose(_L("Select An Existing List"))
	),
	"message" => array(
		"pick" => new JobWiz_messageType(_L("Choose Destination Types")),
		"select" => new JobWiz_messageSelect(_L("Select Message Types")),
		"phone"	=> array(
			"pick" => new JobWiz_messagePhoneChoose(_L("Select A Message")),
			"text" =>	new JobWiz_messagePhoneText(_L("Type Phone Message")),
			"translate" => new JobWiz_messagePhoneTranslate(_L("View and Edit Translations")),
			"record" => new JobWiz_messagePhoneRecord(_L("Record Phone Message")),
			"callme" => new JobWiz_messagePhoneCallMe(_L("Record Phone Message"))
		),
		"email"	=> array(
			"pick" => new JobWiz_messageEmailChoose(_L("Select A Message")),
			"text"	 => new JobWiz_messageEmailText(_L("Type Email Message")),
			"translate" => new JobWiz_messageEmailTranslate(_L("View and Edit Translations")),
			"attachment" => new JobWiz_messageEmailAttachment(_L("Select File Attachment"))
		),
		"sms" => array(
			"pick" => new JobWiz_messageSmsChoose(_L("Select A Message")),
			"text" => new JobWiz_messageSmsText(_L("SMS Message"))
		)
	),
	"schedule" => array(
		"options" => new JobWiz_scheduleOptions(_L("Delivery Options")),
		"date" => new JobWiz_scheduleDate(_L("Delivery Date")),
		"template" => new JobWiz_scheduleTemplate(_L("Template Options"))
	),
	"submit" => array(
		"test" => new JobWiz_submitTest(_L("Test Notification")),
		"confirm" => new JobWiz_submitConfirm(_L("Confirm and Submit"))
	)	
);

$wizard = new Wizard("wizard_job",$wizdata);
$wizard->handlerequest();

if ($wizard->isDone()) {
	exit("All Done!");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = _L("notifications").":"._L("jobs");
$TITLE = _L('Job Wizard');

?>
<script>
<? Validator::load_validators(array("ValInArray","ValHasMessage","ValContactListMethod","ValCallMePhone","ValEasycall","ValLists"));// Included in jobwizard.inc.php ?>
</script>
<?

require_once("nav.inc.php");
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