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
require_once("obj/ValMessageBody.val.php");
require_once("obj/ValNonEmptyMessage.val.php");
require_once("inc/facebook.php");
require_once("inc/facebook.inc.php");
require_once("obj/FacebookPost.fi.php");
require_once("obj/FacebookAuth.fi.php");
require_once("obj/ValFacebookPost.val.php");
require_once("obj/TwitterAuth.fi.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");

// Job step form data
require_once("inc/jobwizard.inc.php");

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
		)),
		"facebookauth" => new JobWiz_facebookAuth(_L("Facebook Auth")),
		"twitterauth" => new JobWiz_twitterAuth(_L("Twitter Auth")),
		"socialmedia" => new JobWiz_socialMedia(_L("Social Media"))
	)),
	"schedule" => new WizSection ("Options",array(
		"options" => new JobWiz_scheduleOptions(_L("Job Options")),
		"date" => new JobWiz_scheduleDate(_L("Schedule Date/Time")),
		"advanced" => new JobWiz_scheduleAdvanced(_L("Advanced Options")),
		"savelists" => new JobWiz_scheduleSaveLists(_L("Save & Review Lists"))
	)),
	"submit" => new WizSection ("Confirm",array(
		"confirm" => new JobWiz_submitConfirm(_L("Review and Confirm"))
	))
);

