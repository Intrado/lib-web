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

////////////////////////////////////////////////////////////////////////////////
// Passed parameter checking
////////////////////////////////////////////////////////////////////////////////
// currently valid jobtypes that can be passed are 'emergency' and 'normal'
if (isset($_GET['jobtype']))
	$_SESSION['wizard_job_type'] = $_GET['jobtype'];

if (isset($_GET['debug']))
	$_SESSION['wizard_job']['debug'] = true;

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
		"sms" => new WizSection ("SMS",array(
			"pick" => new JobWiz_messageSmsChoose(_L("SMS: SMS Text")),
			"text" => new JobWiz_messageSmsText(_L("SMS Text"))
		))
	)),
	"schedule" => new WizSection ("Schedule",array(
		"options" => new JobWiz_scheduleOptions(_L("Schedule Options")),
		"date" => new JobWiz_scheduleDate(_L("Schedule Date/Time")),
		"advanced" => new JobWiz_scheduleAdvanced(_L("Advanced Options"))
	)),
	"submit" => new WizSection ("Confirm",array(
		"confirm" => new JobWiz_submitConfirm(_L("Review and Confirm"))
	))
);

class FinishJobWizard extends WizFinish {
	function phoneRecordedMessage($msgdata) {
		$retval = array();
		foreach ($msgdata as $lang => $data) {
			if ($data)
				$retval[$lang] = array(
					"id" => $data,
					"text" => "",
					"gender" => "",
					"language" => ($lang == "Default")?"english":strtolower($lang),
					"override" => false
				);
		}
		return $retval;
	}
	
