<?

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	$job = new Job($_GET['id'] + 0);
	if ($job->type == "survey")
		redirect("survey.php?id=" . ($_GET['id'] + 0));

	setCurrentJob($_GET['id']);
	redirect();
}

$jobid = $_SESSION['jobid'];
$hassms = getSystemSetting('_hassms', false);

// Set up variables that determine later editability of the form
/*
 If a job is in completed mode then it is complete or cancelled.
 If a job is in submitted mode then it is active, complete, or cancelled.

 A completed job may only have its name and description edited.
 A submitted job may only have its name/description, date/time, and selected message options edited.
 */
$completedmode = false; // Flag indicating that a job is complete or cancelled so only allow editing of name and description.
$submittedmode = false; // Flag indicating that a job has been submitted, allowing editing of date/time, name/desc, and a few selected options.

if ($jobid != NULL) {
	//TODO also check that the job is not sent
	$job = new Job($_SESSION['jobid']);

	if ('complete' == $job->status || 'cancelled' == $job->status || 'cancelling' == $job->status) {
		$completedmode = true;
	}

	if ($job->status == 'active' || $job->status == 'procactive' || $job->status == 'processing' || $job->status == 'scheduled' || $completedmode) {
		$submittedmode = true;
	}
}

if (!$submittedmode && isset($_GET['deletejoblang'])) {
	$joblangid = $_GET['deletejoblang'] + 0;
	$joblang = new JobLanguage($joblangid);
	if (userOwns("job",$joblang->jobid))
	$joblang->destroy();
	redirect($JOBTYPE == "repeating" ? "jobrepeating.php" : "job.php");
	exit();
}

$VALIDJOBTYPES = JobType::getUserJobTypes();

//Fetch all job types in case the job has a jobtype that is not valid for the user
//or the jobtype is deleted now
//this array is to be used only to display the info
$infojobtypes = DBFindMany("JobType", "from jobtype");

 //Get available languages
$alllanguages = QuickQueryList("select name from language"); //DBFindMany("Language","from language");
$ttslanguages = Voice::getTTSLanguages();
$englishkey = array_search('english', $ttslanguages);
if($englishkey !== false)
	unset($ttslanguages[$englishkey]);

$voicearray = array();
$voices = DBFindMany("Voice","from ttsvoice");
foreach ($voices as $voice) {
	$voicearray[$voice->gender][$voice->language] = $voice->id;
}

$peoplelists = QuickQueryList("select id, name, (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name", true);