class FinishJobWizard extends WizFinish {
	function finish ($postdata) {
		// If the job has not ben confirmed, don't try to process the data.
		if (!isset($postdata["/submit/confirm"]["jobconfirm"])  || !$postdata["/submit/confirm"]["jobconfirm"] )
			return false;
		
		global $USER;
		global $ACCESS;
		
		// get job options
		$jobtype = DBFind("JobType", "from jobtype where id=?", false, array($postdata["/start"]["jobtype"]));
		$jobname = $postdata["/start"]["name"];

		// get the list or lists
		$joblists = json_decode($postdata["/list"]["listids"]);
		// Remove temporary 'addme' token from listids. (not a valid listid, obviously)
		if (($i = array_search('addme', $joblists)) !== false)
			unset($joblists[$i]);

		// Start stuffing the job in the DB
		Query("BEGIN");
		
		// if there is "addme" data in the list selection, create a person and list with the contact details
		if ($postdata["/list"]["addme"]) {
			// get the contact details out of postdata
			$addme = array(
				"phone" => (isset($postdata["/list"]["addmePhone"])?Phone::parse($postdata["/list"]["addmePhone"]):""),
				"email" => (isset($postdata["/list"]["addmeEmail"])?trim($postdata["/list"]["addmeEmail"]):""),
				"sms" => (isset($postdata["/list"]["addmeSms"])?Phone::parse($postdata["/list"]["addmeSms"]):"")
			);
			// NOTE: getUserJobTypes() automatically applies user jobType restrictions
			$jobTypes = JobType::getUserJobTypes(false);
			$addmelist = new PeopleList(null);
			$addmelist->userid = $USER->id;
			$addmelist->name = _L("Me");
			$addmelist->description = _L("JobWizard, addme");
			$addmelist->deleted = 1;
			$addmelist->create();
			
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
				$person->create();

				// New Phone, Email, SMS
				$deliveryTypes = array();
				if ($addme['phone']) {
					$deliveryTypes["phone"] = new Phone();
					$deliveryTypes["phone"]->phone = $addme['phone'];
				}
				if ($addme['email']) {
					$deliveryTypes["email"] = new Email();
					$deliveryTypes["email"]->email = $addme['email'];
				}
				if ($addme['sms']) {
					$deliveryTypes["sms"] = new Sms();
					$deliveryTypes["sms"]->sms = $addme['sms'];
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
				$joblists[] = $addmelist->id;
			}
		} //end addme

		$job = Job::jobWithDefaults();

		$job->userid = $USER->id;
		$job->jobtypeid = $jobtype->id;
		$job->name = $jobname;
		$job->description = "";

		$job->type = 'notification';
		$job->modifydate = $job->createdate = date("Y-m-d H:i:s");
		
		// parse the schedule data out of postdata
		$schedule = getSchedule($postdata);
		
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

		// if this wizard has an already created message group selected. use it
		if (wizHasMessageGroup($postdata)) {
			$messagegroup = new MessageGroup($postdata["/message/pickmessage"]["messagegroup"]);
		// if not, create one
		} else {

			$messagegroup = new MessageGroup();
			$messagegroup->userid = $USER->id;
			$messagegroup->name = $jobname;
			$messagegroup->description = "Created in MessageSender";
			$messagegroup->modified = $job->modifydate;
			$messagegroup->deleted = 1;
			$messagegroup->create();
			
			// #################################################################
			// CallMe based message
			// easycall, personalized and custom (with record option selected)
			
			if (JobWiz_messagePhoneEasyCall::isEnabled($postdata, false)) {
				$audiofileidmap = json_decode($postdata["/message/phone/callme"]["message"]);
				foreach ($audiofileidmap as $langcode => $audiofileid) {
					$message = new Message();
					$message->messagegroupid = $messagegroup->id;
					$message->type = 'phone';
					$message->subtype = 'voice';
					$message->autotranslate = 'none';

					$message->name = $messagegroup->name;
					$message->description = Language::getName($langcode);
					$message->userid = $USER->id;
					$message->modifydate = $messagegroup->modified;
					$message->languagecode = $langcode;
					$message->deleted = 0;
					$message->stuffHeaders();
					$message->create();
					
					$part = new MessagePart();
					$part->messageid = $message->id;
					$part->type = "A";
					$part->audiofileid = $audiofileid;
					$part->sequence = 0;
					$part->create();
				}
			}
			
			// #################################################################
			// Text based messages
			// express, personalized and custom could have text components
			
			// keep track of the message data we are going to create messages for
			// format msgArray(typeArray(translateflagArray(data)))
			$messages = array(
				'phone' => array(),
				'email' => array(),
				'sms' => array());
			
			// phone message
			if (JobWiz_messagePhoneText::isEnabled($postdata, false)) {
				$sourcemessage = json_decode($postdata["/message/phone/text"]["message"]);
				
				// this is the default 'en' message so it's autotranslate value is 'none'
				$messages['phone']['en']['none']['text'] = $sourcemessage->text;
				$messages['phone']['en']['none']['gender'] = $sourcemessage->gender;
				
				//also set the messagegroup preferred gender
				$messagegroup->preferredgender = $sourcemessage->gender;
				$messagegroup->stuffHeaders();
				$messagegroup->update(array("data"));
				
				
				// check for and retrieve translations
				if (JobWiz_messagePhoneTranslate::isEnabled($postdata, false)) {
					foreach ($postdata["/message/phone/translate"] as $langcode => $msgdata) {
						$translatedmessage =json_decode($msgdata);
						// if this translation message is enabled
						if ($translatedmessage->enabled) {
							// if the translation text is overridden, don't attach a source message
							// it isn't applicable since we have no way to know what they changed the text to.
							if ($translatedmessage->override) {
								$messages['phone'][$langcode]['overridden']['text'] = $translatedmessage->text;
								$messages['phone'][$langcode]['overridden']['gender'] = $translatedmessage->gender;
							} else {
								$messages['phone'][$langcode]['translated']['text'] = $translatedmessage->text;
								$messages['phone'][$langcode]['translated']['gender'] = $translatedmessage->gender;
								$messages['phone'][$langcode]['source'] = $messages['phone']['en']['none'];
							}
						}
					}
				}
			}
			
			// email message
			if (JobWiz_messageEmailText::isEnabled($postdata, false)) {
				// this is the default 'en' message so it's autotranslate value is 'none'
				$messages['email']['en']['none']['text'] = $postdata["/message/email/text"]["message"];
				$messages['email']['en']['none']["fromname"] = $postdata["/message/email/text"]["fromname"];
				$messages['email']['en']['none']["from"] = $postdata["/message/email/text"]["from"];
				$messages['email']['en']['none']["subject"] = $postdata["/message/email/text"]["subject"];
				$messages['email']['en']['none']['attachments'] = json_decode($postdata["/message/email/text"]['attachments']);
				if ($messages['email']['en']['none']['attachments'] == null) $messages['email']['en']['none']['attachments'] = array();
				
				// check for and retrieve translations
				if (JobWiz_messageEmailTranslate::isEnabled($postdata, false)) {
					foreach ($postdata["/message/email/translate"] as $langcode => $enabled) {
						// emails don't have any actual translation text in session data other than the source message
						// when the message group is created. the modify date will be set in the past and retranslation will
						// get called before attaching to the job
						if ($enabled) {
							$messages['email'][$langcode]['translated'] = $messages['email']['en']['none'];
							$messages['email'][$langcode]['source'] = $messages['email']['en']['none'];
						}
					}
				}
			}
			
			// sms message
			if (JobWiz_messageSmsText::isEnabled($postdata, false))
				$messages['sms']['en']['none']['text'] = $postdata["/message/sms/text"]["message"];
			
			// #################################################################
			// create a message for each one
			
			// for each message type
			foreach ($messages as $type => $msgdata) {
				// for each language code
				foreach ($msgdata as $langcode => $autotranslatevalues) {
					// for each autotranslate value
					foreach ($autotranslatevalues as $autotranslate => $data) {
						$message = new Message();
						$message->messagegroupid = $messagegroup->id;
						$message->type = $type;
						switch($type) {
							case 'phone':
								$message->subtype = 'voice';
								break;
							case 'email':
								$message->subtype = 'html';
								break;
							default:
								$message->subtype = 'plain';
								break;
						}
						
						$message->autotranslate = $autotranslate;
						$message->name = $messagegroup->name;
						$message->description = Language::getName($langcode);
						$message->userid = $USER->id;
						
						// if this is an autotranslated message and an email. set the modify date in the past
						// this way re-translate will populate the message parts for us
						if ($autotranslate == 'translated' && $type == 'email')
							$message->modifydate = date("Y-m-d H:i:s", '1');
						else
							$message->modifydate = $messagegroup->modified;
						
						$message->languagecode = $langcode;
						$message->deleted = 0;
						
						if ($type == 'email') {
							$message->subject = $data["subject"];
							$message->fromname = $data["fromname"];
							$message->fromemail = $data["from"];
						}
						
						$message->stuffHeaders();
						$message->create();
						
						// create the message parts
						$message->recreateParts($data['text'], null, isset($data['gender'])?$data['gender']:false);
						
						// if there are message attachments, attach them
						if (isset($data['attachments']) && $data['attachments']) {
							foreach ($data['attachments'] as $cid => $details) {
								$msgattachment = new MessageAttachment();
								$msgattachment->messageid = $message->id;
								$msgattachment->contentid = $cid;
								$msgattachment->filename = $details->name;
								$msgattachment->size = $details->size;
								$msgattachment->deleted = 0;
								$msgattachment->create();
							}
						}
					}
				}
			}
		}
		
		// refresh any stale auto translations
		$messagegroup->reTranslate();
		
		$job->messagegroupid = $messagegroup->id;

		// store job lists
		foreach ($joblists as $listid)
			QuickUpdate("insert into joblist (jobid,listid) values (?,?)", false, array($job->id, $listid));

		$job->setSetting('translationexpire', date("Y-m-d", strtotime("+15 days"))); // now plus 15 days
		
		// for all the job settings on the "Advanced" step. set some advanced options that will get stuffed into the job
		$advanced = array();
		if (isset($postdata["/schedule/options"]["advanced"]) && $postdata["/schedule/options"]["advanced"])
			foreach (array("skipduplicates", "skipemailduplicates", "leavemessage", "messageconfirmation") as $option)
				if (isset($postdata["/schedule/advanced"][$option]))
					$advanced[$option] = $postdata["/schedule/advanced"][$option];

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
		
		// check for and evaluate facebook data
		$fbdata = false;
		if ($USER->authorize("facebookpost") && isset($postdata["/message/socialmedia"]["fbdata"]))
			$fbdata = json_decode($postdata["/message/socialmedia"]["fbdata"]);
		
		if ($schedule['date']) {
			// run the job if it's scheduled
			$job->runNow();
		
			// Do social media posting
			
			// Do Facebook posting
			if ($fbdata && isset($fbdata->message)) {
				foreach ($fbdata->page as $pageid => $accessToken) {
					if (!fb_post($pageid, $accessToken, $fbdata->message)) {
						// unable to post error
						error_log("Failed to post to facebook pageid: ". $pageid. " for user: ". $USER->id);
					}
				}
			}
			
			// do twitter tweeting
			if ($USER->authorize("twitterpost") && isset($postdata["/message/socialmedia"]["twdata"])) {
				$twitter = new Twitter($USER->getSetting("tw_access_token", false));
				if (!$twitter->tweet($postdata["/message/socialmedia"]["twdata"])) {
					// unable to post error
					error_log("Failed to post to twitter for user: ". $USER->id);
				}
			}
		}
		
		//check for saved messages and lists and undelete as appropriate
		if (JobWiz_scheduleSaveLists::isEnabled($postdata,false)) {
			foreach ($postdata["/schedule/savelists"]["savelists"] as $savelistid) {
				$list = new PeopleList($savelistid);
				if ($list->userid != $USER->id)
					continue;
				
				$list->deleted = 0;
				$list->update();
			}
		}
		
		if (isset($postdata["/schedule/options"]["savemessage"]) && $postdata["/schedule/options"]["savemessage"]) {
			$messagegroup->deleted = 0;
			$messagegroup->update();
		}
		
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
		$html = '';
		if ($postdata["/schedule/options"]["schedule"] == "template") {
			$html = '<h3>Success! Your notification request has been saved</h3>';
		} else {
			$html = '<h3>Success! Your notification request has been submitted.</h3>';
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
		}
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
<? Validator::load_validators(array("ValInArray", "ValJobName", "ValHasMessage", "ValTextAreaPhone","ValEasycall","ValLists","ValTranslation","ValEmailAttach", "ValTimeWindowCallLate", "ValTimeWindowCallEarly","ValRegExp", "valPhone", "ValMessageBody","ValNonEmptyMessage","ValFacebookPost"));// Included in jobwizard.inc.php ?>
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