	function phoneTextMessage($msgdata) {
		return array("Default" => array(
			"id" => "",
			"text" => $msgdata->text,
			"gender" => $msgdata->gender,
			"language" => 'english',
			"override" => false
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
		foreach ($msgdata as $lang => $data) {
			if ($data)
				$retval[$lang] = array(
					"id" => $data,
					"from" => "",
					"fromname" => "",
					"subject" => "",
					"attachments" => "",
					"text" => "",
					"language" => ($lang == "Default")?"english":strtolower($lang),
					"override" => false
				);
		}
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
			"override" => false
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
		// If the job has not ben confirmed, don't try to process the data.
		if (!isset($postdata["/submit/confirm"]["jobconfirm"])  || !$postdata["/submit/confirm"]["jobconfirm"] )
			return false;
		
		$wizHasPhoneMsg = wizHasPhone($postdata);
		$wizHasEmailMsg= wizHasEmail($postdata);
		$wizHasSmsMsg= wizHasSms($postdata);
		
		global $USER;
		global $ACCESS;
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
				if ($wizHasPhoneMsg)
					$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
				if ($wizHasEmailMsg) {
					$emailmessagelink = true;
					$emailMsg = array("Default" => array(
						"id" => "",
						"from" => "contactme@schoolmessenger.com",
						"fromname" => "SchoolMessenger",
						"subject" => "Message from ". $_SESSION['custname'],
						"attachments" => array(),
						"text" => "An important telephone notification was sent to you by ". $_SESSION['custname']. ". Click the link below or copy and paste the link into your web browser to hear the message.\n\n",
						"language" => "english",
						"override" => false
					));
					if (getSystemSetting("_hascallback"))
						$emailMsg["Default"]["text"] .= "You may also listen to this message over the phone by dialing the automated notification system at: ". Phone::format(getSystemSetting("inboundnumber")). "\n\n";
					$emailMsg["Default"]["text"] .= "DO NOT REPLY: This is an automatically generated email. Please do not send a reply message.\nTo be removed from these alerts please contact ". $_SESSION['custname']. ".\n";
				}
				if ($wizHasSmsMsg) {
					$smsmessagelink = true;
					$smsMsg = array("Default" => array(
						"id" => false,
						"text" => "Phone message from ". $_SESSION['custname']. "\n",
						"language" => "english"
					));
					if (getSystemSetting("_hascallback"))
						$smsMsg["Default"]["text"] .= "To listen dial ". Phone::format(getSystemSetting("inboundnumber")). "\n\n";
				}
				break;
			//Express Text
			case "express":
				if ($wizHasPhoneMsg) {
					$phoneMsg = $this->phoneTextMessage(json_decode($postdata["/message/phone/text"]["message"]));
					if (isset($postdata["/message/phone/text"]["translate"]) && $postdata["/message/phone/text"]["translate"])
						$phoneMsg = array_merge($phoneMsg, $this->phoneTextTranslation($postdata["/message/phone/translate"]));
				}
				if ($wizHasEmailMsg) {
					$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
					if (isset($postdata["/message/email/text"]["translate"]) && $postdata["/message/email/text"]["translate"])
						$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
				}
				if ($wizHasSmsMsg) {
					$smsMsg = array("Default" => array(
						"id" => false,
						"text" => $postdata["/message/sms/text"]["message"],
						"language" => "english"
					));
				}
				break;
			//Personalized
			case "personalized":
				if ($wizHasPhoneMsg)
					$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
				if ($wizHasEmailMsg) {
					$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
					if (isset($postdata["/message/email/text"]["translate"]) && $postdata["/message/email/text"]["translate"])
						$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
				}
				if ($wizHasSmsMsg) {
					$smsMsg = array("Default" => array(
						"id" => false,
						"text" => $postdata["/message/sms/text"]["message"],
						"language" => "english"
					));
				}
				break;
			//Custom
			case "custom":
				if ($wizHasPhoneMsg) {
					switch ($postdata["/message/select"]["phone"]) {
						case "record":
							$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
							break;
						case "text":
							if ($postdata["/message/select"]["phone"] == "text") {
								$phoneMsg = $this->phoneTextMessage(json_decode($postdata["/message/phone/text"]["message"]));
								if (isset($postdata["/message/phone/text"]["translate"]) && $postdata["/message/phone/text"]["translate"])
									$phoneMsg = array_merge($phoneMsg, $this->phoneTextTranslation($postdata["/message/phone/translate"]));
							}
							break;
						case "pick":
							$phoneMsg = $this->phoneRecordedMessage($postdata["/message/phone/pick"]);
							break;
						default:
							error_log($postdata["/message/select"]["phone"] . " is an unknown value for ['/message/select']['phone']");
					}
				}
				if ($wizHasEmailMsg) {
					switch ($postdata["/message/select"]["email"]) {
						case "record":
							$emailmessagelink = true;
							$emailMsg = array("Default" => array(
								"id" => "",
								"from" => "contactme@schoolmessenger.com",
								"fromname" => "SchoolMessenger",
								"subject" => "Message from ". $_SESSION['custname'],
								"attachments" => array(),
								"text" => "An important telephone notification was sent to you by ". $_SESSION['custname']. ". Click the link below or copy and paste the link into your web browser to hear the message.\n\n",
								"language" => "english",
								"override" => false
							));
							if (getSystemSetting("_hascallback"))
								$emailMsg["Default"]["text"] .= "You may also listen to this message over the phone by dialing the automated notification system at: ". Phone::format(getSystemSetting("inboundnumber")). "\n\n";
							$emailMsg["Default"]["text"] .= "DO NOT REPLY: This is an automatically generated email. Please do not send a reply message.\nTo be removed from these alerts please contact ". $_SESSION['custname']. ".\n";
							break;
						case "text":
							$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
							if (isset($postdata["/message/email/text"]["translate"]) && $postdata["/message/email/text"]["translate"])
								$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
							break;
						case "pick":
							$emailMsg = $this->emailSavedMessage($postdata["/message/email/pick"]);
							break;
						default:
							error_log($postdata["/message/select"]["email"] . " is an unknown value for ['/message/select']['email']");
					}
				}
				if ($wizHasSmsMsg) {
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
				$accessCallearly = $ACCESS->getValue("callearly");
				if (!$accessCallearly)
					$accessCallearly = "12:00 am";
				$calllate = $USER->getCallLate();
				if ((strtotime($callearly) + 3600) > strtotime($calllate))
					$calllate = date("g:i a", strtotime($callearly) + 3600);
				$accessCalllate = $ACCESS->getValue("calllate");
				if (!$accessCalllate)
					$accessCalllate = "11:59 pm";
				if (strtotime($calllate)  > strtotime($accessCalllate))
					$calllate = $accessCalllate;
				
				$schedule = array(
					"maxjobdays" => isset($postdata["/schedule/advanced"]["maxjobdays"])?$postdata["/schedule/advanced"]["maxjobdays"]:1,
					"date" => date('m/d/Y'),
					"callearly" => $callearly,
					"calllate" => $calllate
				);
				break;
			case "schedule":
				$schedule = array(
					"maxjobdays" => isset($postdata["/schedule/advanced"]["maxjobdays"])?$postdata["/schedule/advanced"]["maxjobdays"]:1,
					"date" => date('m/d/Y', strtotime($postdata["/schedule/date"]["date"])),
					"callearly" => $postdata["/schedule/date"]["callearly"],
					"calllate" => $postdata["/schedule/date"]["calllate"]
				);
				break;
			case "template": 
				$schedule = array(
					"maxjobdays" => isset($postdata["/schedule/advanced"]["maxjobdays"])?$postdata["/schedule/advanced"]["maxjobdays"]:1,
					"date" => false,
					"callearly" => false,
					"calllate" => false
				);
				break;
			default:
				break;
		}
		
		// for all the job settings on the "Advanced" step. set some advanced options that will get stuffed into the job
		$advanced = array();
		if (isset($postdata["/schedule/options"]["advanced"]) && $postdata["/schedule/options"]["advanced"])
			foreach (array("skipduplicates", "skipemailduplicates", "leavemessage", "messageconfirmation") as $option)
				if (isset($postdata["/schedule/advanced"][$option]))
					$advanced[$option] = $postdata["/schedule/advanced"][$option];
		
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
		if ($schedule['date'])
			$job->startdate = date("Y-m-d", strtotime($schedule['date']));
		else
			$job->startdate = date("Y-m-d");
		$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($schedule["maxjobdays"] - 1) * 86400));
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
						
						/* This chunk parses the message contents and stuffs it in message parts. It is commented out and replaced by the next code that just stuffs all the text into one message part.
						$parts = $newmessage->parse($message["text"]);
						foreach ($parts as $part) {
							$part->voiceid = $voiceid;
							$part->messageid = $newmessage->id;
							$part->create();
						}*/
						$part = new MessagePart();
						$part->messageid = $newmessage->id;
						$part->type = "T";
						$part->txt = $message["text"];
						$part->voiceid = $voiceid;
						$part->sequence = 0;
						$part->create();
							
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
		
		// Attach lists
		$listids = $jobsettings['lists'];
		$job->listid = array_shift($listids);
		if ($listids)
			foreach ($listids as $listid)
				QuickUpdate("insert into joblist (jobid,listid) values (?,?)", false, array($job->id, $listid));
		
		$job->setSetting('translationexpire', date("Y-m-d", strtotime("+15 days"))); // now plus 15 days
		if ($jobsettings['emailmessagelink'])
			$job->setSetting('emailmessagelink', "1");
		
		if ($jobsettings['smsmessagelink'])
			$job->setSetting('smsmessagelink', "1");
		
		foreach ($advanced as $option => $value) {
			if ($value == true)
				$job->setSetting($option, 1);
			elseif ($value == false)
				$job->setSetting($option, 0);
			else
				$job->setSetting($option, $value);
		}
		
		// set jobsetting 'callerid' blank for jobprocessor to lookup the current default at job start
		if ($USER->authorize('setcallerid') && !getSystemSetting('_hascallback', false)) {
			$callerid = '';
			if (isset($postdata["/schedule/advanced"]['callerid'])) {
				$callerid = Phone::parse($postdata["/schedule/advanced"]['callerid']);
			}
			// blank callerid is fine, save this setting and default will be looked up by job processor when job starts
			$job->setOptionValue("callerid",$callerid);
		} else {
			$job->setOptionValue("callerid", getDefaultCallerID());
		}
		
		$job->update();
		if ($schedule['date'])
			$job->runNow();
		
		Query("COMMIT");
	}
	
