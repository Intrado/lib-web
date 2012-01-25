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
require_once("obj/TraslationItem.fi.php");
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
require_once("obj/MessageGroup.obj.php");
require_once("obj/FeedCategory.obj.php");
require_once("obj/MessageGroupSelectMenu.fi.php");
require_once("obj/ValLists.val.php");
require_once("obj/ValTimeWindowCallEarly.val.php");
require_once("obj/ValTimeWindowCallLate.val.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/ValMessageGroup.val.php");
require_once("inc/facebook.php");
require_once("inc/facebook.inc.php");
require_once("obj/FacebookAuth.fi.php");
require_once("obj/FacebookPage.fi.php");
require_once("obj/ValFacebookPage.val.php");
require_once("obj/TwitterAuth.fi.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");
require_once("obj/HtmlRadioButtonBigCheck.fi.php");
require_once("obj/TextAreaPhone.fi.php");
require_once("obj/PhoneMessageEditor.fi.php");
require_once("obj/TextAreaPhone.val.php");
require_once("obj/HtmlTextArea.fi.php");
require_once("obj/CheckBoxWithHtmlPreview.fi.php");
require_once("obj/TextAreaWithEnableCheckbox.fi.php");
require_once("obj/PreviewButton.fi.php");
require_once("obj/ValSmsText.val.php");
require_once("obj/ValTtsText.val.php");
require_once("obj/CallerID.fi.php");

// Includes that are required for preview to work
require_once("inc/previewfields.inc.php");
require_once("inc/appserver.inc.php");
require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php';
require_once("obj/PreviewModal.obj.php");


// Job step form data
require_once("inc/jobwizard.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Process Preview Request
////////////////////////////////////////////////////////////////////////////////
PreviewModal::HandleRequestWithId();
PreviewModal::HandleRequestWithPhoneText(null);
PreviewModal::HandleRequestWithEmailText();
////////////////////////////////////////////////////////////////////////////////
// Passed parameter checking
////////////////////////////////////////////////////////////////////////////////
// currently valid jobtypes that can be passed are 'emergency' and 'normal'
if (isset($_GET['jobtype']))
	$_SESSION['wizard_job_type'] = $_GET['jobtype'];

if (isset($_GET['debug']))
	$_SESSION['wizard_job']['debug'] = true;

$wizdata = array(
	"start" => new JobWiz_start(_L("Getting Started")),
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
		"post" => new WizSection("Social Media", array(
			"facebookauth" => new JobWiz_facebookAuth(_L("Connect Facebook")),
			"twitterauth" => new JobWiz_twitterAuth(_L("Connect Twitter")),
			"socialmedia" => new JobWiz_socialMedia(_L("Social Media")),
			"facebookpage" => new JobWiz_facebookPage(_L("Post Destination(s)"))
		))
	)),
	"schedule" => new WizSection ("Options",array(
		"options" => new JobWiz_scheduleOptions(_L("Job Options")),
		"date" => new JobWiz_scheduleDate(_L("Schedule Date/Time")),
		"advanced" => new JobWiz_scheduleAdvanced(_L("Advanced Options"))
	)),
	"submit" => new WizSection ("Confirm",array(
		"confirm" => new JobWiz_submitConfirm(_L("Review and Confirm"))
	))
);

