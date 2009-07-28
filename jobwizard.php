<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/date.inc.php");
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
		"phone" => new WizSection ("Phone",array(
			"pick" => new JobWiz_messagePhoneChoose(_L("Phone: Message")),
			"text" => new JobWiz_messagePhoneText(_L("Text-to-speech")),
			"translate" => new JobWiz_messagePhoneTranslate(_L("Translations")),
			"callme" => new JobWiz_messagePhoneCallMe(_L("Record"))
		)),
		"email"	=> new WizSection ("Email",array(
			"pick" => new JobWiz_messageEmailChoose(_L("Email: Message")),
			"text" => new JobWiz_messageEmailText(_L("Compose Email")),
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
	function phoneRecordedMessage($msgdata) {
		$retval = array();
		foreach ($msgdata as $lang => $data)
			$retval[$lang] = array(
				"id" => $data,
				"text" => "",
				"gender" => "",
				"language" => ($lang == "Default")?"english":strtolower($lang),
				"override" => true
			);
		return $retval;
	}
	
	function phoneTextMessage($msgdata) {
		return array("Default" => array(
			"id" => "",
			"text" => $msgdata->text,
			"gender" => $msgdata->gender,
			"language" => 'english',
			"override" => true
		));
	}
	
	function phoneTextTranslation($msgdata) {
		$retval = array();
		foreach ($msgdata as $lang => $data) {
			$newmsgdata = json_decode($data);
			if ($newmsgdata->enabled)
				$retval[$lang] = array(
					"id" => "",
					"text" => $newmsgdata->text,
					"gender" => $newmsgdata->gender,
					"language" => strtolower($lang),
					"override" => $newmsgdata->override
				);
		}
		return $retval;
	}
	
	function emailSavedMessage($msgdata) {
		$retval = array();
		foreach ($msgdata as $lang => $data)
			$retval[$lang] = array(
				"id" => $data,
				"from" => "",
				"fromname" => "",
				"subject" => "",
				"attachments" => "",
				"text" => "",
				"language" => ($lang == "Default")?"english":strtolower($lang),
				"override" => true
			);
		return $retval;
	}

	function emailTextMessage($msgdata) {
		return array("Default" => array(
			"id" => "",
			"fromname" => "",
			"from" => $msgdata["from"],
			"subject" => $msgdata["subject"],
			"attachments" => json_decode($msgdata["attachments"]),
			"text" => $msgdata["message"],
			"language" => "english",
			"override" => true
		));
	}

	function emailTextTranslation($msgdata, $translationdata) {
		$retval = array();
		foreach ($translationdata as $lang => $data) {
			$newmsgdata = json_decode($data);
			if ($newmsgdata->enabled)
				$retval[$lang] = array(
					"id" => "",
					"from" => $msgdata["from"],
					"fromname" => "",
					"subject" => $msgdata["subject"],
					"attachments" => json_decode($msgdata["attachments"]),
					"text" => $newmsgdata->text,
					"language" => strtolower($lang),
					"override" => $newmsgdata->override
				);
		}
		return $retval;
	}
	
	function finish ($postdata) {
		global $USER;
		$jobtype = DBFind("JobType", "from jobtype where id=?", false, array($postdata["/start"]["jobtype"]));
		$jobname = $postdata["/start"]["name"];

		$phoneMsg = array();
		$emailMsg = array();
		$smsMsg = array();
		$emailmessagelink = false;
		$smsmessagelink = false;
		
		switch ($postdata["/start"]["package"]) {
			//If package is Easycall
			case "easycall":
				$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
				$emailmessagelink = true;
				$emailMsg = array("Default" => array(
					"id" => "",
					"from" => "contactme@schoolmessenger.com",
					"fromname" => "SchoolMessenger",
					"subject" => $postdata["/start"]["name"],
					"attachments" => array(),
					"text" => "An important telephone notification was sent to you by ". $_SESSION['custname']. ". Click the link below or copy and paste the link into your web browser to hear the message.\n\n",
					"language" => "english",
					"override" => true
				));
				if (getSystemSetting("_hascallback"))
					$emailMsg["Default"]["text"] .= "You may also listen to this message over the phone by dialing the automated notification system at: ". Phone::format(getSystemSetting("inboundnumber")). "\n\n";
				$emailMsg["Default"]["text"] .= "DO NOT REPLY: This is an automatically generated email. Please do not send a reply message.\nTo be removed from these alerts please contact ". $_SESSION['custname']. ".\n";
				$smsmessagelink = true;
				$smsMsg = array("Default" => array(
					"id" => false,
					"text" => "Phone message from ". $_SESSION['custname']. "\n",
					"language" => "english"
				));
				if (getSystemSetting("_hascallback"))
					$smsMsg["Default"]["text"] .= "To listen dial ". Phone::format(getSystemSetting("inboundnumber")). "\n\n";
				break;
			//Express Text
			case "express":
				$phoneMsg = $this->phoneTextMessage(json_decode($postdata["/message/phone/text"]["message"]));
				if ($postdata["/message/phone/text"]["translate"] == 'true')
					$phoneMsg = array_merge($phoneMsg, $this->phoneTextTranslation($postdata["/message/phone/translate"]));
				$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
				if ($postdata["/message/email/text"]["translate"] == 'true')
					$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
				$smsMsg = array("Default" => array(
					"id" => false,
					"text" => $postdata["/message/sms/text"]["message"],
					"language" => "english"
				));
				break;
			//Personalized
			case "personalized":
				$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
				$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
				if ($postdata["/message/email/text"]["translate"] == 'true')
					$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
				$smsMsg = array("Default" => array(
					"id" => false,
					"text" => $postdata["/message/sms/text"]["message"],
					"language" => "english"
				));
				break;
			//Custom
			case "custom":
				if (in_array('phone', $postdata["/message/pick"]["type"])) {
					switch ($postdata["/message/select"]["phone"]) {
						case "record":
							$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
							break;
						case "text":
							if ($postdata["/message/select"]["phone"] == "text") {
								$phoneMsg = $this->phoneTextMessage(json_decode($postdata["/message/phone/text"]["message"]));
								if ($postdata["/message/phone/text"]["translate"] == 'true')
									$phoneMsg = array_merge($phoneMsg, $this->phoneTextTranslation($postdata["/message/phone/translate"]));
							}
							break;
						case "pick":
							$phoneMsg = $this->phoneRecordedMessage(array("Default" => $postdata["/message/phone/pick"]["message"]));
							break;
						default:
							error_log($postdata["/message/select"]["phone"] . " is an unknown value for ['/message/select']['phone']");
					}
				}
				if (in_array('email', $postdata["/message/pick"]["type"])) {
					switch ($postdata["/message/select"]["email"]) {
						case "record":
							$emailmessagelink = true;
							$emailMsg = array("Default" => array(
								"id" => "",
								"from" => "contactme@schoolmessenger.com",
								"fromname" => "SchoolMessenger",
								"subject" => $postdata["/start"]["name"],
								"attachments" => array(),
								"text" => "An important telephone notification was sent to you by ". $_SESSION['custname']. ". Click the link below or copy and paste the link into your web browser to hear the message.\n\n",
								"language" => "english",
								"override" => true
							));
							if (getSystemSetting("_hascallback"))
								$emailMsg["Default"]["text"] .= "You may also listen to this message over the phone by dialing the automated notification system at: ". Phone::format(getSystemSetting("inboundnumber")). "\n\n";
							$emailMsg["Default"]["text"] .= "DO NOT REPLY: This is an automatically generated email. Please do not send a reply message.\nTo be removed from these alerts please contact ". $_SESSION['custname']. ".\n";
							break;
						case "text":
							$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
							if ($postdata["/message/email/text"]["translate"] == 'true')
								$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
							break;
						case "pick":
							$emailMsg = $this->emailSavedMessage(array("Default" => $postdata["/message/email/pick"]["message"]));
							break;
						default:
							error_log($postdata["/message/select"]["email"] . " is an unknown value for ['/message/select']['email']");
					}
				}
				if (in_array('sms', $postdata["/message/pick"]["type"])) {
					switch ($postdata["/message/select"]["sms"]) {
						case "record":
							$smsmessagelink = true;
							$smsMsg = array("Default" => array(
								"id" => false,
								"text" => "Phone message from ". $_SESSION['custname']. "\n",
								"language" => "english"
							));
							if (getSystemSetting("_hascallback"))
								$smsMsg["Default"]["text"] .= "To listen dial ". Phone::format(getSystemSetting("inboundnumber")). "\n\n";
							break;
						case "text":
							$smsMsg = array("Default" => array(
								"id" => false,
								"text" => $postdata["/message/sms/text"]["message"],
								"language" => "english"
							));
							break;
						case "pick":
							$smsMsg = array("Default" => array(
								"id" => $postdata["/message/sms/pick"]["message"],
								"text" => false,
								"language" => "english"
							));
							break;
						default:
							error_log($postdata["/message/select"]["sms"] . " is an unknown value for ['/message/select']['sms']");
					}
				}
				break;
			
			default:
				error_log($postdata["/start"]["package"] . "is an unknown value for 'package'");
		}
		
		$schedule = array();
		switch ($postdata["/schedule/options"]["schedule"]) {
			case "now":
				$callearly = date("g:i a");
				if (strtotime($callearly) < strtotime($USER->getCallEarly()))
					$callearly = $USER->getCallEarly();
				$calllate = $USER->getCallLate();
				if (strtotime($callearly) + 3600 > strtotime($calllate))
					 $calllate = date("g:i a", strtotime(strtotime($callearly) + 3600));
				$schedule = array(
					"date" => date('m/d/Y'),
					"callearly" => date("g:i a"),
					"calllate" => $USER->getCallLate()
				);
				break;
			case "schedule":
				$schedule = array(
					"date" => date('m/d/Y', strtotime($postdata["/schedule/date"]["date"])),
					"callearly" => $postdata["/schedule/date"]["callearly"],
					"calllate" => $postdata["/schedule/date"]["calllate"]
				);
				break;
			case "template": 
				$schedule = array(
					"date" => false,
					"callearly" => false,
					"calllate" => false
				);
				break;
			default:
				break;
		}

		$jobsettings = array(
			"jobtype" => $jobtype->id,
			"jobname" => $jobname,
			"lists" => json_decode($postdata["/list"]["listids"]),
			"phone" => $phoneMsg,
			"email" => $emailMsg,
			"sms" => $smsMsg,
			"print" => array(),
			"emailmessagelink" => $emailmessagelink,
			"smsmessagelink" => $smsmessagelink
		);
		
		Query("BEGIN");
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
			if (isset($jobsettings[$type]) && $jobsettings[$type])
				$jobtypes[] = $type;
		
		$job->type = implode(",",$jobtypes);
		$job->modifydate = QuickQuery("select now()");
		$job->createdate = QuickQuery("select now()");
		$job->scheduleid = null;
		if ($schedule['date']) {
			$job->startdate = date("Y-m-d", strtotime($schedule['date']));
			$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($USER->getSetting("maxjobdays", 1) - 1) * 86400));
		} else {
			$job->startdate = date("Y-m-d");
			$job->enddate = date("Y-m-d", strtotime("today") + (1 * 86400));
		}
		$job->starttime = ($schedule['callearly'])?date("H:i", strtotime($schedule['callearly'])):date("H:i", strtotime($USER->getCallEarly()));
		$job->endtime = ($schedule['calllate'])?date("H:i", strtotime($schedule['calllate'])):date("H:i", strtotime($USER->getCallLate()));
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
						
						if (isset($message['attachments']) && $message['attachments']) {
							foreach ($message['attachments'] as $cid => $details) {
								$msgattachment = new MessageAttachment();
								$msgattachment->messageid = $newmessage->id;
								$msgattachment->contentid = $cid;
								$msgattachment->filename = $details->name;
								$msgattachment->size = $details->size;
								$msgattachment->deleted = 1;
								$msgattachment->create();
							}
						}
						$messageid = $newmessage->id;
					} else {
						$messageid = $message['id'];
					}
					
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
		if ($jobsettings['emailmessagelink'])
			$job->setSetting('emailmessagelink', "1");
		
		if ($jobsettings['smsmessagelink'])
			$job->setSetting('smsmessagelink', "1");
		
		$job->update();
		if ($schedule['date'])
			$job->runNow();
		
		Query("COMMIT");
		
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
<? Validator::load_validators(array("ValInArray", "ValJobName", "ValHasMessage", "ValTextAreaPhone","ValEasycall","ValLists","ValTranslation","ValEmailAttach", "ValTimeWindowCallLate", "ValTimeWindowCallEarly", "ValDate"));// Included in jobwizard.inc.php ?>
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