	function getFinishPage ($postdata) {
		// If the job has not ben confirmed, don't send any page data
		if (!isset($postdata["/submit/confirm"]["jobconfirm"])  || !$postdata["/submit/confirm"]["jobconfirm"] )
			return '<h1>Invalid data submitted.</h1>
				<script>
					window.location="unauthorized.php";
				</script>';
				
		$html = '<h1>Success! Your notification request has been submitted.</h1>';
		//if ($postdata["/schedule/options"]["schedule"] == "template")
			$html .= '<script>window.location="start.php";</script>';
		return $html;
	}
}

$wizard = new Wizard("wizard_job",$wizdata, new FinishJobWizard("Finish"));
$wizard->doneurl = "start.php";
$wizard->handlerequest();

// After reload check session data for job type information
if (isset($_SESSION['wizard_job_type'])) {
	$_SESSION['wizard_job']['jobtype'] = $_SESSION['wizard_job_type'];
	unset($_SESSION['wizard_job_type']);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = _L("notifications").":"._L("jobs");
$TITLE = false;

require_once("nav.inc.php");

?>
<script type="text/javascript">	
<? Validator::load_validators(array("ValInArray", "ValJobName", "ValHasMessage", "ValTextAreaPhone","ValEasycall","ValLists","ValTranslation","ValEmailAttach", "ValTimeWindowCallLate", "ValTimeWindowCallEarly", "ValDate","ValRegExp"));// Included in jobwizard.inc.php ?>
</script>
<?

startWindow("MessageSender");
echo $wizard->render();
endWindow();
if (isset($_SESSION['wizard_job']['debug']) && $_SESSION['wizard_job']['debug']) {
	startWindow("Wizard Data");
	echo "<pre>";
	var_dump($_SESSION['wizard_job']);
	echo "</pre>";
	endWindow();
}
require_once("navbottom.inc.php");

?>