$joblangs = array("phone" => array(), "email" => array(), "print" => array(), "sms" => array());
if (isset($job->id)) {
	$joblangs['phone'] = DBFindMany('JobLanguage', "from joblanguage where type='phone' and jobid=" . $job->id);
	$joblangs['email'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'email' and jobid = " . $job->id);
	$joblangs['print'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'print' and jobid = " . $job->id);
	$joblangs['sms'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'sms' and jobid = " . $job->id);
}


/****************** main message section ******************/

$f = "notification";
$s = "main" . $JOBTYPE;
$reloadform = 0;




// used to determine if advanced settings need to be expanded to show error
$hassettingsdetailerror = false;
$hasphonedetailerror = false;
$hasemaildetailerror = false;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'phone') || CheckFormSubmit($f,'email') || CheckFormSubmit($f,'print') || CheckFormSubmit($f,'sms') || CheckFormSubmit($f,'send'))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		foreach (array("phone","email","print","sms") as $type){
			MergeSectionFormData($f, $type);
			if($type == "sms" || $type == "phone")
				continue;
			SetRequired($f, $s, $type . "messageid", (bool)GetFormData($f, $s, 'send' . $type));
		}
		SetRequired($f, $s, "listid", GetFormData($f, $s, "listradio") == "single");
		SetRequired($f, $s, "listids", GetFormData($f, $s, "listradio") == "multi");
		foreach (array("phone","email","sms") as $type){
			if(GetFormData($f, $s, "send" . $type)) {
				SetRequired($f, $s, $type . "messageid", GetFormData($f, $s, $type . "radio") == "select");
				SetRequired($f, $s, $type . "textarea", GetFormData($f, $s, $type . "radio") == "create");
			} else {
				SetRequired($f, $s, $type . "messageid",0);
				SetRequired($f, $s, $type . "textarea",0);
			}
		}
		if(GetFormData($f, $s, 'sendemail') && GetFormData($f, $s,"emailradio") == "create") {
			SetRequired($f, $s, "fromemail",1);
			SetRequired($f, $s, "fromname",1);
			SetRequired($f, $s, "emailsubject",1);
			$emaildomain = getSystemSetting('emaildomain');
			$fromemaildomain = substr(GetFormData($f, $s, "fromemail"), strpos(GetFormData($f, $s, "fromemail"), "@")+1);
		} else {
			SetRequired($f, $s, "fromemail",0);
			SetRequired($f, $s, "fromname",0);
			SetRequired($f, $s, "emailsubject",0);
		}			
				
		//do check

		$sendphone = GetFormData($f, $s, "sendphone");
		$sendemail = GetFormData($f, $s, "sendemail");
		$sendsms = getSystemSetting("_hassms", false) ? GetFormData($f, $s, "sendsms") : 0;

		$name = trim(GetFormData($f,$s,"name"));
		if ( empty($name) ) {
			PutFormData($f,$s,"name",'',"text",1,$JOBTYPE == "repeating" ? 30: 50,true);
		}
		$callerid = Phone::parse(GetFormData($f,$s,"callerid"));

		// check this before CheckFormSection() because we do not want to expand sections if nothing selected
		if(!$submittedmode && !$sendphone && !$sendemail && !$sendsms){
			error("Plese select a delivery type");
		} else if( CheckFormSection($f, $s) ) {
			$hassettingsdetailerror = true;
			$hasphonedetailerror = true;
			$hasemaildetailerror = true;

			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($JOBTYPE == 'normal' && (strtotime(GetFormData($f,$s,"startdate")) === -1 || strtotime(GetFormData($f,$s,"startdate")) === false)) {
			$hassettingsdetailerror = true;
			error('The start date is invalid');
		} else if ($JOBTYPE=='normal' && (strtotime(GetFormData($f,$s,"starttime")) === -1 || strtotime(GetFormData($f,$s,"starttime")) === false)) {
			$hassettingsdetailerror = true;
			error('The start time is invalid');
		} else if ($JOBTYPE=='normal' && (strtotime(GetFormData($f,$s,"endtime")) === -1 || strtotime(GetFormData($f,$s,"endtime")) === false)) {
			$hassettingsdetailerror = true;
			error('The end time is invalid');
		} else if (strtotime(GetFormData($f,$s,"endtime")) <= strtotime(GetFormData($f,$s,"starttime")) ) {
			$hassettingsdetailerror = true;
			error('The end time cannot be before or the same as the start time');
		} else if (strtotime(GetFormData($f, $s,"endtime"))-(30*60) < strtotime(GetFormData($f,$s,"starttime"))){
			$hassettingsdetailerror = true;
			error('The end time must be at least 30 minutes after the start time');
		} else if ($JOBTYPE == "normal" &&  (strtotime(GetFormData($f,$s,"startdate"))+((GetFormData($f,$s,"numdays")-1)*86400) < strtotime("today")) && !$completedmode){
			$hassettingsdetailerror = true;
			error('The end date has already passed. Please correct this problem before proceeding');
		} else if ($JOBTYPE == "normal" && (strtotime(GetFormData($f,$s,"startdate"))+((GetFormData($f,$s,"numdays")-1)*86400) == strtotime("today")) && (strtotime(GetFormData($f,$s,"endtime")) < strtotime("now")) && !$completedmode) {
			$hassettingsdetailerror = true;
			error('The end time has already passed. Please correct this problem before proceeding');
		} else if ($JOBTYPE == "normal" && GetFormData($f, $s, "sendphone") && GetFormData($f, $s, "phoneradio") == "create" && (strtotime(GetFormData($f,$s,"startdate"))-(7*86400) > strtotime("today")) && !$completedmode) {
			$hassettingsdetailerror = true;
			error('The start date must be within 7 days when creating a text-to-speach message');
		} else if (QuickQuery("select id from job where deleted = 0 and name = '" . DBsafe($name) . "' and userid = $USER->id and status in ('new','scheduled','processing','procactive','active','repeating') and id != " . ( 0+ $_SESSION['jobid']))) {
			error('A job named \'' . $name . '\' already exists');
		} else if ($callerid != "" && strlen($callerid) != 10) {
			$hasphonedetailerror = true;
			error('The Caller ID must be exactly 10 digits long (including area code)');
		} else if (getSystemSetting('_hascallback', false) && (GetFormData($f, $s, "radiocallerid") == "byuser") && (strlen($callerid) == 0)) {
			$hasphonedetailerror = true;
			error('The Caller ID must be exactly 10 digits long (including area code)');
		} else if($sendemail && GetFormData($f, $s,"emailradio") == "create" && $emaildomain && (strtolower($emaildomain) != strtolower($fromemaildomain))){
			error('The From Email address is not valid', 'You must use an email address at ' . $emaildomain);
		} else {
			//submit changes

			if ($_SESSION['jobid'] == null)
				$job = Job::jobWithDefaults();
			else
				$job = new Job($_SESSION['jobid']);

			//TODO check userowns on all messages, lists, etc
			//only allow editing some fields
			if ($completedmode) {
				$job->name = $name;
				$job->description = trim(GetFormData($f,$s,"description"));
			} else if ($submittedmode) {
				$job->name = $name;
				$job->description = trim(GetFormData($f,$s,"description"));
				PopulateObject($f,$s,$job,array("startdate", "starttime", "endtime"));
			} else {
			
				/* Process the create a message for phone and email */
				foreach (array("phone","email") as $type){	
					$mstr = $type . "messageid";
					// If this is a phonemessage and no message was selected the message is a translation message and the phonetextarea is requerd to be fuild in.
					if($USER->authorize('send' . $type) && GetFormData($f, $s, "send" . $type) && GetFormData($f, $s, $type . "radio") == "create"){
						$themessageid = null;
						$part = null;
						// A translation message is a message that was created in the jobeditor. It does not mean that if is translated
						if($job->getSetting("jobcreated" . $type)) {	
							$themessageid = $job->$mstr;
							if($themessageid) {
								$part = DBFind("MessagePart","from messagepart where messageid=" . $themessageid ." and sequence=0");	
							}
						} else {
							if($job->id) // If translation mode switched we need to erase the previous joblanguage associations
								QuickUpdate("delete from joblanguage where type='$type' and jobid=" . $job->id);  
						}						
						$job->setSetting("jobcreated" . $type, 1); // Tell the job that this message was created here
						$job->setSetting('translationexpire', date("Y-m-d", strtotime(date("Y-m-d")) + (15 * 86400))); // now plus 15 days
						$message = new Message($themessageid);
						if($type == "email") {
							$message->subject = GetFormData($f, $s, 'emailsubject');
							$message->fromname = GetFormData($f, $s, 'fromname');
							$useremails = explode(";", $USER->email);
							$message->fromemail = GetFormData($f, $s, 'fromemail');
							$message->stuffHeaders();
						}
						$message->userid = $USER->id;$message->type = $type;$message->deleted = 1;						
						$message->name = GetFormData($f, $s,'name');
						$message->description = "Translated message " . date(" M j, Y g:i:s", strtotime("now"));
						$message->update();
						if(!$part) {
							$part = new MessagePart();
							$part->messageid = $message->id;$part->type="T";$part->sequence=0;	
						}
						$part->voiceid = ($type == "phone")?$voicearray[GetFormData($f, $s, 'voiceradio')]["english"]:NULL;
						$part->txt = GetFormData($f, $s, $type . "textarea");
						$part->update();
						//Do a putform on message select so if there is an error later on, another message does not get created
						PutFormData($f, $s, $type . "messageid", $message->id, 'number', 'nomin', 'nomax');
					} else {
						if($job->getSetting("jobcreated" . $type) && $job->id) {
							//If translation mode switched we need to erase the previous joblanguage associations
							QuickUpdate("delete joblanguage j, message m, messagepart p FROM joblanguage j, message m, messagepart p where
												j.jobid=" . $job->id . " and j.messageid = m.id and m.type = '" . $type . "' and j.messageid = p.messageid");	
							if($job->$mstr) {
								QuickUpdate("delete message m, messagepart p FROM message m, messagepart p where 
									m.id=" . $job->$mstr . " and m.type = '" . $type . "' and p.messageid = m.id");
							}
						}
						$job->setSetting("jobcreated" . $type, 0);
					}
				}				
				
				if($hassms && $USER->authorize('sendsms') && GetFormData($f, $s, "sendsms") && GetFormData($f, $s, "smsradio") == "create"){
					$part = null;
					// If this Message was create in job editor we are free to edit the message, otherwise we have to create a new message
					if($job->getSetting('jobcreatedsms') && $job->smsmessageid) {
						$part = DBFind("MessagePart","from messagepart where messageid=" . $job->smsmessageid ." and sequence=0");	
					} else {
						$job->setSetting('jobcreatedsms', 1); // Tell the job that this message was created here
						$job->smsmessageid = null;
					}
					$message = new Message($job->smsmessageid);
					$message->userid=$USER->id;$message->type = 'sms';$message->deleted = 1;					
					$message->name = GetFormData($f, $s,'name');
					$message->description = "SMS Message " . date(" M j, Y g:i:s", strtotime("now"));
					$message->update();
					$job->smsmessageid = $message->id;
					if(!$part) {
						$part = new MessagePart();
						$part->messageid = $message->id;$part->type="T";$part->sequence = 0;
					}
					$part->txt = GetFormData($f, $s, 'smstextarea');
					$part->update();
					//Do a putform on message select so if there is an error later on, another message does not get created
					PutFormData($f, $s, 'smsmessageid', $message->id, 'number', 'nomin', 'nomax');
				} else {
					if($job->smsmessageid) {
						QuickUpdate("delete message m, messagepart p FROM message m, messagepart p where m.id=" . $job->smsmessageid . " and p.messageid = m.id");
					}
					$job->setSetting('jobcreatedsms', 0);	
				}

				if(GetFormData($f, $s, "listradio") == "single") {
					$job->listid = GetFormData($f, $s, "listid");
				} else {
					$job->listid = array_shift(GetFormData($f,$s,'listids'));
				}

				$job->name = $name;
				$job->description = trim(GetFormData($f,$s,"description"));
				$fieldsarray = array("jobtypeid", "phonemessageid",
				"emailmessageid","printmessageid", "smsmessageid", "starttime", "endtime",
				"sendphone", "sendemail", "sendprint", "sendsms", "maxcallattempts");
				PopulateObject($f,$s,$job,$fieldsarray);

				if ($JOBTYPE != 'repeating') {
					$job->startdate = GetFormData($f, $s, 'startdate');
				}

				if(!$USER->authorize('sendphone'))
					$job->sendphone = false;
				if(!$USER->authorize('sendemail'))
					$job->sendemail = false;
				if(!$USER->authorize('sendprint'))
					$job->sendprint = false;
				if(!$hassms || !$USER->authorize('sendsms'))
					$job->sendsms = false;
			}

			$jobtypes = array();
			if ($job->sendphone && $job->phonemessageid != 0) {
				$jobtypes[] = "phone";
			} else {
				$job->phonemessageid = NULL;
				$job->sendphone = false;
			}
			if ($job->sendemail && $job->emailmessageid != 0) {
				$jobtypes[] = "email";
			} else {
				$job->emailmessageid = NULL;
				$job->sendemail = false;
			}
			if ($job->sendprint && $job->printmessageid != 0) {
				$jobtypes[] = "print";
			} else {
				$job->printmessageid = NULL;
				$job->sendprint = false;
			}
			if ($hassms && $job->sendsms && $job->smsmessageid != 0) {
				$jobtypes[] = "sms";
			} else {
				$job->smsmessageid = NULL;
				$job->sendsms = false;
			}
			$job->type=implode(",",$jobtypes);

			//repopulate the form with these linked values in case of a validation error.
			$fields = array(
				array("sendphone","bool",0,1),
				array("sendemail","bool",0,1),
				array("sendprint","bool",0,1),
				array("sendsms","bool",0,1)
			);

			PopulateForm($f,$s,$job,$fields);

			if ($JOBTYPE == "repeating") {
				$schedule = new Schedule($job->scheduleid);
				$schedule->time = date("H:i", strtotime(GetFormData($f,$s,"scheduletime")));
				$schedule->triggertype = "job";
				$schedule->type = "R";
				$schedule->userid = $USER->id;

				$dow = array();
				for ($x = 1; $x < 8; $x++) {
					if(GetFormData($f,$s,"dow$x")) {
						$dow[$x-1] = $x;
					}
				}
				$schedule->daysofweek = implode(",",$dow);
				$schedule->nextrun = $schedule->calcNextRun();

				$schedule->update();
				$job->scheduleid = $schedule->id;

				$numdays = GetFormData($f, $s, 'numdays');

				// 86,400 seconds in a day - precaution b/c windows doesn't
				//	like dates before 1970, and using 0 makes windows think it's 12/31/69
				$job->startdate = date("Y-m-d", 86400);
				$job->enddate = date("Y-m-d", ($numdays * 86400));
			} else if ($JOBTYPE == 'normal' && !$completedmode) {
				$numdays = GetFormData($f, $s, 'numdays');
				$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
			}

			//reformat the dates & times to DB friendly format
			$job->startdate = date("Y-m-d", strtotime($job->startdate));
			$job->enddate = date("Y-m-d", strtotime($job->enddate));
			$job->starttime = date("H:i", strtotime($job->starttime));
			$job->endtime = date("H:i", strtotime($job->endtime));
			$job->userid = $USER->id;

			// make sure we don't resave these options on an already submitted or completed job
			if(!$submittedmode && !$completedmode) {
				$job->setOption("skipduplicates",GetFormData($f,$s,"skipduplicates"));
				$job->setOption("skipemailduplicates",GetFormData($f,$s,"skipemailduplicates"));

				if ($USER->authorize('setcallerid') && GetFormData($f,$s,"callerid")) {
					$job->setOptionValue("callerid",Phone::parse(GetFormData($f,$s,"callerid")));
					// if customer has callback feature
					if (getSystemSetting('_hascallback', false)) {
						$radio = "0";
						if (GetFormData($f, $s, "radiocallerid") == "byuser") {
							$radio = "1";
						}
						$job->setSetting('prefermycallerid', $radio);
					}
				} else {
					$callerid = $USER->getSetting("callerid",getSystemSetting('callerid'));
					$job->setOptionValue("callerid", $callerid);
				}

				if ($USER->authorize("leavemessage"))
					$job->setOption("leavemessage", GetFormData($f,$s,"leavemessage"));

				if ($USER->authorize("messageconfirmation"))
					$job->setOption("messageconfirmation", GetFormData($f, $s, "messageconfirmation"));

			}
			if(!$completedmode){

				$job->setOption("sendreport",GetFormData($f,$s,"sendreport"));
				$job->setOptionValue("maxcallattempts", GetFormData($f,$s,"maxcallattempts"));
			}
			if ($job->id) {
				$job->update();
				status('Updated Job information successfully');
			} else {
				if ($JOBTYPE == "normal") {
					$job->status = "new";
				} else {
					$job->status = "repeating";
				}

				$job->createdate = QuickQuery("select now()");
				$job->create();
			}

			$_SESSION['jobid'] = $job->id;
			//echo $job->_lastsql;
			//echo mysql_error();


			//now add any language options
			$addlang = false;
			foreach (array("phone","email") as $type){	
				if ($USER->authorize('sendmulti') && $job->getSetting("jobcreated" . $type) ) {
					($type == "phone") ? $languages = &$ttslanguages : $languages = &$alllanguages;
					foreach($languages as $language) {
						if($language == "English")
							continue;
						$language = escapehtml(ucfirst($language));
						$joblanguage = DBFind("JobLanguage","from joblanguage where jobid=" . $job->id . " and language='$language' and type='$type'");
						if(GetFormData($f, $s, $type . "_$language")){
							$voiceid = NULL;
							if($type == "phone"){
								if (GetFormData($f, $s, 'voiceradio') == "female") {
									if(isset($voicearray['female'][$language])){
										$voiceid = $voicearray['female'][$language];
									} else if(isset($voicearray['male'][$language])){
										$voiceid = $voicearray['male'][$language];
									}
								} else {
									if(isset($voicearray['male'][$language])){
										$voiceid = $voicearray['male'][$language];
									} else if(isset($voicearray['female'][$language])){
										$voiceid = $voicearray['female'][$language];
									}
								}
								if(!$voiceid)
									error_log("Warning no voice found for $language");		
							}
							if(!$joblanguage) {
								$joblanguage = new Joblanguage();
								$joblanguage->jobid=$job->id;
							}
							$message = new Message($joblanguage->messageid);
							$message->userid=$USER->id;$message->type=$type;$message->name ="$language translation";$message->description="";$message->deleted=1;
							if($type == "email") {
								$message->subject = GetFormData($f, $s, 'emailsubject');
								$message->fromname = $USER->firstname;
								$message->fromaddress = $USER->lastname;
								$useremails = explode(";", $USER->email);
								$message->fromemail = $useremails[0];
								$message->stuffHeaders();
							}
							$message->update();
							$part = NULL;
							if($message->id) {
								$part = DBFind("MessagePart","from messagepart where messageid=" . $message->id ." and sequence=0");				
							}
							if(!$part) {
								$part = new MessagePart();
								$part->messageid=$message->id;$part->type="T";$part->sequence=0;
							}
							$part->txt = GetFormData($f, $s, $type."expand_" . $language); // If textarea box is disabled the return value will be blank. 			
							$part->voiceid = $voiceid;		
							$part->update();
							$joblanguage->messageid=$message->id;$joblanguage->type=$type;$joblanguage->language=$language;
							$joblanguage->translationeditlock = GetFormData($f, $s,$type."edit_$language");
							$joblanguage->update();			
						} else {
							if($joblanguage) {
								QuickUpdate("delete joblanguage, message, messagepart FROM joblanguage, message, messagepart where 
								joblanguage.messageid = $joblanguage->messageid and joblanguage.messageid = message.id and joblanguage.type='$type' and joblanguage.messageid = messagepart.messageid");
							}
						}
					}
				}
			}
			if($USER->authorize('sendmulti')) {
				$types = array();
				if(!$job->getSetting('jobcreatedphone'))
					$types[] = "phone";
				if(!$job->getSetting('jobcreatedemail'))
					$types[] = "email";	
				$types[] = "print";	
				
				foreach ($types as $type) {
					if (CheckFormSubmit($f,$type))
						$addlang = true;

					if (GetFormData($f,$type,"newlang" . $type) && GetFormData($f,$type,"newmess" . $type)) {
						MergeSectionFormData($f, $type);
						$joblang = new JobLanguage();
						$joblang->type = $type;
						$joblang->language = GetFormData($f,$type,"newlang" . $type);
						$joblang->messageid = GetFormData($f,$type,"newmess" . $type);
						$joblang->jobid = $job->id;
						if ($joblang->language && $joblang->messageid)
						$joblang->create();
					}
				}
			}

			/* Store multilists*/
			QuickUpdate("DELETE FROM joblist WHERE jobid=$job->id");
			if(GetFormData($f, $s, "listradio") == "multi") {
				$batchvalues = array();
				$listids = GetFormData($f,$s,'listids');

				array_shift($listids);  // The first list has already been added to the job above
				foreach($listids as $id) {
					$values = "($job->id,". ($id+0) . ")";
					$batchvalues[] = $values;
				}
				if(!empty($batchvalues)){
					$sql = "INSERT INTO joblist (jobid,listid) VALUES ";
					$sql .= implode(",",$batchvalues);
					QuickUpdate($sql);
				}
			}

			//TODO check for send button
			if ($JOBTYPE == "normal" && CheckFormSubmit($f,'send')) {
				if ($job->phonemessageid || $job->emailmessageid || $job->printmessageid || $job->smsmessageid)	{
					ClearFormData($f);
					redirect("jobconfirm.php?id=" . $job->id);
				}
			} else if (!$addlang) {
				if ($job->phonemessageid || $job->emailmessageid || $job->printmessageid || $job->smsmessageid)	{
					ClearFormData($f);
					redirect('jobs.php');
				}
			} else {
				$reloadform = 1;
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	if ($_SESSION['jobid'] == NULL) {
		$jobid = NULL;
		$job = Job::jobWithDefaults();
	} else {
		$job = new Job($_SESSION['jobid']);
	}

	//beautify the dates & times
	$job->startdate = date("m/j/Y", strtotime($job->startdate));
	//$job->startdate = date("F jS, Y", strtotime($job->startdate));
	$job->enddate = date("F jS, Y", strtotime($job->enddate));
	$job->starttime = date("g:i a", strtotime($job->starttime));
	$job->endtime = date("g:i a", strtotime($job->endtime));

	//TODO break out options
	$fields = array(
	array("name","text",1,$JOBTYPE == "repeating" ? 30: 50,true),
	array("description","text",1,50,false),
	array("jobtypeid","number","nomin","nomax", true),
	array("listid","number","nomin","nomax",true),
	array("phonemessageid","number","nomin","nomax"),
	array("emailmessageid","number","nomin","nomax"),
	array("printmessageid","number","nomin","nomax"),
	array("smsmessageid","number","nomin","nomax"),
	array("starttime","text",1,50,true),
	array("endtime","text",1,50,true),
	array("sendphone","bool",0,1),
	array("sendemail","bool",0,1),
	array("sendprint","bool",0,1),
	array("sendsms","bool",0,1)
	);

	PopulateForm($f,$s,$job,$fields);

	$selectedlists = array(); // ids
	PutFormData($f,$s,"listradio","single");

	if($jobid){
		$selectedlists = QuickQueryList("select listid from joblist where jobid=$job->id", false);
		PutFormData($f,$s,"listradio",empty($selectedlists)?"single":"multi");
		if($job->listid) {
			$selectedlists[] = $job->listid;
		}
	}
	PutFormData($f,$s,"listids",$selectedlists,"array",array_keys($peoplelists),"nomin","nomax",true);
	// names to display in submittedmode
	$selectedlistnames = array();
	if (count($selectedlists) > 0)
		$selectedlistnames = QuickQueryList("select name from list where id in (".implode(",", $selectedlists).")");

	PutFormData($f,$s,"voiceradio","female");
	PutFormData($f,$s,"phonetextarea","","text");
	PutFormData($f,$s,"emailtextarea","","text");
	PutFormData($f,$s,"emailsubject","","text");
	PutFormData($f,$s,"phoneradio","select");
	PutFormData($f,$s,"emailradio","select");
	
	PutFormData($f,$s,"fromname",$USER->firstname . " " . $USER->lastname,"text");
	$useremails = explode(";", $USER->email);
	PutFormData($f,$s,"fromemail",$useremails[0],"text");
	if($job->getSetting('jobcreatedphone')) {
		PutFormData($f,$s,"phoneradio","create");
		if($job->phonemessageid && $part = DBFind("MessagePart","from messagepart where messageid=$job->phonemessageid and sequence=0")) {
			PutFormData($f,$s,"phonetextarea",escapehtml($part->txt),'text');
			if($part->voiceid == $voicearray['male']['english'])
				PutFormData($f,$s,"voiceradio","male");			
		}	
	}
	if($job->getSetting('jobcreatedemail')) {
		PutFormData($f,$s,"emailradio","create");
		if($job->emailmessageid && $message = DBFind("Message","from message where id=$job->emailmessageid")) {
			$message->readHeaders();
			PutFormData($f,$s,"emailsubject",escapehtml($message->subject),'text');	
			PutFormData($f,$s,"fromname",escapehtml($message->fromname),"text");
			PutFormData($f,$s,"fromemail",escapehtml($message->fromemail),"text");
		}	
		if($part = DBFind("MessagePart","from messagepart where messageid=$job->emailmessageid and sequence=0")) {
			PutFormData($f,$s,"emailtextarea",escapehtml($part->txt),'text');		
		}	
	}
	if($USER->authorize('sendmulti')) {
		PutFormData($f,$s,"phonetranslatecheck",0,"bool",0,1);
		PutFormData($f,$s,"emailtranslatecheck",0,"bool",0,1);

		foreach($ttslanguages as $ttslanguage) {
			$language = escapehtml(ucfirst($ttslanguage));
			PutFormData($f,$s,"phoneexpand_$language","","text","nomin","nomax",false);
			PutFormData($f,$s,"phoneverify_$language","","text","nomin","nomax",false);
			PutFormData($f,$s,"phone_$language",0,"bool",0,1);
			PutFormData($f,$s,"phoneedit_$language",0,"bool",0,1);	
		}
		foreach($alllanguages as $alllanguage) {
			if($alllanguage == "English")
				continue;
			$language = escapehtml(ucfirst($alllanguage));
			PutFormData($f,$s,"emailexpand_$language","","text","nomin","nomax",false);
			PutFormData($f,$s,"emailverify_$language","","text","nomin","nomax",false);
			PutFormData($f,$s,"email_$language",0,"bool",0,1);
			PutFormData($f,$s,"emailedit_$language",0,"bool",0,1);	
		}
		$expired = true;
		$expire = $job->getSetting('translationexpire');
		if($expire && strtotime($expire) > strtotime(date("Y-m-d"))) {
			$expired = false;
		}
		if($job->getSetting('jobcreatedphone')) {
			foreach($joblangs['phone'] as $joblang){
				$language = escapehtml(ucfirst($joblang->language));				
				PutFormData($f,$s,"phonetranslatecheck",1,"bool",0,1);
				PutFormData($f,$s,"phone_$language",1,"bool",0,1);
				if ($joblang->translationeditlock != 0 || $expired === false) {
					if($joblang->messageid && $part = DBFind("MessagePart","from messagepart where messageid=$joblang->messageid and sequence = 0")) {
						$body = escapehtml($part->txt);
						PutFormData($f,$s,"translationtext_$language",$body,"text","nomin","nomax",false);
						PutFormData($f,$s,"phoneexpand_$language",$body,"text","nomin","nomax",false);
						PutFormData($f,$s,"phoneedit_$language",$joblang->translationeditlock,"bool",0,1);			
					}
				}			
			}
		}
		if($job->getSetting('jobcreatedemail')) {
			foreach($joblangs['email'] as $joblang){
				$language = escapehtml(ucfirst($joblang->language));				
				PutFormData($f,$s,"emailtranslatecheck",1,"bool",0,1);
				PutFormData($f,$s,"email_$language",1,"bool",0,1);
				if ($joblang->translationeditlock || $expired == false) {
					if($joblang->messageid && $part = DBFind("MessagePart","from messagepart where messageid=$joblang->messageid and sequence = 0")) {
						$body = escapehtml($part->txt);
						PutFormData($f,$s,"emailtext_$language",$body,"text","nomin","nomax",false);
						PutFormData($f,$s,"emailexpand_$language",$body,"text","nomin","nomax",false);
						PutFormData($f,$s,"emailedit_$language",$joblang->translationeditlock,"bool",0,1);
					}
				}			
			}
		}
	}

	PutFormData($f,$s,"maxcallattempts",$job->getOptionValue("maxcallattempts"), "number",1,$ACCESS->getValue('callmax'),true);
	PutFormData($f,$s,"skipduplicates",$job->isOption("skipduplicates"), "bool",0,1);
	PutFormData($f,$s,"skipemailduplicates",$job->isOption("skipemailduplicates"), "bool",0,1);

	PutFormData($f,$s,"sendreport",$job->isOption("sendreport"), "bool",0,1);
	PutFormData($f, $s, 'numdays', (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400, 'number', 1, ($ACCESS->getValue('maxjobdays') != null ? $ACCESS->getValue('maxjobdays') : "7"), true);
	PutFormData($f,$s,"callerid", Phone::format($job->getOptionValue("callerid")), "phone", 10, 10);

	if ($job->getSetting("prefermycallerid","0") == "1") {
		$radio = "byuser";
	} else {
		$radio = "bydefault";
	}
	PutFormData($f, $s, "radiocallerid", $radio);


	PutFormData($f,$s,"leavemessage",$job->isOption("leavemessage"), "bool", 0, 1);
	PutFormData($f,$s,"messageconfirmation",$job->isOption("messageconfirmation"), "bool", 0, 1);

	if ($JOBTYPE == "repeating") {
		$schedule = new Schedule($job->scheduleid);

		$scheduledows = array();
		if ($schedule->id == NULL) {
			$schedule->time = $USER->getCallEarly();
		} else {
			$data = explode(",", $schedule->daysofweek);
			for ($x = 1; $x < 8; $x++)
				$scheduledows[$x] = in_array($x,$data);
		}
		for ($x = 1; $x < 8; $x++) {
			PutFormData($f,$s,"dow$x",(isset($scheduledows[$x]) ? $scheduledows[$x] : 0),"bool",0,1);
		}
		PutFormData($f,$s,"scheduletime",date("g:i a", strtotime($schedule->time)),"text",1,50,true);
	} else {
		PutFormData($f, $s, 'startdate', $job->startdate, 'text', 1, 50, true);
	}

	PutFormData($f,"phone","newlangphone","");
	PutFormData($f,"phone","newmessphone","");
	PutFormData($f,"email","newlangemail","");
	PutFormData($f,"email","newmessemail","");
	PutFormData($f,"print","newlangprint","");
	PutFormData($f,"print","newmessprint","");
	
	PutFormData($f,$s,"smstextarea", "", "text", 0, 160);
	PutFormData($f,$s,"smsradio","select");
	if($job->getSetting('jobcreatedsms') && $job->smsmessageid) {
		PutFormData($f,$s,"smsradio","create");
		if($part = DBFind("MessagePart","from messagepart where messageid=$job->smsmessageid and sequence=0")){
			PutFormData($f,$s,"smstextarea", $part->txt, "text", 0, 160);
		}
	}
}

$messages = array();
// if submitted or completed, gather only the selected messageids used by this job
// because the schedulemanager copies all messages setting deleted=1 when job is due to start
if ($submittedmode || $completedmode) {
	$messages['phone'] = DBFindMany("Message","from message where id='$job->phonemessageid' or id in (select messageid from joblanguage where type='phone' and jobid=$job->id)");
	$messages['email'] = DBFindMany("Message","from message where id='$job->emailmessageid' or id in (select messageid from joblanguage where type='email' and jobid=$job->id)");
	$messages['print'] = DBFindMany("Message","from message where id='$job->printmessageid' or id in (select messageid from joblanguage where type='print' and jobid=$job->id)");
	$messages['sms'] = DBFindMany("Message","from message where id='$job->smsmessageid' or id in (select messageid from joblanguage where type='sms' and jobid=$job->id)");

} else {
	$messages['phone'] = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='phone' order by name");
	$messages['email'] = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='email' order by name");
	$messages['print'] = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='print' order by name");
	$messages['sms'] = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='sms' order by name");
	// find if this was a copied job, with deleted messages
	if (isset($job) && $job->id != null) {
		$copiedmessages = DBFindMany("Message","from message where id='$job->phonemessageid' or id in (select messageid from joblanguage where type='phone' and jobid=$job->id)");
		foreach ($copiedmessages as $m) {
			if ($m->deleted == "1") {
				$m->name = "(copy) ".$m->name;
				$messages['phone'][] = $m;
			}
		}
		$copiedmessages = DBFindMany("Message","from message where id='$job->emailmessageid' or id in (select messageid from joblanguage where type='email' and jobid=$job->id)");
		foreach ($copiedmessages as $m) {
			if ($m->deleted == "1") {
				$m->name = "(copy) ".$m->name;
				$messages['email'][] = $m;
			}
		}
		$copiedmessages = DBFindMany("Message","from message where id='$job->printmessageid' or id in (select messageid from joblanguage where type='print' and jobid=$job->id)");
		foreach ($copiedmessages as $m) {
			if ($m->deleted == "1") {
				$m->name = "(copy) ".$m->name;
				$messages['print'][] = $m;
			}
		}
		$copiedmessages = DBFindMany("Message","from message where id='$job->smsmessageid' or id in (select messageid from joblanguage where type='sms' and jobid=$job->id)");
		foreach ($copiedmessages as $m) {
			if ($m->deleted == "1") {
				$m->name = "(copy) ".$m->name;
				$messages['sms'][] = $m;
			}
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function message_select($type, $form, $section, $name, $extrahtml = "") {
	global $messages, $submittedmode;
?>
<table border=0 cellpadding=3 cellspacing=0>
	<tr>
		<td><?
		NewFormItem($form,$section,$name, "selectstart", NULL, NULL, "id='$name' style='float:left;' " . ($submittedmode ? " DISABLED " : "") . $extrahtml);
		NewFormItem($form,$section,$name, "selectoption", ' -- Select a Message -- ', "");
		foreach ($messages[$type] as $message) {
			NewFormItem($form,$section,$name, "selectoption", $message->name, $message->id);
		}
		NewFormItem($form,$section,$name, "selectend");
		?></td>
		<?	if ($type == "phone") { ?>
		<td><?= button('Play', "var audio = new getObj('$name').obj; if(audio.selectedIndex >= 1) popup('previewmessage.php?id=' + audio.options[audio.selectedIndex].value, 400, 400);") ?>
		</td>
		<?	} ?>
	</tr>
</table>
<?
}

function language_select($form, $section, $name, $skipusedtype) {
	global $job, $alllanguages, $joblangs, $submittedmode;

	NewFormItem($form, $section, $name, 'selectstart', NULL, NULL, ($submittedmode ? "DISABLED" : ""));
	NewFormItem($form, $section, $name, 'selectoption'," -- Select a Language -- ","");
	foreach ($alllanguages as $language) {
		if($job && !$job->getSetting('jobcreatedphone')) {
			$used = false;
			foreach ($joblangs[$skipusedtype] as $joblang) {
				if ($joblang->language == $language) {
					$used = true;
					break;
				}
			}

			if ($used)
			continue;
		}
		NewFormItem($form, $section, $name, 'selectoption', $language, $language);
	}
	NewFormItem($form, $section, $name, 'selectend');
}

function alternate($type) {
	global $USER, $f, $job, $messages, $joblangs, $submittedmode, $JOBTYPE;
	if($USER->authorize('sendmulti')) {
?>
<table border="0" cellpadding="2" cellspacing="1" class="list">
	<tr class="listHeader" align="left" valign="bottom">
		<th>Language Preference</th>
		<th>Message to Send</th>
		<th>&nbsp;</th>
	</tr>
	<?
	if ($job && $job->getSetting('jobcreatedphone') === 0){
		$id = $type . 'messageid';
		//just show the selected options? allowing to edit could cause the page to become slow
		//with many languages/messages
		foreach($joblangs[$type] as $joblang) {
		?>
		<tr valign="middle">
			<td><?= $joblang->language ?></td>
			<td><? if ($type == "phone") { ?>
			<div style="float: right;"><?= button('Play', "popup('previewmessage.php?id=" . $joblang->messageid . "', 400, 400);"); ?></div>
			<? } ?> <?= $messages[$type][$joblang->messageid]->name ?></td>
			<td><? if (!$submittedmode) { ?> <a
				href="<?= ($JOBTYPE == "repeating" ? "jobrepeating.php" : "job.php") ?>?deletejoblang=<?= $joblang->id ?>">Delete</a>
				<? } ?></td>
		</tr>
		<?
		}
	}
	?>
	<tr valign="middle">
		<td><? language_select($f,$type,"newlang" . $type, $type); ?></td>
		<td><? message_select($type, $f, $type, 'newmess' . $type); ?></td>
		<td><? 	if (!$submittedmode)
		echo submit($f, $type, 'Add'); ?></td>
	</tr>
</table>

<?
	} else {
		echo "&nbsp;";
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:jobs";
$TITLE = ($JOBTYPE == 'repeating' ? 'Repeating Job Editor: ' : 'Job Editor: ') . ($jobid == NULL ? "New Job" : escapehtml($job->name));
if (isset($job))
	$DESCRIPTION = "Job status: " . fmt_status($job, NULL);

include_once("nav.inc.php");
?>
<div id="translationstatus" align="center" style="display: none;font-size: 14px;font-weight: bold;"></div>
<?
NewForm($f);

if ($JOBTYPE == "normal") {
	if ($submittedmode)
	buttons(submit($f, $s, 'Save'));
	else
	buttons(submit($f, $s, 'Save For Later'),button('Proceed To Confirmation',($USER->authorize('sendmulti') && $JOBTYPE != 'repeating')?"sendjobconfirm();":"submitForm('$f','send');"));
} else {
	buttons(submit($f, $s, 'Save'));
}


startWindow('Job Information');

if ($JOBTYPE == "repeating" && getSystemSetting("disablerepeat") ) {
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td align=center>
		<div class='alertmessage noprint'>The System Administrator has
		disabled all Repeating Jobs. <br>
		No Repeating Jobs can be run while this setting remains in effect.</div>
		</td>
	</tr>
</table>
<?
}

?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Delivery Type:<br></th>
		<td class="bottomBorder">
		<table border="0" cellpadding="2" cellspacing="0">
			<tr>
			<?
			if($USER->authorize('sendphone')){
				?>
				<td align="center" style="padding-left: 15px">
				<div <?=$submittedmode ? "" : "onclick=\"clickIcon('phone')\"" ?>><img
					src="img/themes/<?=getBrandTheme()?>/icon_phone.gif"
					align="absmiddle"></div>
				</td>
				<?
			}
			if($USER->authorize('sendemail')){
				?>
				<td align="center" style="padding-left: 15px">
				<div <?=$submittedmode ? "" : "onclick=\"clickIcon('email')\"" ?>><img
					src="img/themes/<?=getBrandTheme()?>/icon_email.gif"
					align="absmiddle"></div>
				</td>
				<?
			}
			if($hassms && $USER->authorize('sendsms')){
				?>
				<td align="center" style="padding-left: 15px">
				<div <?=$submittedmode ? "" : "onclick=\"clickIcon('sms')\""?>><img
					src="img/themes/<?=getBrandTheme()?>/icon_sms.gif"
					align="absmiddle"></div>
				</td>
				<?
			}
			?>
			</tr>
			<tr>
			<?
			if($USER->authorize('sendphone')){
				?>
				<td style="padding-left: 15px">Phone:<? NewFormItem($f,$s,"sendphone","checkbox",NULL,NULL,"id='sendphone' " . ($submittedmode ? "DISABLED" : "") . " onclick=\"if(this.checked) displaySection('phone'); else hideSection('phone')\""); ?></td>
				<?
			}
			if($USER->authorize('sendemail')){
				?>

				<td style="padding-left: 15px">Email:<? NewFormItem($f,$s,"sendemail","checkbox",NULL,NULL,"id='sendemail' " . ($submittedmode ? "DISABLED" : "") . " onclick=\"if(this.checked) displaySection('email'); else hideSection('email');\""); ?></td>
				<?
			}
			if($hassms && $USER->authorize('sendsms')){
				?>
				<td style="padding-left: 15px">SMS:<? NewFormItem($f,$s,"sendsms","checkbox",NULL,NULL,"id='sendsms' " . ($submittedmode ? "DISABLED" : "") . " onclick=\"if(this.checked) displaySection('sms'); else hideSection('sms');\""); ?></td>
				<?
			}
			?>
			</tr>
		</table>
		</td>
	</tr>

	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:<br></th>
		<td class="bottomBorder">&nbsp;
		<div id="settings" style="display: none">
		<table border="0" cellpadding="2" cellspacing="0" width="100%">
			<tr>
				<td width="30%">Job Name</td>
				<td><? NewFormItem($f,$s,"name","text", 30,$JOBTYPE == "repeating" ? 30:50); ?>
			</td>
			<tr>
				<td>Description</td>
				<td><? NewFormItem($f,$s,"description","text", 30,50); ?></td>
			</tr>

			<? if ($JOBTYPE == "repeating") { ?>
			<tr>
				<td>Repeat this job every:</td>
				<td>
				<table border="0" cellpadding="2" cellspacing="1" class="list">
					<tr class="listHeader" align="left" valign="bottom">
						<th>Su</th>
						<th>M</th>
						<th>Tu</th>
						<th>W</th>
						<th>Th</th>
						<th>F</th>
						<th>Sa</th>
						<th>Time</th>
					</tr>
					<tr>
						<td><? NewFormItem($f,$s,"dow1","checkbox"); ?></td>
						<td><? NewFormItem($f,$s,"dow2","checkbox"); ?></td>
						<td><? NewFormItem($f,$s,"dow3","checkbox"); ?></td>
						<td><? NewFormItem($f,$s,"dow4","checkbox"); ?></td>
						<td><? NewFormItem($f,$s,"dow5","checkbox"); ?></td>
						<td><? NewFormItem($f,$s,"dow6","checkbox"); ?></td>
						<td><? NewFormItem($f,$s,"dow7","checkbox"); ?></td>
						<td><? time_select($f,$s,"scheduletime", NULL, NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate')); ?></td>
					</tr>
				</table>
				</td>
			</tr>
			<? } ?>
			<tr>
				<td>Job Type <?= help('Job_SettingsType',NULL,"small"); ?></td>
				<td>
				<table border="0" cellpadding="2px" cellspacing="1px" class="list"
					id="jobtypetable">
					<tr class="listHeader" align="left" valign="bottom">
						<th>Name</th>
						<th style="padding-left: 6px;">Info</th>
						<?
						if(getSystemSetting('_dmmethod', "asp")=='hybrid'){
							?>
						<th>Delivery System</th>
						<?
						}
						?>
					</tr>
					<tr valign="top">
						<td><?

						NewFormItem($f,$s,"jobtypeid", "selectstart", NULL, NULL, "id=jobtypeid " . ($submittedmode ? "DISABLED" : "") . " onchange='display_jobtype_info(this.value)' ");
						NewFormItem($f,$s,"jobtypeid", "selectoption", " -- Select a Job Type -- ", "");
						foreach ($VALIDJOBTYPES as $item) {
							NewFormItem($f,$s,"jobtypeid", "selectoption", $item->name, $item->id);
						}
						NewFormItem($f,$s,"jobtypeid", "selectend");
						?></td>

						<td style="padding-left: 6px;">
						<div id="jobtypeinfo" style="float: left;"></div>
						</td>
						<?
						if(getSystemSetting('_dmmethod', "asp")=='hybrid'){
							?>
						<td>
						<div id="addinfo"></div>
						</td>
						<?
						}
						?>
					</tr>
				</table>
				</td>
			</tr>
			<tr>
				<td valign="top">List(s) <?= help('Job_SettingsList',NULL,"small"); ?></td>
<?				if ($submittedmode) {
?>
				<td>
<?					foreach ($selectedlistnames as $listname) {
						echo $listname."<br>";
					}
?>
				</td>

<?				} else {
?>
				<td valign="top" width="100%" style="white-space:nowrap;">
<?					NewFormItem($f, $s, "listradio", "radio", NULL, "single","id='listradio_single' onclick=\"if(this.checked == true) {show('singlelist');hide('multilist');} else{hide('singlelist');show('multilist');}\""); ?>One List&nbsp;
<?					NewFormItem($f, $s, "listradio", "radio", NULL, "multi","id='listradio_multi' onclick=\"if(this.checked == true) {hide('singlelist');show('multilist');} else{show('singlelist');hide('multilist');}\""); ?>Multiple Lists
				<div id='singlelist' style="padding-top: 1em;display: none">
<?
						NewFormItem($f,$s,"listid", "selectstart");
						NewFormItem($f,$s,"listid", "selectoption", "-- Select a list --", NULL);
						foreach ($peoplelists as $id => $name) {
							NewFormItem($f,$s,"listid", "selectoption", $name, $id);
						}
						NewFormItem($f,$s,"listid", "selectend");
?>
				</div>
				<div id='multilist' style="padding-top: 1em;display: none">
<?
					NewFormItem($f,$s,"listids", "selectmultiple",10, $peoplelists, "");
?>
				</td>
<?				}
?>
			</tr>
		</table>
		</div>

		<div id='displaysettingsdetails' style="display: none"><a href="#"
			onclick="displaySection('settings', true); return false; ">Show
		advanced options</a></div>

		<div id='displaysettingsbasic' style="display: none"><a href="#"
			onclick="displaySection('settings', false); return false; ">Hide
		advanced options</a></div>

		<div id="settingsdetails" style="display: none">
		<table border="0" cellpadding="2" cellspacing="0" width="100%">
		<? if ($JOBTYPE != "repeating") { ?>
			<tr>
				<td width="30%">Start date <?= help('Job_SettingsStartDate',NULL,"small"); ?></td>
				<td><? NewFormItem($f,$s,"startdate","text", 30, NULL, ($completedmode ? "DISABLED" : "onfocus=\"this.select();lcs(this,false,true)\" onclick=\"event.cancelBubble=true;this.select();lcs(this,false,true)\"")); ?></td>
			</tr>
			<? } ?>

			<tr>
				<td width="30%">Number of days to run <?= help('Job_SettingsNumDays', NULL, "small"); ?></td>
				<td><?
				NewFormItem($f, $s, 'numdays', "selectstart", NULL, NULL, ($completedmode ? "DISABLED" : ""));
				//. " onchange=\"showDate('" . date("Y M d", ($job!= null ? strtotime($job->startdate) : 'today')) . "', this.options[this.selectedIndex].value);\"");
				$maxdays = $ACCESS->getValue('maxjobdays');
				if ($maxdays == null) {
					$maxdays = 7; // Max out at 7 days if the permission is not set.
				}
				for ($i = 1; $i <= $maxdays; $i++) {
					NewFormItem($f, $s, 'numdays', "selectoption", $i, $i);
				}
				NewFormItem($f, $s, 'numdays', "selectend");
				?> <!--
					<span id="job_end_date">You have scheduled this job to end on <?= isset($job) ? $job->enddate : "" ?></span>
					--></td>
			</tr>
			<tr>
				<td colspan="2">Delivery window:</td>


			<tr>
				<td>&nbsp;&nbsp;Earliest <?= help('Job_PhoneEarliestTime', NULL, 'small') ?></td>
				<td><? time_select($f,$s,"starttime", NULL, 5, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), ($completedmode ? "DISABLED" : "")); ?></td>
			</tr>
			<tr>
				<td>&nbsp;&nbsp;Latest <?= help('Job_PhoneLatestTime', NULL, 'small') ?></td>
				<td><? time_select($f,$s,"endtime", NULL, 5, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), ($completedmode ? "DISABLED" : "")); ?></td>
			</tr>
			<tr>
				<td>Email a report when the job completes <?= help('Job_SendReport', NULL, 'small'); ?></td>
				<td><? NewFormItem($f,$s,"sendreport","checkbox",1, NULL, ($completedmode ? "DISABLED" : "")); ?>Report</td>
			</tr>
		</table>
		</div>

		</td>
	</tr>
	<? if($USER->authorize('sendphone')) { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Phone:</th>
		<td class="bottomBorder">

		<div id='displayphoneoptions'>
			<? if(!$submittedmode){ ?>
				<a href="#" onclick="displaySection('phone'); new getObj('sendphone').obj.checked=true; return false;">Click here</a> or select checkbox above.
			<?	} else { ?>
					 &nbsp;
			<?	}	?>
		</div>

		<div id='phoneoptions' style="display: none">
		<table border="0" cellpadding="2" cellspacing="0" width=100%>
			<tr>
				<td width="30%" valign="top">Message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
				<td style="white-space:nowrap;">
<?					NewFormItem($f, $s, "phoneradio", "radio", NULL, "select","id='phoneselect' " . ($submittedmode ? "DISABLED" : " onclick=\"if(this.checked == true) {hide('phonecreatemessage');show('phoneselectmessage'); show('phonemultilingualoption');}\"")); ?> Select a message&nbsp;
<? 					NewFormItem($f, $s, "phoneradio", "radio", NULL, "create","id='phonecreate' " . ($submittedmode ? "DISABLED" : " onclick=\"if(this.checked == true) {" . (($USER->authorize('sendmulti') && $JOBTYPE != 'repeating')?"toggletranslations('phone',false);automatictranslation('phone');":"") . "show('phonecreatemessage');hide('phoneselectmessage');hide('phonemultilingualoption'); }\""));	?> Create a text-to-speech message
				<div id='phoneselectmessage' style="display: block">
<?					message_select('phone',$f, $s,"phonemessageid", "id='phonemessageid'");?>
				</div>
				<div id='phonecreatemessage' style="white-space: nowrap;display: none">
					Type Your English Message
<?					if($USER->authorize('sendmulti') && $JOBTYPE != 'repeating') { ?>
					| <?  NewFormItem($f,$s,"phonetranslatecheck","checkbox",1, NULL,"id='phonetranslatecheck' " . ($submittedmode ? "DISABLED" : " onclick=\"automatictranslation('phone')\"")); ?>
					Automatically translate to other languages<table style="display: inline"><tr><td><?= help('Job_AutomaticallyTranslate',NULL,"small"); ?></td></tr></table>
<? } ?>
					<br />
					<table>
						<tr>
							<td><? NewFormItem($f,$s,"phonetextarea", "textarea", 50, 5,"id='phonetextarea' onkeyup=\"phonetranslationstate=false;\" " . ($submittedmode ? "DISABLED" : "")); ?></td>
							<td valign="bottom"><?=	button('Play', "previewlanguage('english',true,true)");?></td>
						</tr>
					</table>
					Voice:
					<? NewFormItem($f, $s, "voiceradio", "radio", NULL, "female","id='voiceradio_female' checked " . ($submittedmode ? "DISABLED" : "")); ?> Female
					<? NewFormItem($f, $s, "voiceradio", "radio", NULL, "male","id='voiceradio_male' " . ($submittedmode ? "DISABLED" : "")); ?> Male
					<br />
<?					if($USER->authorize('sendmulti') && $JOBTYPE != 'repeating') { ?>
					<table width="100%">
					<tr>
					<td style="white-space:nowrap;">
						<div id='phonetranslationsshow' style="white-space:nowrap;display: none">
							<? button_bar(button('Show Translations', "toggletranslations('phone',true);submitTranslations('phone');"));?>
						</div>
						<div id='phonetranslationshide' style="white-space:nowrap;display: none">
							<? button_bar(button('Hide Translations', "toggletranslations('phone',false);"),button('Refresh Translations', "submitTranslations('phone');"));?>							
						</div>
					</td>
					</tr>
					</table>
					<div id='phonetranslations' style="display: none">
						<table border="0" cellpadding="2" cellspacing="0" width="100%" style="empty-cells:show;">
<?
						foreach($ttslanguages as $ttslanguage) {
							$language = escapehtml(ucfirst($ttslanguage));
							$playaction = "previewlanguage('$language'," . (isset($voicearray["female"][strtolower($language)])?"true":"false") . "," . (isset($voicearray["male"][strtolower($language)])?"true":"false") . ")"
?>
							<tr>
								<td class="topBorder" valign="top" style="white-space:nowrap;"><? NewFormItem($f,$s,"phone_$language","checkbox",NULL, NULL,"id='phone_$language' " . ($submittedmode ? "DISABLED" : " onclick=\"translationlanguage('phone','$language')\"")); echo "&nbsp;" . $language . ": ";?>
								</td>
								<td class="topBorder" valign="top" ><div id='phonelock_<? echo $language?>'><img src="img/padlock.gif"></div><img src="img/pixel.gif" width="10" height="1"></td>
								<td class="topBorder" valign="top" style="white-space:nowrap;" width="100%">
									<table width="100%" style="table-layout:fixed;">
									<tr>
										<td>
										<div class="chop" id='phonetxt_<? echo $language?>'  onclick="langugaedetails('phone','<? echo $language;?>',true); return false;">&nbsp;</div>
										</td>
									</tr>
									</table>
									<div id='phoneexpandtxt_<? echo $language?>' style="display: none">
										<? NewFormItem($f,$s,"phoneexpand_$language", "textarea", 45, 3,"id='phoneexpand_$language'"); ?>
										<br />
										<? NewFormItem($f,$s,"phoneedit_$language","checkbox",1, NULL,"id='phoneedit_$language'" . ($submittedmode ? "DISABLED" : " onclick=\"editlanguage('phone','$language')\"")); ?> Override Translation 
										<table style="display: inline"><tr><td><?= help('Job_OverrideTranslation',NULL,"small"); ?></td></tr></table>
										<br /><br />
										<a href="#" onclick="submitRetranslation('phone','<? echo $language?>');return false;">Retranslation</a>
										<table style="display: inline"><tr><td><?= help('Job_Retranslation',NULL,"small"); ?></td></tr></table> <br />
										<? NewFormItem($f,$s,"phoneverify_$language", "textarea", 45, 3,"id='phoneverify_$language' disabled"); ?>
									</div>
								</td>
								<td class="topBorder" valign="top" style="white-space:nowrap;">
									<div id='phoneshow_<? echo $language?>' style="display: none">
										<? button_bar(button('Play',$playaction," "),button('Show', "langugaedetails('phone','$language',true); return false;"));?>
									</div>
									<div id='phonehide_<? echo $language?>' style="display: none">
										<? button_bar(button('Play',$playaction," "),button('Hide', "langugaedetails('phone','$language',false); return false;"));?>
									</div>
								</td>
							</tr>
<?						} // End of languages ?>
						</table>
						<div id='branding'>
						<div style="color: rgb(103, 103, 103);float: right;" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">Translation powered by<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="http://www.google.com/uds/css/small-logo.png"></span></div>
						</div>
					</div>
<? 					} // End of automatic translations ?>
				</div>
				</td>
			</tr>
		</table>
		</div>

		<div id='displayphonedetails' style="display: none"><a href="#"
			onclick="displaySection('phone', true); return false; ">Show advanced
		options</a></div>

		<div id='displayphonebasic' style="display: none"><a href="#"
			onclick="displaySection('phone', false); return false; ">Hide
		advanced options</a></div>

		<div id='phonedetails' style="display: none"><? if($USER->authorize('sendmulti')) { ?>
		<div id='phonemultilingualoption' style="display: none">
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
			<tr>
				<td width="30%">Multilingual message options <?= help('Job_MultilingualPhoneOption',NULL,"small"); ?></td>
				<td><? alternate('phone'); ?></td>
			</tr>
			</table>
		</div>
		<? } ?>
		<table border="0" cellpadding="2" cellspacing="0" width=100%>
			<tr>
				<td width="30%">Maximum attempts <?= help('Job_PhoneMaxAttempts', NULL, 'small')  ?></td>
				<td><?
				$max = first($ACCESS->getValue('callmax'), 1);
				NewFormItem($f,$s,"maxcallattempts","selectstart", NULL, NULL, ($completedmode ? "DISABLED" : ""));
				for($i = 1; $i <= $max; $i++)
				NewFormItem($f,$s,"maxcallattempts","selectoption",$i,$i);
				NewFormItem($f,$s,"maxcallattempts","selectend");
				?></td>
			</tr>


			<?
			if ($USER->authorize('setcallerid')) {
				if (getSystemSetting('_hascallback', false)) {
					?>
			<tr>
				<td><? NewFormItem($f, $s, "radiocallerid", "radio", null, "bydefault",($submittedmode ? "DISABLED" : "")); ?>
				Use default Caller ID</td>
				<td><? echo Phone::format(getSystemSetting('callerid')); ?></td>
			</tr>
			<tr>
				<td><? NewFormItem($f, $s, "radiocallerid", "radio", null, "byuser",($submittedmode ? "DISABLED" : "")); ?>
				Preferred Caller ID</td>
				<td><? NewFormItem($f,$s,"callerid","text", 20, 20, ($submittedmode ? "DISABLED" : "")); ?></td>
			</tr>
			<?
				} else {
					?>
			<tr>
				<td>Caller&nbsp;ID <?= help('Job_CallerID',NULL,"small"); ?></td>
				<td><? NewFormItem($f,$s,"callerid","text", 20, 20, ($submittedmode ? "DISABLED" : "")); ?></td>
			</tr>
			<?
				}
			}
			?>
			<tr>
				<td>Skip duplicate phone numbers <?=  help('Job_PhoneSkipDuplicates', NULL, 'small') ?></td>
				<td><? NewFormItem($f,$s,"skipduplicates","checkbox",1, NULL, ($submittedmode ? "DISABLED" : "")); ?>Skip
				Duplicates</td>
			</tr>

			<? if($USER->authorize('leavemessage')) { ?>
			<tr>
				<td>Allow call recipients to leave a message <?= help('Jobs_VoiceResponse', NULL, 'small') ?>
				</td>
				<td><? NewFormItem($f, $s, "leavemessage", "checkbox", 0, NULL, ($submittedmode ? "DISABLED" : "")); ?>
				Accept Voice Responses</td>
			</tr>
			<?
			}
			if ($USER->authorize("messageconfirmation")){
				?>
			<tr>
				<td>Allow message confirmation by recipients <?= help('Job_MessageConfirmation', NULL, 'small') ?>
				</td>
				<td><? NewFormItem($f, $s, "messageconfirmation", "checkbox", 0, NULL, ($submittedmode ? "DISABLED" : "")); ?>
				Request Message Confirmation</td>
			</tr>
			<?
			}
			?>
		</table>
		</div>

		</td>
	</tr>
	<? } ?>
	<? if($USER->authorize('sendemail')) { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Email:</th>
		<td class="bottomBorder">
		<div id='displayemailoptions'><?
		if(!$submittedmode){
			?> <a href="#"
			onclick="displaySection('email'); new getObj('sendemail').obj.checked=true; return false;">Click
		here</a> or select checkbox above. <?
		} else {
			?> &nbsp; <?
		}
		?></div>

		<div id='emailoptions' style="display: none">
		<table border="0" cellpadding="2" cellspacing="0" width=100%>
			<tr>
				<td width="30%" valign="top">Message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
				<td style="white-space:nowrap;">
<?					NewFormItem($f, $s, "emailradio", "radio", NULL, "select","id='emailselect' " . ($submittedmode ? "DISABLED" : " onclick=\"if(this.checked == true) {hide('emailcreatemessage');show('emailselectmessage'); show('emailmultilingualoption');}\"")); ?> Select a message&nbsp;
<? 					NewFormItem($f, $s, "emailradio", "radio", NULL, "create","id='emailcreate' " . ($submittedmode ? "DISABLED" : " onclick=\"if(this.checked == true) {checkemail();" . (($USER->authorize('sendmulti') && $JOBTYPE != 'repeating')?"toggletranslations('email',false);automatictranslation('email');":"") . "show('emailcreatemessage');hide('emailselectmessage');hide('emailmultilingualoption'); }\""));	?> Create a message
				<div id='emailselectmessage' style="display: block">
<?					message_select('email',$f, $s,"emailmessageid", "id='emailmessageid'");?>
				</div>
				<div id='emailcreatemessage' style="white-space: nowrap;display: none">
					<div>
					<table>			
						<tr>
						<td>From Name:</td><td> <? NewFormItem($f, $s, 'fromname', 'text', 25, 50,'id="fromname"'); ?></td>
						</tr>
						<tr>
						<td>From Email:</td><td> <? NewFormItem($f, $s, 'fromemail', 'text', 25, 50,'id="fromemail"'); ?></td>
						</tr>
						<tr>
						<td>Subject:</td><td><? NewFormItem($f, $s, 'emailsubject', 'text', 40, 50,'id="emailsubject"'); ?></td>
						</tr>
					</table>
					 </div>
					Type Your English Message
<?					if($USER->authorize('sendmulti') && $JOBTYPE != 'repeating') { ?>
					| <?  NewFormItem($f,$s,"emailtranslatecheck","checkbox",1, NULL,"id='emailtranslatecheck' " . ($submittedmode ? "DISABLED" : " onclick=\"automatictranslation('email')\"")); ?>
					Automatically translate to other languages<table style="display: inline"><tr><td><?= help('Job_AutomaticallyTranslate',NULL,"small"); ?></td></tr></table>
<? } ?>
					<br />
					<table>
						<tr>
							<td><? NewFormItem($f,$s,"emailtextarea", "textarea", 50, 5,"id='emailtextarea' onkeyup=\"emailtranslationstate=false;\" " . ($submittedmode ? "DISABLED" : "")); ?></td>
						</tr>
					</table>
<? 					if($USER->authorize('sendmulti') && $JOBTYPE != 'repeating') {  ?>						
					<div id='emailtranslationsshow' style="white-space:nowrap;display: none">
						<? button_bar(button('Show Translations', "toggletranslations('email',true);submitTranslations('email');"));?>
					</div>
					<div id='emailtranslationshide' style="white-space:nowrap;display: none">
						<? button_bar(button('Hide Translations', "toggletranslations('email',false);"),button('Refresh Translations', "submitTranslations('email');"));?>							
					</div>
					<div id='emailtranslations' style="display: none">

					
						<table border="0" cellpadding="2" cellspacing="0" width="100%" style="empty-cells:show;">
<?			
						foreach($alllanguages as $alllanguage) {
							$language = escapehtml(ucfirst($alllanguage));
							if($language == "English")
								continue;
?>							
							<tr>
								<td class="topBorder" valign="top" style="white-space:nowrap;"><? NewFormItem($f,$s,"email_$language","checkbox",NULL, NULL,"id='email_$language' " . ($submittedmode ? "DISABLED" : " onclick=\"translationlanguage('email','$language')\"")); echo "&nbsp;" . $language . ": ";?>
								</td>
								<td class="topBorder" valign="top" ><div id='emaillock_<? echo $language?>'><img src="img/padlock.gif"></div><img src="img/pixel.gif" width="10" height="1"></td>
								<td class="topBorder" valign="top" style="white-space:nowrap;" width="100%">
									<table width="100%" style="table-layout:fixed;">
									<tr>
										<td>
										<div class="chop" id='emailtxt_<? echo $language?>'  onclick="langugaedetails('email','<? echo $language;?>',true); return false;">&nbsp;</div>
										</td>
									</tr>
									</table>
									<div id='emailexpandtxt_<? echo $language?>' style="display: none">
										<? NewFormItem($f,$s,"emailexpand_$language", "textarea", 45, 3,"id='emailexpand_$language'"); ?>
										<br />
										<? NewFormItem($f,$s,"emailedit_$language","checkbox",1, NULL,"id='emailedit_$language'" . ($submittedmode ? "DISABLED" : " onclick=\"editlanguage('email','$language')\"")); ?> Override Translation 
										<table style="display: inline"><tr><td><?= help('Job_OverrideTranslation',NULL,"small"); ?></td></tr></table>
										<br /><br />
										<a href="#" onclick="submitRetranslation('email','<? echo $language?>');return false;">Retranslation</a>
										<table style="display: inline"><tr><td><?= help('Job_Retranslation',NULL,"small"); ?></td></tr></table> <br />
										<? NewFormItem($f,$s,"emailverify_$language", "textarea", 45, 3,"id='emailverify_$language' disabled"); ?>
									</div>
								</td>
								<td class="topBorder" valign="top" style="white-space:nowrap;">
									<div id='emailshow_<? echo $language?>' style="display: none">
										<? button_bar(button('Show', "langugaedetails('email','$language',true); return false;"));?>
									</div>
									<div id='emailhide_<? echo $language?>' style="display: none">
										<? button_bar(button('Hide', "langugaedetails('email','$language',false); return false;"));?>
									</div>
								</td>
							</tr>
<?						} // End of languages ?>
						</table>
						<div style="color: rgb(103, 103, 103);float: right;" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">Translation powered by<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="http://www.google.com/uds/css/small-logo.png"></span></div>
					</div>
<? 					} // End of automatic translations ?>
				
				</div>
				</td>
			</tr>
		</table>
		</div>

		<div id='displayemaildetails' style="display: none"><a href="#"
			onclick="displaySection('email', true); return false; ">Show advanced
		options</a></div>

		<div id='displayemailbasic' style="display: none"><a href="#"
			onclick="displaySection('email', false); return false; ">Hide
		advanced options</a></div>

		<div id='emaildetails' style="display: none">
		<div id='emailmultilingualoption' style="display: none">
<? if($USER->authorize('sendmulti')) { ?>
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
			<tr>
				<td width="30%">Multilingual message options <?= help('Job_MultilingualEmailOption',NULL,"small"); ?></td>
				<td><? alternate('email'); ?></td>
			</tr>
			</table>
<? } ?>
		</div>
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
				<td width="30%">Skip duplicate email addresses <?=  help('Job_EmailSkipDuplicates', NULL, 'small') ?></td>
				<td><? NewFormItem($f,$s,"skipemailduplicates","checkbox",1, NULL, ($submittedmode ? "DISABLED" : "")); ?>Skip
				Duplicates</td>
				</tr>
			</table>
		</div>
		</td>
	</tr>
	<? } ?>
	<? if($USER->authorize('sendprint')) { ?>
	<tr valign="top">
		<th align="right" valign="top" class="windowRowHeader">Print</th>
		<td>
		<div id='displayprintoptions'><?
		if(!$submittedmode){
			?> <a href="#"
			onclick="displaySection('print'); new getObj('sendprint').obj.checked=true; return false;">Click
		here</a> or select checkbox above. <?
		} else {
			?> &nbsp; <?
		}
		?></div>
		<div id='printoptions' style="display: none">
		<table border="0" cellpadding="2" cellspacing="0" width=100%>
			<tr>
				<td width="30%">Send printed letters <? print help('Job_PrintOptions', null, 'small'); ?></td>
				<td><? NewFormItem($f,$s,"sendprint","checkbox",NULL,NULL,"id='sendprint' " . ($submittedmode ? "DISABLED" : "")); ?>Print</td>
			</tr>
			<tr>
				<td>Default message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
				<td><? message_select('print', $f, $s, "printmessageid"); ?></td>
			</tr>
			<? if($USER->authorize('sendmulti')) { ?>
			<tr>
				<td>Multilingual message options <?= help('Job_MultilingualPrintOption',NULL,"small"); ?></td>
				<td><? alternate('print'); ?></td>
			</tr>
			<? } ?>
			<? if (0) { ?>
			<tr>
				<td colspan="2"><? NewFormItem($f,$s,"printall","radio",NULL,"1"); ?>
				Send to all valid addresses in this list</td>
			</tr>
			<tr>
				<td colspan="2"><? NewFormItem($f,$s,"printall","radio",NULL,"0"); ?>
				After job completes, print letters for anyone who was not contacted</td>
			</tr>
			<? } ?>
		</table>
		</div>
		</td>
	</tr>
	<? } ?>
	<? if($hassms && $USER->authorize('sendsms')) { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">SMS:</th>
		<td class="bottomBorder">
		<div id='displaysmsoptions'><?
		if(!$submittedmode){
			?> <a href="#"
			onclick="displaySection('sms'); new getObj('sendsms').obj.checked=true; return false;">Click
		here</a> or select checkbox above. <?
		} else {
			?> &nbsp; <?
		}
		?></div>
		<div id='smsoptions' style="display: none">
		<table border="0" cellpadding="2" cellspacing="0" width=100%>
			<tr>
				<td width="30%">Default message <?= help('Job_SMSDefaultMessage', NULL, 'small') ?></td>
				<td>
<?					NewFormItem($f, $s, "smsradio", "radio", NULL, "select","id='smsselect' " . ($submittedmode ? "DISABLED" : " onclick=\"if(this.checked == true) {hide('smscreatemessage');show('smsselectmessage');}\"")); ?> Select a message&nbsp;
<? 					NewFormItem($f, $s, "smsradio", "radio", NULL, "create","id='smscreate' " . ($submittedmode ? "DISABLED" : " onclick=\"if(this.checked == true) {show('smscreatemessage');hide('smsselectmessage'); }\""));	?> Create a message
				<div id='smsselectmessage' style="display: block">
					<? message_select('sms',$f, $s,"smsmessageid", "id='smsmessageid'"); ?>
				</div>
				<div id='smscreatemessage'><? NewFormItem($f,$s,"smstextarea", "textarea", 20, 3, 'id="bodytext" onkeydown="limit_chars(this);" onkeyup="limit_chars(this);"' . ($submittedmode ? " DISABLED " : "")); ?>
					<span id="charsleft"><?= 160 - strlen(GetFormData($f,$s,"smstextarea")) ?></span>
					characters remaining.
				</div>
				</td>
			</tr>
		</table>
		<div id='smsmultilingualoption'></sms>
		</div>
		</td>
	</tr>
	<? } ?>
</table>

<script language="javascript">
	var phonesubmitstate = false;
	var emailsubmitstate = false;
	var phonetranslationstate = false;
	var emailtranslationstate = false;
	var displaysettingsdetailsstate = 'visible';
	<? if ($hassettingsdetailerror) { ?>
		displaysettingsdetailsstate = 'hidden';
	<?}?>
	var displayphonedetailsstate = 'visible';
	<? if ($hasphonedetailerror) { ?>
		displayphonedetailsstate = 'hidden';
	<? } ?>
	var displayemaildetailsstate = 'visible';
	<? if ($hasemaildetailerror) { ?>
		displayemaildetailsstate = 'hidden';
	<? } ?>
	var jobtypetablestyle = new getObj("jobtypetable").obj.style.border;
	var jobtypeinfo = new Array();

	jobtypeinfo[""] = new Array("", "");
	<?
	foreach($infojobtypes as $infojobtype){
		$info = escapehtml($infojobtype->info);
		$info = str_replace(array("\r\n","\n","\r"),"<br>",$info);
	?>
		jobtypeinfo[<?=$infojobtype->id?>] = new Array("<?=$infojobtype->systempriority?>", "<?=$info?>");
	<? } ?>
	display_jobtype_info(new getObj('jobtypeid').obj.value);

	var typeischecked = false;

	<?
	if($hassms && $USER->authorize('sendsms')) {
	?>
		var smsmessageobj = new getObj('smsmessageid').obj;
		if(smsmessageobj && smsmessageobj.value != ""){
				new getObj('sendsms').obj.checked = true;
		}
		var smsmessagedropdown = new getObj('smsmessageid').obj;
		if(smsmessagedropdown.value != ""){
			hide('smscreatemessage');
		}
		if(isCheckboxChecked('sendsms')){
			typeischecked = true;
			show('smsoptions');
			hide('displaysmsoptions');
			hide('displaysmsoptions');
		}
	<?}?>

	var phonemessageobj = new getObj('phonemessageid').obj;
	if(phonemessageobj  && phonemessageobj .value != ""){
		new getObj('sendphone').obj.checked = true;
	}
	var emailmessageobj = new getObj('emailmessageid').obj;
	if(emailmessageobj && emailmessageobj .value != ""){
		new getObj('sendemail').obj.checked = true;
	}
	if(isCheckboxChecked('sendphone')){
		typeischecked = true;
		show('phoneoptions');
		hide('displayphoneoptions');

		<?
		if ($_SESSION['jobid'] != null) {
			$diffvalues = $job->compareWithDefaults();
		}
		if (((isset($diffvalues['phonelang']) && $job->getSetting('jobcreatedphone') === 0)  ||
			isset($diffvalues['maxcallattempts']) ||
			isset($diffvalues['callerid']) ||
			isset($diffvalues['radiocallerid']) ||
			isset($diffvalues['skipduplicates']) ||
			isset($diffvalues['leavemessage']) ||
			isset($diffvalues['messageconfirmation'])) ||
			(($_SESSION['jobid'] != null) && ($job->status == "complete" || $job->status == "cancelled" || $job->status == "cancelling"))) {
		?>
			displayphonedetailsstate = 'hidden';
		<? } ?>
		if (displayphonedetailsstate == 'visible') {
				show('displayphonedetails');
		} else {
			show('phonedetails');
			show('displayphonebasic');
		}
	}
	if(isCheckboxChecked('sendemail')){
		typeischecked = true;
		show('emailoptions');
		hide('displayemailoptions');

		<?
		if ($_SESSION['jobid'] != null) {
			$diffvalues = $job->compareWithDefaults();
		}
		if (((isset($diffvalues['emaillang']) && $job->getSetting('jobcreatedemail') === 0) ||
			isset($diffvalues['skipemailduplicates'])) ||
			(($_SESSION['jobid'] != null) && ($job->status == "complete" || $job->status == "cancelled" || $job->status == "cancelling"))) {
		?>
			displayemaildetailsstate = 'hidden';
		<? } ?>
		if (displayemaildetailsstate == 'visible') {
			show('displayemaildetails');
		} else {
			show('emaildetails');
			show('displayemailbasic');
		}
	}
	if(	typeischecked == true ){
		show('settings');
		<?
		if ($_SESSION['jobid'] != null) {
			$diffvalues = $job->compareWithDefaults();
		}
		if ((isset($diffvalues['startdate']) ||
			isset($diffvalues['enddate']) ||
			isset($diffvalues['starttime']) ||
			isset($diffvalues['endtime']) ||
			isset($diffvalues['sendreport'])) ||
			(($_SESSION['jobid'] != null) && ($job->status == "complete" || $job->status == "cancelled" || $job->status == "cancelling"))) {
		?>
			displaysettingsdetailsstate = 'hidden';
		<? } ?>
		if (displaysettingsdetailsstate == 'visible') {
			show('displaysettingsdetails');
		} else {
			show('settingsdetails');
			show('displaysettingsbasic');
		}
	}

	// Loading List View
	if(isCheckboxChecked('listradio_single')){
		show('singlelist');hide('multilist');
	} else {
		hide('singlelist');show('multilist');
	}
	
	function limit_chars(field) {
		if (field.value.length > 160)
			field.value = field.value.substring(0,160);
		var status = new getObj('charsleft');
		var remaining = 160 - field.value.length;
		if (remaining <= 0)
			status.obj.innerHTML="<b style='color:red;'>0</b>";
		else if (remaining <= 20)
			status.obj.innerHTML="<b style='color:orange;'>" + remaining + "</b>";
		else
			status.obj.innerHTML=remaining;
	}
/*
	Function to show the date in page text
*/
function showDate(enddate, days) {
	days = days -1; // a 1 day offset means "the same day", a 2 day offset means "tomorrow", etc.
	enddate = new Date (new Date(enddate).valueOf() + (86400000 * days));
	var strdate = new getObj('job_end_date');
	strdate.obj.textContent = 'You have scheduled this job to end on ' + formatDate(enddate);
}

/*
	Function to format the date string
*/
function formatDate(Ob) {
	var months = new String('JanFebMarAprMayJunJulAugSepOctNovDec');
	with (Ob) {
		var month = 3 * getMonth()
    	return months.substring(month, month + 3) + " " + getDate() + ", " + getFullYear()
	} // TODO - add on change handler to the start date text box
}

function displaySection(section, details){
	switch(section){
		case 'phone':
			show('phoneoptions');
			if (details) {
				show('phonedetails');
				hide('displayphonedetails');
				show('displayphonebasic');
			} else {
				hide('phonedetails');
				show('displayphonedetails');
				hide('displayphonebasic');
			}
			hide('displayphoneoptions');
			break;
		case 'email':
			show('emailoptions');
			if (details) {
				show('emaildetails');
				hide('displayemaildetails');
				show('displayemailbasic');
			} else {
				hide('emaildetails');
				show('displayemaildetails');
				hide('displayemailbasic');
			}
			hide('displayemailoptions');
			break;
		case 'print':
			show('printoptions');
			hide('displayprintoptions');
			break;
		case 'sms':
			show('smsoptions');
			hide('displaysmsoptions');
			break;
		case 'settings':
			if (details) {
				displaysettingsdetailsstate = 'hidden';
				show('settingsdetails');
				show('displaysettingsbasic');
				hide('displaysettingsdetails');
			} else {
				displaysettingsdetailsstate = 'visible';
				hide('settingsdetails');
				hide('displaysettingsbasic');
				show('displaysettingsdetails');
			}
			break;
	}
	show('settings');

	if (section != 'settings') {
		if (displaysettingsdetailsstate == 'hidden') {
			show('displaysettingsbasic');
			hide('displaysettingsdetails');
		} else {
			displaysettingsdetailsstate = 'visible';
			hide('displaysettingsbasic');
			show('displaysettingsdetails');
		}
	}
}

function hideSection(section){
	switch(section){
		case 'phone':
			hide('phoneoptions');
			hide('displayphonedetails');
			hide('phonedetails');
			hide('displayphonebasic');
			show('displayphoneoptions');
			break;
		case 'email':
			hide('emailoptions');
			hide('displayemaildetails');
			hide('emaildetails');
			hide('displayemailbasic');
			show('displayemailoptions');
			break;
		case 'print':
			hide('printoptions');
			show('displayprintoptions');
			break;
		case 'sms':
			hide('smsoptions');
			show('displaysmsoptions');
			break;
	}

	var typeischecked = false;
	if(isCheckboxChecked('sendphone')){
		typeischecked = true;
		hide('displayphoneoptions');
	}
	if(isCheckboxChecked('sendemail')){
		typeischecked = true;
		hide('displayemailoptions');
	}
	if(isCheckboxChecked('sendsms')){
		typeischecked = true;
		hide('displaysmsoptions');
	}

	if(typeischecked == false){
		hide('settings');
		hide('displaysettingsdetails');
		hide('settingsdetails');
		hide('displaysettingsbasic');
		displaysettingsdetailsstate = 'visible';
	}
}

function clickIcon(section){
	var checkbox = new getObj('send' + section).obj;
	checkbox.checked = !checkbox.checked;

	if(checkbox.checked){
		displaySection(section);
	} else {
		hideSection(section);
	}
}


function display_jobtype_info(value){
	new getObj("jobtypeinfo").obj.innerHTML = jobtypeinfo[value][1];
	var jobtypetable = new getObj("jobtypetable").obj;
	if(jobtypeinfo[value][0] == 1){
		jobtypetable.style.border="3px double red";
	} else {
		jobtypetable.style.border=jobtypetablestyle;
	}
<?
	if(getSystemSetting('_dmmethod', "asp")=='hybrid'){
?>
		var addinfo = new getObj("addinfo").obj;

		if(jobtypeinfo[value][0] == 1){
			addinfo.innerHTML = "High capacity emergency call routing";

		} else {
			addinfo.innerHTML = "Standard call routing";
		}
	if(value == ""){
		new getObj("addinfo").obj.innerHTML ="";
	}
<?
	}
?>
}

function previewlanguage(language,female,male) {
	var voice = 'default';
	if(isCheckboxChecked('voiceradio_male') && male) {
		voice = 'male';
	} else if(female) {
		voice = 'female';
	}
	var text;
	if(language == 'english')
		text = new getObj('phonetextarea').obj;
	else
		text = new getObj('phoneexpand_' + language).obj;
	var encodedtext=encodeURIComponent(text.value);

	popup('previewmessage.php?text=' + encodedtext + '&language=' + language +'&gender=' + voice, 400, 400);
}
function checkemail() {
	fromemail = new getObj('fromemail').obj.value;
	if(fromemail == "") {
		alert("To be able to create and send a email message please save this job and set your email address on the accout page");
	}
}

//Loading Message View
var types=Array('phone','email','sms');
for(var i=0;i<3;i++){
	if(isCheckboxChecked(types[i] + 'select')) {
		hide(types[i] + 'createmessage');
		show(types[i] + 'selectmessage');
		show(types[i] + 'multilingualoption');
	} else {
		show(types[i] + 'createmessage');
		hide(types[i] + 'selectmessage');
		hide(types[i] + 'multilingualoption');
	}
}

</script>

<? // These scripts contol the translation ?>
<? if($USER->authorize('sendmulti') && $JOBTYPE != 'repeating') { ?>
<script src='script/json2.js'></script>
<script>
<?
$languagestring = "";
foreach($ttslanguages as $ttslanguage) { $languagestring .= ",'" . escapehtml(ucfirst($ttslanguage)) . "'";}
$languagestring = substr($languagestring,1);
?>
var phonelanguages=new Array(<? echo $languagestring; ?>);
<?
$languagestring = "";
foreach($alllanguages as $alllanguage) { 
	if($alllanguage == "English")
		continue;
	$languagestring .= ",'" . escapehtml(ucfirst($alllanguage)) . "'";}
$languagestring = substr($languagestring,1);
?>
var emaillanguages=new Array(<? echo $languagestring; ?>);
var googleready = false;
var cancelgoogle = false;
var callbacksection = 'phone';

// Loading the translation setup
for(var j=0;j<2;j++){	
	var type = types[j];
	if(isCheckboxChecked(type + 'create')) {
		var checked = false;
		show(type + 'translationsshow');
		languages = phonelanguages;
		if(type == 'email') 
			languages = emaillanguages;		
		for (i = 0; i < languages.length; i++) {
			var language = languages[i];
			if(isCheckboxChecked(type + '_' + language)){
				show(type + 'txt_' + language);
				show(type + 'show_' + language);
				checked = true;
			} else {
				hide(type + 'txt_' + language);
				hide(type + 'show_' + language);
			}
			editlanguage(type,language);
			hide(type + 'expandtxt_' + language);
			hide(type + 'hide_' + language);
			if(!isCheckboxChecked(type + 'edit_' + language)){
				var x = new getObj(type + 'expand_' + language);
				x.obj.disabled = true;
			}
		}	
		if(!checked) {
			var x = new getObj(type + 'translatecheck');
			x.obj.checked = false;
			hide(type + 'translationsshow');
		}
	}
}

<?
/*
 * If The Automatic translation check is clicked or the user switch from select a message to create a message 
 * If The checkbox is selected all the languages should be selected too.
 * If a user has unselected all languages the phonetranslatecheck is unselected to avoid both show and hide translation 
 * to show at the same time the show('phonetranslationsshow'); has to be conditional
 *
 */
?>
function automatictranslation(section){
	languages = phonelanguages;
	if(section == 'email') 
		languages = emaillanguages;	
	
	if(isCheckboxChecked(section + 'translatecheck')){
		for (i = 0; i < languages.length; i++) {
			var language = languages[i]
			var x = new getObj(section + '_' + language);
			show(section + 'txt_' + language);
			if(!x.obj.disabled) {
				x.obj.checked = true;
				show(section + 'show_' + language);
			}
			editlanguage(section,language);
			hide(section + 'expandtxt_' + language);
			hide(section + 'hide_' + language);
		}
		var basic = new getObj(section + 'translationshide').obj;
		if(basic.style.display != "block")
			show(section + 'translationsshow');
	} else {
		for (i = 0; i < languages.length; i++) {
			var language = languages[i];
			var x = new getObj(section + '_' + language);
			x.obj.checked = false;
			hide(section + 'txt_' + language);
			hide(section + 'show_' + language);
			hide(section + 'expandtxt_' + language);
			hide(section + 'hide_' + language);
		}
		hide(section + 'translationsshow');
		hide(section + 'translationshide');
		hide(section + 'translations');
	}
}
<? // Show Translation options ?>
function toggletranslations(section,showtranslation){
	if (showtranslation) {
		show(section + 'translations');
		hide(section + 'translationsshow');
		show(section + 'translationshide');
	} else {
		hide(section + 'translations');
		show(section + 'translationsshow');
		hide(section + 'translationshide');
	}
	return false;
}

<? // If language checkbox is selected ?>
function translationlanguage(section,language){
	var checked = false;
	languages = phonelanguages;
	if(section == 'email') 
		languages = emaillanguages;	
	
	for (i = 0; i < languages.length; i++) {
		if(isCheckboxChecked(section + '_' + languages[i])){
			checked = true;
		}
	}
	if(!checked) {
		var x = new getObj(section + 'translatecheck');
		x.obj.checked = false;
	}
	if (isCheckboxChecked(section + '_' + language)){
		switch(section) {
			case 'phone': phonetranslationstate = false; break;
			case 'email': emailtranslationstate = false; break;
		}
		setChecked(section + 'translatecheck');
		show(section + 'txt_' + language);
		show(section + 'show_' + language);
	} else {
		hide(section + 'txt_' + language);
		hide(section + 'show_' + language);
		var tr = new getObj(section + 'txt_' + language).obj;
		tr.innerHTML = "&nbsp;";
		var trexpand = new getObj(section + 'expand_' + language).obj;
		trexpand.value = "";
		var edit = new getObj(section + 'edit_' + language).obj;
		edit.checked = false;
	}
	editlanguage(section,language); <? // To show or hide the lock symbol ?>
	hide(section + 'expandtxt_' + language);
	hide(section + 'hide_' + language);
}

<? //If language details is clicked ?>
function langugaedetails(section,language, details){
	if(details){
		hide(section + 'txt_' + language);
		show(section + 'expandtxt_' + language);
		show(section + 'hide_' + language);
		hide(section + 'show_' + language);
		var retranslation = new getObj(section + 'verify_' + language).obj;
		retranslation.value = "";
	} else {
		show(section + 'txt_' + language);
		hide(section + 'expandtxt_' + language);
		hide(section + 'hide_' + language);
		show(section + 'show_' + language);
		<? // If the translation is edited the text will need to be copied when the language box is collapsed ?>
		if(isCheckboxChecked(section + 'edit_' + language)){
			var tr = new getObj(section + 'txt_' + language).obj;
			var trexpand = new getObj(section + 'expand_' + language).obj;
			if(trexpand.value != "") {
  				tr.innerHTML = trexpand.value;
			} else {
				tr.innerHTML = "&nbsp;" <? //May want to warn about this ?>
			}	
		}
	}
}
function editlanguage(section,language) {
	var textbox = new getObj(section + 'expand_' + language).obj;
	textbox.disabled = !isCheckboxChecked(section + 'edit_' + language);
	if(isCheckboxChecked(section + '_' + language) && isCheckboxChecked(section + 'edit_' + language)){
		show(section + 'lock_' + language);
	} else {
		hide(section + 'lock_' + language);
		switch(section) {
			case 'phone': phonetranslationstate = false; break;
			case 'email': emailtranslationstate = false; break;
		}	 
	}
}

function setTranslations (html, langstring) {
	section = callbacksection;
	
	var trlanguages = langstring.split(";");
	response = JSON.parse(html, function (key, value) {	return value;}); //See documentation at http://www.json.org/js.html on how this function can be used
	result = response.responseData;
	if (response.responseStatus != 200){	
		if (phonesubmitstate || emailsubmitstate){	
			var status = new getObj('translationstatus').obj;
			status.innerHTML = "Unable to generate translations<br />Please read the help for more information";
		}		
		return;
	}
	if(result instanceof Array) {
		for ( i in result) {
			var language = trlanguages.shift();		
			if (result[i].responseStatus == 200){
				var tr = new getObj(section + 'txt_' + language).obj;
				var trexpand = new getObj(section + 'expand_' + language).obj;
				var retranslation = new getObj(section + 'verify_' + language).obj;
				retranslation.value = "";		
				tr.innerHTML = result[i].responseData.translatedText;
				trexpand.value = result[i].responseData.translatedText;
				switch(section) {
					case 'phone': phonetranslationstate = true; break;
					case 'email': emailtranslationstate = true; break;
				}
			}
		}
	} else {
		var tr = new getObj(section + 'txt_' + trlanguages[0]).obj;
		var trexpand = new getObj(section + 'expand_' + trlanguages[0]).obj;
		tr.innerHTML = result.translatedText;
		trexpand.value = result.translatedText;
		
		var retranslation = new getObj(section + 'verify_' + trlanguages[0]).obj;
		retranslation.value = "";
		switch(section) {
			case 'phone': phonetranslationstate = true; break;
			case 'email': emailtranslationstate = true; break;
		}
	}
	if(section == 'phone' && phonesubmitstate && phonetranslationstate) {
		if(isCheckboxChecked('sendemail') && isCheckboxChecked('emailcreate') && isCheckboxChecked('emailtranslatecheck') && !emailtranslationstate) {			
			emailsubmitstate = true;
			phonesubmitstate = false;
			submitTranslations('email');
			return;
		} 
		submitForm('<? echo $f; ?>','send');
	} 
	if(section == 'email' && emailsubmitstate && emailtranslationstate) {		
		submitForm('<? echo $f; ?>','send');
	} 
}

function submitTranslations(section) {
	if((section == 'phone' && phonetranslationstate) || (section == 'email' && emailtranslationstate)){
		return; //There are no changes to the text or a new language has not been added
	}
	
    var text = new getObj(section + 'textarea').obj.value;
	if(text == "")
		return;
	if(text != text.substring(0, 2000)){
		text = text.substring(0, 2000);
		alert("The message is too long. Only the first 2000 characters are submitted for translation.");
	}

	languages = phonelanguages;
	if(section == 'email') 
		languages = emaillanguages;	
	
	var serialized = [];
	var trlanguages = [];
	for (l in languages) {
		if (isCheckboxChecked(section + '_' + languages[l])){
			if(isCheckboxChecked(section + 'edit_' + languages[l])) {
				var tr = new getObj(section + 'txt_' + languages[l]).obj;
				var trexpand = new getObj(section + 'expand_' + languages[l]).obj;
				if(trexpand.value != "") {
	  				tr.innerHTML = trexpand.value;
				} else {
					tr.innerHTML = "&nbsp;" <? //May want to warn about this ?>
				}
			} else {
				serialized.push(encodeURIComponent(languages[l]));
			}
		}
	}
	
	var seriallang = serialized.join(";");
	callbacksection = section;
	ajax('translate.php',"english=" + encodeURIComponent(text) + "&languages=" + seriallang, setTranslations, seriallang);
	return false;
}

function setRetranslation (html, language) {
	response = JSON.parse(html, function (key, value) {	return value;}); //See documentation at http://www.json.org/js.html on how this function can be used
	result = response.responseData;
	if (response.responseStatus != 200){	
		return;
	}
	var retranslation = new getObj(callbacksection + 'verify_' + language).obj;
	retranslation.value = result.translatedText;
}

function submitRetranslation(section,language) {
	var text = new getObj(section + 'expand_' + language).obj.value;
	if(text == "")
		return;
	if(text != text.substring(0, 2000)){
		text = text.substring(0, 2000);
		alert("The message is too long. Only the first 2000 characters are submitted for translation.");
	}
	var urllang = encodeURIComponent(language);
	callbacksection = section;
	ajax('translate.php',"text=" + encodeURIComponent(text) + "&language=" + urllang, setRetranslation, urllang);
	return false;
}
function enablesection(section) {
	languages = phonelanguages;
	if(section == 'email') 
		languages = emaillanguages;	
	
	for (i = 0; i < languages.length; i++) {
		var trexpand = new getObj(section + 'expand_' + languages[i]).obj;
		trexpand.disabled = false;
	}	
}
function sendjobconfirm() {
	phonesubmitstate = false;
	emailsubmitstate = false;
	scroll(0,0);
	enablesection('phone');
	enablesection('email');
	
	if(isCheckboxChecked('sendphone') && isCheckboxChecked('phonecreate') && isCheckboxChecked('phonetranslatecheck') && !phonetranslationstate) {
		phonesubmitstate = true;
		submitTranslations('phone');
	}
	if(!phonesubmitstate && isCheckboxChecked('sendemail') && isCheckboxChecked('emailcreate') && isCheckboxChecked('emailtranslatecheck') && !emailtranslationstate) {
		emailsubmitstate = true;
		submitTranslations('email');
	}
	if(!phonesubmitstate && !emailsubmitstate) {
		submitForm('<? echo $f; ?>','send');
	} else {
		show('translationstatus');
		var status = new getObj('translationstatus').obj;
		status.innerHTML = "Generating Translations<br /><img src=\"img/progressbar.gif?date=" + <?= time() ?> + "\">";
	}
}
</script>

<? } // End of Translation - This block will be removed if it is a repeating job or sendmulti is not enabled   ?>
<script SRC="script/calendar.js"></script>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>