class FinishJobWizard extends WizFinish {
	function finish ($postdata) {
		// If the job has not been confirmed, don't try to process the data.
		if (!$this->parent->dataHelper("/submit/confirm:jobconfirm"))
			return false;
		
		global $USER;
		global $ACCESS;
		
		// get job options
		$jobtype = DBFind("JobType", "from jobtype where id=?", false, array($this->parent->dataHelper("/start:jobtype")));
		$jobname = $this->parent->dataHelper("/start:name");

		// get the list or lists
		$joblists = $this->parent->dataHelper("/list:listids", true);
		// Remove temporary 'addme' token from listids. (not a valid listid, obviously)
		if (($i = array_search('addme', $joblists)) !== false)
			unset($joblists[$i]);
		
		// Start stuffing the job in the DB
		Query("BEGIN");
		
		// if there is "addme" data in the list selection, create a person and list with the contact details
		if ($this->parent->dataHelper("/list:addme")) {
			// get the contact details out of postdata
			$addme = array(
				"phone" => Phone::parse($this->parent->dataHelper("/list:addmePhone")),
				"email" => trim($this->parent->dataHelper("/list:addmeEmail")),
				"sms" => Phone::parse($this->parent->dataHelper("/list:addmeSms"))
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
		$schedule = getSchedule($this->parent);
		
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
		
		// keep track of post messages
		$jobpostmessage = array();
		
		// if this wizard has an already created message group selected. use it
		if (wizHasMessageGroup($this->parent)) {
			$messagegroup = new MessageGroup($this->parent->dataHelper("/message/pickmessage:messagegroup"));
			// pull post type messages out and keep track of their subtype
			$jobpostmessage = QuickQueryList("select subtype from message where messagegroupid = ? and type = 'post'", false, false, array($messagegroup->id));
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
				$audiofileidmap = $this->parent->dataHelper("/message/phone/callme:message", true);
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
					$message->stuffHeaders();
					$message->create();
					
					$part = new MessagePart();
					$part->messageid = $message->id;
					$part->type = "A";
					$part->audiofileid = $audiofileid;
					$part->sequence = 0;
					$part->create();
				}
				// create a post voice message (if it's enabled)
				if ($this->parent->dataHelper('/message/post/socialmedia:createpostvoice')) {
					$message = new Message();
					$message->messagegroupid = $messagegroup->id;
					$message->type = 'post';
					$message->subtype = 'voice';
					$message->autotranslate = 'none';

					$message->name = $messagegroup->name;
					$message->description = Language::getName($langcode);
					$message->userid = $USER->id;
					$message->modifydate = $messagegroup->modified;
					$message->languagecode = "en";
					$message->stuffHeaders();
					$message->create();
					
					$part = new MessagePart();
					$part->messageid = $message->id;
					$part->type = "A";
					$part->audiofileid = $audiofileidmap->en;
					$part->sequence = 0;
					$part->create();
					
					$jobpostmessage[] = "voice";
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
				'sms' => array(),
				'post' => array());
			
			// phone message
			if (JobWiz_messagePhoneText::isEnabled($postdata, false)) {
				$sourcemessage = $this->parent->dataHelper("/message/phone/text:message", true);
				
				// this is the default 'en' message so it's autotranslate value is 'none'
				$messages['phone']['voice']['en']['none']['text'] = $sourcemessage->text;
				$messages['phone']['voice']['en']['none']['gender'] = $sourcemessage->gender;
				
				//also set the messagegroup preferred gender
				$messagegroup->preferredgender = $sourcemessage->gender;
				$messagegroup->stuffHeaders();
				$messagegroup->update(array("data"));
				
				// create a post voice (provided that's enabled)
				if ($this->parent->dataHelper('/message/post/socialmedia:createpostvoice')) {
					$messages['post']['voice']['en']['none'] = $messages['phone']['voice']['en']['none'];
				}
					
				// check for and retrieve translations
				foreach ($this->parent->dataHelper("/message/phone/translate", true, "[]") as $langcode => $translatedmessage) {
					// if this translation message is enabled
					if ($translatedmessage->enabled) {
						// if the translation text is overridden, don't attach a source message
						// it isn't applicable since we have no way to know what they changed the text to.
						if ($translatedmessage->override) {
							$messages['phone']['voice'][$langcode]['overridden']['text'] = $translatedmessage->text;
							$messages['phone']['voice'][$langcode]['overridden']['gender'] = $translatedmessage->gender;
						} else {
							$messages['phone']['voice'][$langcode]['translated']['text'] = $translatedmessage->text;
							$messages['phone']['voice'][$langcode]['translated']['gender'] = $translatedmessage->gender;
							$messages['phone']['voice'][$langcode]['source'] = $messages['phone']['voice']['en']['none'];
						}
					}
				}
			}
			
			// email message
			if (JobWiz_messageEmailText::isEnabled($postdata, false)) {
				// this is the default 'en' message so it's autotranslate value is 'none'
				$messages['email']['html']['en']['none']['text'] = $this->parent->dataHelper("/message/email/text:message");
				$messages['email']['html']['en']['none']["fromname"] = $this->parent->dataHelper("/message/email/text:fromname");
				$messages['email']['html']['en']['none']["from"] = $this->parent->dataHelper("/message/email/text:from");
				$messages['email']['html']['en']['none']["subject"] = $this->parent->dataHelper("/message/email/text:subject");
				$messages['email']['html']['en']['none']['attachments'] = $this->parent->dataHelper("/message/email/text:attachments", true, "[]");
				
				// check for and retrieve translations
				$translationselections = $this->parent->dataHelper("/message/email/translate",false,array());
				$translations = translate_fromenglish($messages['email']['html']['en']['none']['text'],array_keys($translationselections));
				$translationsindex = 0;
				
				foreach ($translationselections as $langcode => $enabled) {
					if ($enabled) {
						$messages['email']['html'][$langcode]['source'] = $messages['email']['html']['en']['none'];
						$messages['email']['html'][$langcode]['translated'] = $messages['email']['html']['en']['none'];
						if ($translations[$translationsindex] !== false)
							$messages['email']['html'][$langcode]['translated']['text'] = $translations[$translationsindex];
						
					}
					$translationsindex++;
				}
			}
			
			// sms message
			if (JobWiz_messageSmsText::isEnabled($postdata, false))
				$messages['sms']['plain']['en']['none']['text'] = $this->parent->dataHelper("/message/sms/text:message");
			
			// social media
			if (JobWiz_socialMedia::isEnabled($postdata,false)) {
				if ($this->parent->dataHelper('/message/post/socialmedia:fbdata')) {
					$fbdata = $this->parent->dataHelper('/message/post/socialmedia:fbdata', true);
					$messages['post']['facebook']['en']['none']['text'] = $fbdata->message;
				}
				if ($this->parent->dataHelper('/message/post/socialmedia:twdata')) {
					$twdata = $this->parent->dataHelper('/message/post/socialmedia:twdata', true);
					$messages['post']['twitter']['en']['none']['text'] = $twdata->message;
				}
				if ($this->parent->dataHelper('/message/post/socialmedia:feeddata')) {
					$feeddata = $this->parent->dataHelper('/message/post/socialmedia:feeddata', true);
					$messages['post']['feed']['en']['none']['subject'] = $feeddata->subject;
					$messages['post']['feed']['en']['none']['text'] = $feeddata->message;
				}
			}
			
			// #################################################################
			// create a message for each one
			// for each message type
			foreach ($messages as $type => $msgdata) {
				// for each subtype
				foreach ($msgdata as $subtype => $msglang) {
					// for each language code
					foreach ($msglang as $langcode => $autotranslatevalues) {
						// for each autotranslate value
						foreach ($autotranslatevalues as $autotranslate => $data) {
							if ($data["text"]) {
								$message = new Message();
								$message->messagegroupid = $messagegroup->id;
								$message->type = $type;
								$message->subtype = $subtype;
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
								
								if ($type == 'email' || ($type == "post" && $subtype == 'feed'))
									$message->subject = $data["subject"];
								if ($type == 'email') {
									$message->fromname = $data["fromname"];
									$message->fromemail = $data["from"];
								}
								
								$message->stuffHeaders();
								$message->create();
								
								// keep track of any post type messages for adding to jobpost table later
								if ($type == "post")
									$jobpostmessage[] = $subtype;
								
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
										$msgattachment->create();
									}
								} // end if there are attachments
							} // end if this message has a body
						} // end for each autotranslate value
					} // for each language code
				} // for each subtype
			} // for each message type
		} // end if creating a message group
		
		
		$job->messagegroupid = $messagegroup->id;

