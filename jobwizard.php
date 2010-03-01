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
require_once("obj/SectionWidget.fi.php");
require_once("obj/Job.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/Job.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/Content.obj.php");
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/auth.inc.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessageGroupSelectMenu.fi.php");
require_once("obj/ValLists.val.php");
require_once("obj/ValTimeWindowCallEarly.val.php");
require_once("obj/ValTimeWindowCallLate.val.php");

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
		"options" => new JobWiz_messageOptions(_L("Message Options")),
		"pick" => new JobWiz_messageType(_L("Delivery Methods")),
		"select" => new JobWiz_messageSelect(_L("Message Source")),
		"pickmessage" => new JobWiz_messageGroupChoose(_L("Message")),
		"phone" => new WizSection ("Phone",array(
			//"pick" => new JobWiz_messagePhoneChoose(_L("Phone: Message")),
			"text" => new JobWiz_messagePhoneText(_L("Text-to-speech")),
			"translate" => new JobWiz_messagePhoneTranslate(_L("Translations")),
			"callme" => new JobWiz_messagePhoneEasyCall(_L("Record"))
		)),
		"email"	=> new WizSection ("Email",array(
			"text" => new JobWiz_messageEmailText(_L("Compose Email")),
			"translate" => new JobWiz_messageEmailTranslate(_L("Translations"))
		)),
		"sms" => new WizSection ("SMS",array(
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

function getSmsMessageLinkText() {
	// Redialer will append the URL with link code, and if has callback the inbound number, HELP 4 info.
	return getSystemSetting("smscustomername", "SchoolMessenger") . " sent a msg. ";
}

class FinishJobWizard extends WizFinish {
	function phoneRecordedMessage($msgdata) {
		$retval = array();
		foreach ($msgdata as $lang => $data) {
			if ($data)
				$retval[$lang] = array(
					"id" => $data,
					"text" => "",
					"gender" => "",
					"language" => ($lang == "Default")?"en":strtolower($lang),
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
			"language" => 'en',
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
					"language" => ($lang == "Default")?"en":strtolower($lang),
					"override" => false
				);
		}
		return $retval;
	}

	function emailTextMessage($msgdata) {
		return array("Default" => array(
			"id" => "",
			"fromname" => $msgdata["fromname"],
			"from" => $msgdata["from"],
			"subject" => $msgdata["subject"],
			"attachments" => json_decode($msgdata["attachments"]),
			"text" => $msgdata["message"],
			"language" => "en",
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
					"fromname" => $msgdata["fromname"],
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
						"language" => "en",
						"override" => false
					));
					if (getSystemSetting("_hascallback"))
						$emailMsg["Default"]["text"] .= "You may also listen to this message over the phone by dialing the automated notification system at: ". Phone::format(getSystemSetting("inboundnumber")). "\n\n";
					$emailMsg["Default"]["text"] .= "DO NOT REPLY: This is an automatically generated email. Please do not send a reply message.";
				}
				if ($wizHasSmsMsg) {

					$smsmessagelink = true;
					$smsMsg = array("Default" => array(
						"id" => false,
						"text" => getSmsMessageLinkText(),
						"language" => "en"
					));
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
						"language" => "en"
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
						"language" => "en"
					));
				}
				break;
			//Custom
			case "custom":
				if ($wizHasPhoneMsg) {
					if(isset($postdata["/message/select"])) {
						switch ($postdata["/message/select"]["phone"]) {
							case "record":
								$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
								break;
							case "text":
								$phoneMsg = $this->phoneTextMessage(json_decode($postdata["/message/phone/text"]["message"]));
								if (isset($postdata["/message/phone/text"]["translate"]) && $postdata["/message/phone/text"]["translate"])
									$phoneMsg = array_merge($phoneMsg, $this->phoneTextTranslation($postdata["/message/phone/translate"]));
								break;
							//case "pick":
							//	$phoneMsg = $this->phoneRecordedMessage($postdata["/message/phone/pick"]);
							//	break;
							default:
								error_log($postdata["/message/select"]["phone"] . " is an unknown value for ['/message/select']['phone']");
						}
					} else {
						$phoneMsg = $this->phoneTextMessage(json_decode($postdata["/message/phone/text"]["message"]));
						if (isset($postdata["/message/phone/text"]["translate"]) && $postdata["/message/phone/text"]["translate"])
							$phoneMsg = array_merge($phoneMsg, $this->phoneTextTranslation($postdata["/message/phone/translate"]));
					}
				}
				if ($wizHasEmailMsg) {
					if(isset($postdata["/message/select"])) {
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
									"language" => "en",
									"override" => false
								));
								if (getSystemSetting("_hascallback"))
									$emailMsg["Default"]["text"] .= "You may also listen to this message over the phone by dialing the automated notification system at: ". Phone::format(getSystemSetting("inboundnumber")). "\n\n";
								$emailMsg["Default"]["text"] .= "DO NOT REPLY: This is an automatically generated email. Please do not send a reply message.";
								break;
							case "text":
								$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
								if (isset($postdata["/message/email/text"]["translate"]) && $postdata["/message/email/text"]["translate"])
									$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
								break;
							//case "pick":
							//	$emailMsg = $this->emailSavedMessage($postdata["/message/email/pick"]);
							//	break;
							default:
								error_log($postdata["/message/select"]["email"] . " is an unknown value for ['/message/select']['email']");
						}
					} else {
						$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
						if (isset($postdata["/message/email/text"]["translate"]) && $postdata["/message/email/text"]["translate"])
							$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
					}
				}
				if ($wizHasSmsMsg) {
					if(isset($postdata["/message/select"])) {
						switch ($postdata["/message/select"]["sms"]) {
							case "record":
								$smsmessagelink = true;
								$smsMsg = array("Default" => array(
									"id" => false,
									"text" => getSmsMessageLinkText(),
									"language" => "en"
								));
								break;
							case "text":
								$smsMsg = array("Default" => array(
									"id" => false,
									"text" => $postdata["/message/sms/text"]["message"],
									"language" => "en"
								));
								break;
							//case "pick":
							//	$smsMsg = array("Default" => array(
							//		"id" => $postdata["/message/sms/pick"]["message"],
							//		"text" => false,
							//		"language" => "english"
							//	));
							//	break;
							default:
								error_log($postdata["/message/select"]["sms"] . " is an unknown value for ['/message/select']['sms']");
						}
					} else {
						$smsMsg = array("Default" => array(
							"id" => false,
							"text" => $postdata["/message/sms/text"]["message"],
							"language" => "english"
						));
					}
				}
				break;

			default:
				error_log($postdata["/start"]["package"] . "is an unknown value for 'package'");
		}

		$schedule = getSchedule($postdata);

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
			"Messagegroup" => "",          // Add this 
			"phone" => $phoneMsg,
			"email" => $emailMsg,
			"sms" => $smsMsg,
			"print" => array(),
			"emailmessagelink" => $emailmessagelink,
			"smsmessagelink" => $smsmessagelink
		);
		// Remove temporary 'addme' token from listids.
		if (($i = array_search('addme', $jobsettings['lists'])) !== false)
			unset($jobsettings['lists'][$i]);

		Query("BEGIN");

		if ($postdata["/list"]["addme"]) {
			// NOTE: getUserJobTypes() automatically applies user jobType restrictions
			$jobTypes = JobType::getUserJobTypes(false);
			$addmelist = new PeopleList(null);
			$addmelist->userid = $USER->id;
			$addmelist->name = _L("Me");
			$addmelist->description = _L("JobWizard, addme");
			$addmelist->deleted = 1;
			$addmelist->update();
			if ($addmelist->id) {
				// Constants
				$langfield = FieldMap::getLanguageField();
				$fnamefield = FieldMap::getFirstNameField();
				$lnamefield = FieldMap::getLastNameField();

				// New Person
				$person = new Person();
				$person->userid = $USER->id;
				$person->deleted = 0; // NOTE: This person must not be set as deleted, otherwise the list will not include him.
				$person->type = "manualadd";
				$person->$fnamefield = $USER->firstname;
				$person->$lnamefield = $USER->lastname;
				$person->$langfield = "en";
				$person->update();

				// New Phone, Email, SMS
				$deliveryTypes = array();
				if (isset($postdata["/list"]["addmePhone"])) {
					$deliveryTypes["phone"] = new Phone();
					$deliveryTypes["phone"]->phone = Phone::parse($postdata["/list"]["addmePhone"]);
				}
				if (isset($postdata["/list"]["addmeEmail"])) {
					$deliveryTypes["email"] = new Email();
					$deliveryTypes["email"]->email = trim($postdata["/list"]["addmeEmail"]);
				}
				if ($wizHasSmsMsg && isset($postdata["/list"]["addmeSms"])) {
					$deliveryTypes["sms"] = new Sms();
					$deliveryTypes["sms"]->sms = Phone::parse($postdata["/list"]["addmeSms"]);

				}

				// Delivery Types and Job Types
				foreach ($deliveryTypes as $deliveryTypeName => $deliveryTypeObject) {
					$deliveryTypeObject->personid = $person->id;
					$deliveryTypeObject->sequence = 0;
					$deliveryTypeObject->editlock = 0;
					$deliveryTypeObject->update();

					// For each job type, assume sequence = 0, enabled = 1
					foreach ($jobTypes as $jobType) {
						// NOTE: $person->id is assumed to be a new id, so no need to worry about duplicate keys
						$query = "insert into contactpref (personid, jobTypeid, type, sequence, enabled) values (?, ?, ?, 0, 1)";
						QuickUpdate($query, false, array($person->id, $jobType->id, $deliveryTypeName));
					}
				}

				// New List Entry
				$le = new ListEntry();
				$le->type = "add";
				$le->listid = $addmelist->id;
				$le->personid = $person->id;
				$le->create();

				// Include this single-person list in the job.
				$jobsettings["lists"][] = $addmelist->id;
			}
		}

		$job = Job::jobWithDefaults();

		$job->userid = $USER->id;
		$job->jobtypeid = $jobsettings['jobtype'];
		$job->name = $jobsettings['jobname'];
		$job->description = "";

		$job->type = 'notification';
		$job->modifydate = $job->createdate = date("Y-m-d H:i:s", time());
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

		$messagegroupid = null;
		if (wizHasMessageGroup($postdata)) {
			$messagegroupid = $postdata["/message/pickmessage"]["messagegroup"];
		} else {
			$messagegroup = new MessageGroup();
			$messagegroup->userid = $USER->id;
			$messagegroup->name = $jobsettings["jobname"];
			$messagegroup->description = "";
			$messagegroup->modified = $job->modifydate;
			$messagegroup->deleted = 1;
			$messagegroup->create();
			$messagegroupid = $messagegroup->id;
			foreach (array("phone","email","sms") as $type){
				if ($jobsettings[$type]) {
					// there is a message for this type
					$messages = $jobsettings[$type]; // There may be many messages for each type
					foreach ($messages as $title => $message) {
						$lang = isset($message['language'])?$message['language']:"en";
						if (!$message['id']) {
							// create a new message
							$voiceid = null;
							if ($type == 'phone') {
								$voiceid = QuickQuery("select id from ttsvoice where languagecode=? and gender=?",false,array($lang,$message["gender"]));
								if($voiceid === false)
									$voiceid = 1; // default to english
							}
							$newmessage = new Message();
							$newmessage->messagegroupid = $messagegroup->id;
							$newmessage->type = $type;
							switch($type) {
								case 'phone':
									$newmessage->subtype = 'voice';
									break;
								case 'email':
									$newmessage->subtype = 'plain';  // TODO: Add HTML support to wizard
									break;
								default:
									$newmessage->subtype = 'plain';
									break;
							}

							$newmessage->autotranslate = 'none';

							$newmessage->name = ($jobsettings["jobname"]);
							$newmessage->description = "";
							$newmessage->userid = $USER->id;
							$newmessage->modifydate = $messagegroup->modified;//QuickQuery("select now()");
							$newmessage->languagecode = $lang;
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
							//$messageid = $newmessage->id;
						} else {
							//$messageid = $message['id'];
						}
					}
				}
			}


		}
		$job->messagegroupid = $messagegroupid;//$messageid;
		//TODO
		//When the user picks an existing message the messageid is a message group
		//but when the content is created on the fly.....

		// store job lists
		$listids = $jobsettings['lists'];
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

		// set jobsetting 'callerid'
		if (getSystemSetting('_hascallback', false)) {
			// blank callerid is fine, save this setting and default will be looked up by job processor when job starts
			$callerid = '';
		} else {
			if ($USER->authorize('setcallerid') && (isset($postdata["/schedule/advanced"]['callerid'])))
				$callerid = Phone::parse($postdata["/schedule/advanced"]['callerid']);
			else
				$callerid = getDefaultCallerID();
		}
		$job->setSetting('callerid', $callerid);

		$job->update();
		if ($schedule['date'])
			$job->runNow();

		$_SESSION['wizard_job']['submitted_jobid'] = $job->id;

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
		$html .= '<div id="embedjobmonitor"></div>';
		$html .= "
			<script type='text/javascript'>
				new Ajax.Updater('embedjobmonitor', 'jobmonitor.php', {
					evalScripts: true,
					method: 'get',
					parameters: {
						notpopup: true,
						jobid: {$_SESSION['wizard_job']['submitted_jobid']}
					}
				});
			</script>
		";
		//if ($postdata["/schedule/options"]["schedule"] == "template")
		//	$html .= '<script>window.location="start.php";</script>';
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
<? Validator::load_validators(array("ValInArray", "ValJobName", "ValHasMessage", "ValTextAreaPhone","ValEasycall","ValLists","ValTranslation","ValEmailAttach", "ValTimeWindowCallLate", "ValTimeWindowCallEarly","ValRegExp", "valPhone","ValMessageTranslationExpiration"));// Included in jobwizard.inc.php ?>
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
