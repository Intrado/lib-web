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
require_once("obj/FormSelectMessage.fi.php");
require_once("obj/Job.obj.php");
require_once("obj/JobLanguage.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/Job.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessageAttachment.obj.php");
// Job step form data
require_once("jobwizard.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

$wizdata = array(
	"start" => new JobWiz_start(_L("Job Type")),
	"list" => new JobWiz_listChoose(_L("List")),
	"message" => new WizSection("Message",array(
		"pick" => new JobWiz_messageType(_L("Delivery Methods")),
		"select" => new JobWiz_messageSelect(_L("Message Source")),
		"phone"	=> new WizSection ("Phone",array(
			"pick" => new JobWiz_messagePhoneChoose(_L("Phone: Message")),
			"text" =>	new JobWiz_messagePhoneText(_L("Text-to-speech")),
			"translate" => new JobWiz_messagePhoneTranslate(_L("Translations")),
			"callme" => new JobWiz_messagePhoneCallMe(_L("Record"))
		)),
		"email"	=> new WizSection ("Email",array(
			"pick" => new JobWiz_messageEmailChoose(_L("Email: Message")),
			"text"	 => new JobWiz_messageEmailText(_L("Compose Email")),
			"translate" => new JobWiz_messageEmailTranslate(_L("Translations"))
		)),
		"sms" => new WizSection ("Txt",array(
			"pick" => new JobWiz_messageSmsChoose(_L("Txt: Message")),
			"text" => new JobWiz_messageSmsText(_L("Compose Txt"))
		))
	)),
	"schedule" => new WizSection ("Schedule",array(
		"options" => new JobWiz_scheduleOptions(_L("Schedule Options")),
		"date" => new JobWiz_scheduleDate(_L("Schedule Date/Time"))
	)),
	"submit" => new WizSection ("Confirm",array(
		"confirm" => new JobWiz_submitConfirm(_L("Review and Confirm"))
	))
);

class FinishJobWizard extends WizFinish {
	
	function finish ($postdata) {
		global $USER;
		error_log("SAVING WIZARD DATA");
		
		$jobsettings = $_SESSION['confirmedJobWizard'];
		unset($_SESSION['confirmedJobWizard']);
		$schedule = $jobsettings['schedule'];
		
		//Query("BEGIN");
		$job = Job::jobWithDefaults();
		
		// Attach lists
		$listids = $jobsettings['lists'];
		$job->listid = array_shift($listids);
		if ($listids)
			foreach ($listids as $listid)
				QuickUpdate("insert into joblist (jobid,listid) values (?,?)", false, array($job->id, $listid));
		
		$job->userid = $USER->id;
		$job->jobtypeid = $jobsettings['jobtype'];
		$job->name = $jobsettings['jobname'];
		$job->description = "";
		
		$jobtypes = array();
		foreach (array("phone","email","sms","print") as $type)
			if (isset($jobsettings[$type]))
				$jobtypes[] = $type;
		
		$job->type = implode(",",$jobtypes);
		$job->modifydate = QuickQuery("select now()");
		$job->createdate = QuickQuery("select now()");
		$job->scheduleid = null;
		$job->startdate = date("Y-m-d", strtotime($schedule['date']));
		$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($schedule['days'] - 1) * 86400));
		$job->starttime = date("H:i", strtotime($schedule['callearly']));
		$job->endtime = date("H:i", strtotime($schedule['calllate']));
		$job->finishdate = null;
		$job->status = "new";
		$job->create();
		
		
		foreach (array("phone","email","sms","print") as $type){
			if ($jobsettings[$type]) {
				// there is a message for this type
				$messages = $jobsettings[$type];
				foreach ($messages as $title => $message) {
					$lang = isset($message['language'])?$message['language']:"english";
					if (!$message['id']) {
						// create a new message
						$voiceid = null;
						if ($type == 'phone') {
							$voiceid = QuickQuery("select id from ttsvoice where language=? and gender=?",false,array($lang,$message["gender"]));
							if($voiceid === false)
								$voiceid = 1; // default to english
						}
						$newmessage = new Message();
						$newmessage->type = $type;
						$newmessage->name = ($jobsettings["jobname"]);
						$newmessage->description = "";
						$newmessage->userid = $USER->id;
						$newmessage->modifydate = QuickQuery("select now()");
						$newmessage->deleted = 1;
						if ($type == 'email') {
							$newmessage->subject = $message["subject"];
							$newmessage->fromname = ($message["fromname"])?$message["fromname"]:$USER->firstname." ".$USER->lastname;
							$newmessage->fromemail = ($message["from"]);
						}
						$newmessage->stuffHeaders();
						$newmessage->create();
						
						$parts = $newmessage->parse($message["text"]);
						foreach ($parts as $part) {
							$part->voiceid = $voiceid;
							$part->messageid = $newmessage->id;
							$part->create();
						}
						
						// TODO: need correct attachment values.
						if (isset($message['attachments']) && $message['attachments']) {
							foreach ($message['attachments'] as $attachment) {
								$msgattachment = new MessageAttachment();
								$msgattachment->messageid = $newmessage->id;
								$msgattachment->contentid = $attachment;
								$msgattachment->filename = "filename";
								$msgattachment->size = 0;
								$msgattachment->deleted = 1;
								$msgattachment->create();
							}
						}
						$messageid = $newmessage->id;
					} else {
						$messageid = $message['id'];
					}
					error_log("message $type, with id ".$messageid.", for jobid ".$job->id);
					
					$joblang = new JobLanguage();
					$joblang->jobid = $job->id;
					$joblang->messageid = $messageid;
					$joblang->type = $type;
					$joblang->language = ucfirst($lang);
					$joblang->translationeditlock = isset($message['override'])?$message['override']:0;
					$joblang->create();
					
					// TODO: english is currently set to default everywhere. This needs to be a customer setting or something
					$typemessageid = $type."messageid";
					if ($message['language'] == 'english')
						$job->$typemessageid = $messageid;
				}
			}
		}
		
		$job->setSetting('translationexpire', date("Y-m-d", strtotime("+15 days"))); // now plus 15 days
		$job->update();
		if ($schedule['date'])
			$job->runNow();
		
		//Query("COMMIT");
		
	}
	
	function getFinishPage ($postdata) {
		return "<h1>Job submitted</h1>";

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

startWindow("");
echo $wizard->render();
endWindow();
if (0) {
	startWindow("Wizard Data");
	echo "<pre>";
	var_dump($_SESSION['wizard_job']);
	//var_dump($_SERVER);
	//var_dump($_SESSION['confirmedJobWizard']);
	echo "</pre>";
	endWindow();
}
require_once("navbottom.inc.php");

?>