		// store job lists
		foreach ($joblists as $listid)
			QuickUpdate("insert into joblist (jobid,listid) values (?,?)", false, array($job->id, $listid));

		// store the jobpost messages
		$createdpostpage = false;
		foreach ($jobpostmessage as $subtype) {
			switch ($subtype) {
				case "facebook":
					if (facebookAuthorized($this->parent)) {
						// get the destinations for facebook
						foreach ($this->parent->dataHelper("/message/post/facebookpage:fbpage", true, "[]") as $pageid) {
							if ($pageid == "me")
								$pageid = $USER->getSetting("fb_user_id");
							$job->updateJobPost("facebook", $pageid);
						}
					}
					break;
				case "twitter":
					if (twitterAuthorized($this->parent)) {
						$twitterauth = json_decode($USER->getSetting("tw_access_token"));
						$job->updateJobPost("twitter", $twitterauth->user_id);
					}
					break;
				case "page":
				case "voice":
					if (!$createdpostpage && (facebookAuthorized($this->parent) || twitterAuthorized($this->parent))) {
						$createdpostpage = true;
						$job->updateJobPost("page", "");
					}
				case "feed":
					if (getSystemSetting("_hasfeed") && $USER->authorize("feedpost"))
						$job->updateJobPost("feed", $this->parent->dataHelper("/message/post/facebookpage:feedcategories", false));
			}
		}
		
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
		
		// run the job if it's scheduled
		if ($schedule['date'])
			$job->runNow();
		
		//check for saved messages and lists and undelete as appropriate
		if (isset($postdata["/schedule/options"]["savelists"])) {
			foreach ($postdata["/schedule/options"]["savelists"] as $savelistid) {
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
<?
// Some of these are defined in jobwizard.inc.php 
Validator::load_validators(array("ValInArray", "ValJobName", "ValHasMessage",
	"ValTextAreaPhone", "ValEasycall", "ValLists", "ValTranslation", "ValEmailAttach",
	"ValTimeWindowCallLate", "ValTimeWindowCallEarly", "ValSmsText", "valPhone",
	"ValMessageBody", "ValMessageGroup", "ValMessageTypeSelect", "ValFacebookPage",
	"ValTranslationCharacterLimit","ValTimePassed","ValTtsText","ValCallerID"));
?>
</script>
<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
<script src="script/livepipe/window.js" type="text/javascript"></script>
<script src="script/modalwrapper.js" type="text/javascript"></script>
<script src="script/niftyplayer.js.php" type="text/javascript"></script>
<?
PreviewModal::includePreviewScript();

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
