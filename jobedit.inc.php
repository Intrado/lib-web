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
$queryresult = Query("SELECT DISTINCT l.name FROM language l INNER JOIN ttsvoice t ON t.language = l.name and l.name <> 'english'");
$languagearray = array();
while($row = DBGetRow($queryresult)){		
	$languagearray[htmlentities(strtolower($row[0]))] = NULL;
} 

//Get Selected languages
if($jobid && $job->getSetting('translationmessage')){
	$queryresult = Query("SELECT j.language, m.id as messageid FROM joblanguage j, message m where j.messageid = m.id and j.jobid=$jobid and m.deleted=1");
	while($row = DBGetRow($queryresult)){		
		$languagearray[htmlentities(strtolower($row[0]))] = $row[1];
	}
}

$voicearray = array();
$voices = DBFindMany("Voice","from ttsvoice");
foreach ($voices as $voice) {
	$voicearray[$voice->gender][$voice->language] = $voice->id;
}

$peoplelists = QuickQueryList("select id, name, (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name", true);


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
		SetRequired($f, $s, "smsmessagetxt", GetFormData($f, $s, 'sendsms') && GetFormData($f, $s, 'smsmessageid') == "");
		SetRequired($f, $s, "phonetextarea", GetFormData($f, $s, 'sendphone') && GetFormData($f, $s, 'phonemessageid') == "");
		SetRequired($f, $s, "listid", 1);
		if(GetFormData($f, $s, "listradio") == "single") {
			SetRequired($f, $s, "listid", 1);
			SetRequired($f, $s, "listids", 0);
		} else {
			SetRequired($f, $s, "listid", 0);
			SetRequired($f, $s, "listids", 1);
		}
		
		//do check

		$sendphone = GetFormData($f, $s, "sendphone");
		$sendemail = GetFormData($f, $s, "sendemail");
		$sendsms = getSystemSetting("_hassms", false) ? GetFormData($f, $s, "sendsms") : 0;

		$name = trim(GetFormData($f,$s,"name"));
		if ( empty($name) ) {
			PutFormData($f,$s,"name",'',"text",1,$JOBTYPE == "repeating" ? 30: 50,true);
		}

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
		} else if (QuickQuery("select id from job where deleted = 0 and name = '" . DBsafe($name) . "' and userid = $USER->id and status in ('new','scheduled','processing','procactive','active','repeating') and id != " . ( 0+ $_SESSION['jobid']))) {
			error('A job named \'' . $name . '\' already exists');
		} else if (GetFormData($f,$s,"callerid") != "" && strlen(Phone::parse(GetFormData($f,$s,"callerid"))) != 10) {
			$hasphonedetailerror = true;
			error('The Caller ID must be exactly 10 digits long (including area code)');
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
				// If this is a phonemessage and no message was selected the message is a translation message and the phonetextarea is requerd to be fuild in. 
				if(GetFormData($f, $s, "sendphone") && GetFormData($f, $s, "messageselect") == "create"){
					$themessageid = null;
					// If this Message was created in job editor we are free to edit the message, otherwise we have to create a new message
					if($job->getSetting('translationmessage')) {
						if( $job->phonemessageid ) {
							$themessageid = $job->phonemessageid;
							//Delete the part(s) of the message 
							QuickUpdate("delete from messagepart where messageid=$themessageid");
						}
					} else {
						if($job->id)
							QuickUpdate("delete from joblanguage where jobid=" . $job->id);  // If translation mode switched we need to rease the previous joblanguage assosiations			
					}
					$job->setSetting('translationmessage', 1); // Tell the job that this message was created here
					$newphonemessage = new Message($themessageid);	
					$newphonemessage->userid = $USER->id;
					$newphonemessage->type = 'phone';
					$newphonemessage->name = GetFormData($f, $s,'name');
					$newphonemessage->description = "Translated message " . date(" M j, Y g:i:s", strtotime("now"));
					$newphonemessage->deleted = 1;
					$newphonemessage->update();
					
					$part = new MessagePart();
					$part->messageid = $newphonemessage->id;
					$part->type="T";
					$part->voiceid = $voicearray[GetFormData($f, $s, 'voiceselect')]["english"];
					$part->txt = GetFormData($f, $s, 'phonetextarea');
					$part->sequence = 0;
					$part->create();	
					//Do a putform on message select so if there is an error later on, another message does not get created
					PutFormData($f, $s, "phonemessageid", $newphonemessage->id, 'number', 'nomin', 'nomax');	
				} else {
					if($job->getSetting('translationmessage') && $job->id) {
						QuickUpdate("delete joblanguage jl, message ms, messagepart mp
											FROM joblanguage jl, message ms, messagepart mp 
											where 
											jl.jobid=" . $job->id . " and 
											jl.messageid = ms.id and 
											jl.messageid = mp.messageid");
						
						if( $job->phonemessageid ) {
							QuickUpdate("delete message ms, messagepart mp FROM message ms, messagepart mp
											 where 
											 ms.id=" . $job->phonemessageid . " and 
											 mp.messageid = ms.id");
						}
					}	
					$job->setSetting('translationmessage', 0);
				}
				
				
					
				if($hassms && $USER->authorize('sendsms') && GetFormData($f, $s, "sendsms") && GetFormData($f, $s, 'smsmessageid') == "" ){
					$themessageid = null;
					// If this Message was create in job editor we are free to edit the message, otherwise we have to create a new message
					if($job->getSetting('jobcreatedsms')) {
						$themessageid = $job->smsmessageid;
						//update the parts
						QuickUpdate("delete from messagepart where messageid=$themessageid");
					} else {
						$job->setSetting('jobcreatedsms', 1); // Tell the job that this message was created here
					}		
					$newsmsmessage = new Message($themessageid);
					$newsmsmessage->userid = $USER->id;
					$newsmsmessage->type = 'sms';
					$newsmsmessage->name = GetFormData($f, $s,'name');
					$newsmsmessage->description = "SMS Message " . date(" M j, Y g:i:s", strtotime("now"));
					$newsmsmessage->deleted = 1;
					$newsmsmessage->update();
					
					$part = new MessagePart();
					$part->messageid = $newsmsmessage->id;
					$part->type="T";
					$part->txt = GetFormData($f, $s, 'smsmessagetxt');
					$part->sequence = 0;
					$part->create();	
					
					//Do a putform on message select so if there is an error later on, another message does not get created
					PutFormData($f, $s, 'smsmessageid', $newsmsmessage->id, 'number', 'nomin', 'nomax');
				}
				
				QuickUpdate("DELETE FROM joblist WHERE jobid=$job->id");				
				if(GetFormData($f, $s, "listradio") == "single") {
					$job->listid = GetFormData($f, $s, "listid");
				} else {
					$batchvalues = array();
					$listids = GetFormData($f,$s,'listids');
					$job->listid = array_shift($listids);
					foreach($listids as $id) {
						$values = "($job->id,". DBSafe($id) . ")";
						$batchvalues[] = $values;
					}
					if(!empty($batchvalues)){
						$sql = "INSERT INTO joblist (jobid,listid) VALUES ";
						$sql .= implode(",",$batchvalues);
						QuickUpdate($sql);
					}					
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
			if ($job->getSetting('translationmessage') ) {
				foreach($languagearray as $language => $messageid) {
					if(GetFormData($f, $s, "translate_$language")){
						$part = NULL;
						$joblanguage = NULL;
					
						$message = new Message($messageid);	
						if($messageid != NULL) {
							 // if messageid is not null there should be an existing message and joblanguage
							$part = DBFind("MessagePart","from messagepart where messageid=" . $message->id ." and sequence=0 and type='T'");
							$joblanguage = DBFind("JobLanguage","from joblanguage where jobid=" . $job->id . " and messageid= " . $message->id);
						}
						$message->userid = $USER->id;
						$message->type = 'phone';
						$message->name = GetFormData($f, $s,'name') . "_$language";
						$message->description = "Translated message " . date(" M j, Y g:i:s", strtotime("now"));
						$message->deleted = 1;
						$message->update();
						
						if (!$joblanguage) {  
							$joblanguage = new Joblanguage();	
						}
						$joblanguage->jobid = $job->id;
						$joblanguage->messageid = $message->id;
						$joblanguage->type = 'phone';
						$joblanguage->language = $language;
						$joblanguage->update();
						
						if(!$part) {
							$part = new MessagePart();
						}
						$part->messageid = $message->id;
						$part->type="T";
						
						$langstr = strtolower($language);
						if (GetFormData($f, $s, 'voiceselect') == "female") {
							if(isset($voicearray['female'][$langstr])){
								$part->voiceid = $voicearray['female'][$langstr];
							} else if(isset($voicearray['male'][$langstr])){
								$part->voiceid = $voicearray['male'][$langstr];					
							} else {
								error_log("Warning no voice found for $langstr");
							}
						} else {
							if(isset($voicearray['male'][$langstr])){
								$part->voiceid = $voicearray['male'][$langstr];
							} else if(isset($voicearray['female'][$langstr])){
								$part->voiceid = $voicearray['female'][$langstr];					
							} else {
								error_log("Warning no voice found for $langstr");
							}
						}
							
						$part->txt = GetFormData($f, $s, "translationtextexpand_" . $language);
						$part->sequence = 0;
						$part->update();			
					} else {
						// delete message if exist
						if($messageid){
							QuickUpdate("delete from joblanguage where messageid=$messageid");
							QuickUpdate("delete from message where id=$messageid");
							QuickUpdate("delete from messagepart where messageid=$messageid");
						}
					}					
				}
			} elseif($USER->authorize('sendmulti')) {
				foreach (array("phone","email","print") as $type) {
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
	array("listid","number","nomin","nomax",false),   // Set required if single list is selected but set listid required to false here.
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
	
	$selectedlists = QuickQueryList("select listid from joblist where jobid=$job->id", false);	
	PutFormData($f,$s,"listradio",empty($selectedlists)?"single":"multi");
	if($job->listid) {
		$selectedlists[] = $job->listid;
	}
	PutFormData($f,$s,"listids",$selectedlists,"array",array_keys($peoplelists));
		
	PutFormData($f,$s,"translatecheck",1,"bool",0,1);
	PutFormData($f,$s,"voiceselect",1);
	
	PutFormData($f,$s,"messageselect","create");
	PutFormData($f,$s,"phonetextarea","","text");	
	if($job->getSetting('translationmessage')) {
		if($phonemessage = DBFind("Message","from message where id='$job->phonemessageid' and deleted=1 and type='phone'")) {
			$parts = DBFindMany("MessagePart","from messagepart where messageid=$phonemessage->id order by sequence");
			$body = $phonemessage->format($parts);
			PutFormData($f,$s,"phonetextarea",$body,'text');
		}
	} else if ($jobid != NULL) { 
		PutFormData($f,$s,"messageselect","select");
	}


	foreach($languagearray as $language => $messageid) {		
		$messagefound = false;
		if($messageid) {
			$translationmessage = DBFind("Message","from message where id='$messageid' and deleted=1 and type='phone'");	
			if($translationmessage != NULL) {				
				$parts = DBFindMany("MessagePart","from messagepart where messageid=$messageid order by sequence");
				$body = $translationmessage->format($parts);
				PutFormData($f,$s,"translationtext_$language",$body,"text","nomin","nomax",false);
				PutFormData($f,$s,"translationtextexpand_$language",$body,"text","nomin","nomax",false);
				PutFormData($f,$s,"retranslationtext_$language","retranslation","text","nomin","nomax",false);
				PutFormData($f,$s,"translate_$language",1,"bool",0,1);
				$messagefound = true;
			} 
		} 
		if(!$messagefound) {
				PutFormData($f,$s,"translationtext_$language","empty translation first box","text","nomin","nomax",false);
				PutFormData($f,$s,"translationtextexpand_$language","empty translation second box","text","nomin","nomax",false);
				PutFormData($f,$s,"retranslationtext_$language","empty retranslation","text","nomin","nomax",false);
				PutFormData($f,$s,"translate_$language",$jobid?0:1,"bool",0,1);
		}
		PutFormData($f,$s,"tr_edit_$language",0,"bool",0,1);			
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

	$smsmessage = DBFind("Message","from message where id='$job->smsmessageid' and deleted=1 and type='sms'");	
	if($smsmessage != NULL) {
		$parts = DBFindMany("MessagePart","from messagepart where messageid=$smsmessage->id order by sequence");
		$body = $smsmessage->format($parts);
		PutFormData($f,$s,"smsmessagetxt",$body,'text');
	} else {
		PutFormData($f,$s,"smsmessagetxt", "", "text", 0, 160);
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
}

$joblangs = array("phone" => array(), "email" => array(), "print" => array(), "sms" => array());
if (isset($job->id)) {
	$joblangs['phone'] = DBFindMany('JobLanguage', "from joblanguage where type='phone' and jobid=" . $job->id);
	$joblangs['email'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'email' and jobid = " . $job->id);
	$joblangs['print'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'print' and jobid = " . $job->id);
	$joblangs['sms'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'sms' and jobid = " . $job->id);
}

$languages = DBFindMany("Language","from language");


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

		if($type == "sms") {
			NewFormItem($form,$section,$name,"selectoption", ' -- Create a Message -- ', "");
		} else {
			NewFormItem($form,$section,$name, "selectoption", ' -- Select a Message -- ', "");
		}
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
	global $job, $languages, $joblangs, $submittedmode;

	NewFormItem($form, $section, $name, 'selectstart', NULL, NULL, ($submittedmode ? "DISABLED" : ""));
	NewFormItem($form, $section, $name, 'selectoption'," -- Select a Language -- ","");
	foreach ($languages as $language) {
		if($job && !$job->getSetting('translationmessage')) {
			$used = false;
			foreach ($joblangs[$skipusedtype] as $joblang) {
				if ($joblang->language == $language->name) {
					$used = true;
					break;
				}
			}
	
			if ($used)
			continue;
		}
		NewFormItem($form, $section, $name, 'selectoption', $language->name, $language->name);
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
	if ($job && !$job->getSetting('translationmessage')){
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

NewForm($f);

if ($JOBTYPE == "normal") {
	if ($submittedmode)
	buttons(submit($f, $s, 'Save'));
	else
	buttons(submit($f, $s, 'Save For Later'),submit($f, 'send','Proceed To Confirmation'));
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
				<td colspan="2"><? NewFormItem($f,$s,"name","text", 30,$JOBTYPE == "repeating" ? 30:50); ?></td>
			</tr>
			<tr>
				<td>Description</td>
				<td colspan="2"><? NewFormItem($f,$s,"description","text", 30,50); ?></td>
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
				<td colspan="2">
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
				<td valign="top">List <?= help('Job_SettingsList',NULL,"small"); ?></td>
				<td valign="top" style="white-space:nowrap;">
					<? NewFormItem($f, $s, "listradio", "radio", NULL, "single","id='listradio_single' checked  onclick=\"if(this.checked == true) {show('singlelist');hide('multilist');} else{hide('singlelist');show('multilist');}\""); ?>Single&nbsp;List<br />
					<? NewFormItem($f, $s, "listradio", "radio", NULL, "multi","id='listradio_multi' onclick=\"if(this.checked == true) {hide('singlelist');show('multilist');} else{show('singlelist');hide('multilist');}\""); ?>Multi&nbsp;List
				</td>
				<td valign="center" width="100%" style="white-space:nowrap;">
				<div id='singlelist' style="padding-left: 2em;display: none">					
<?
						NewFormItem($f,$s,"listid", "selectstart", NULL, NULL, ($submittedmode ? "DISABLED" : ""));
						NewFormItem($f,$s,"listid", "selectoption", "-- Select a list --", NULL);
						foreach ($peoplelists as $id => $name) {
							NewFormItem($f,$s,"listid", "selectoption", $name, $id);
						}
						NewFormItem($f,$s,"listid", "selectend");
?>
				</div>
				<div id='multilist' style="padding-left: 2em;display: none">					
<?
						NewFormItem($f,$s,"listids", "selectmultiple",5, $peoplelists, ($submittedmode ? "DISABLED" : ""));
?>
				</td>
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
				<td><? time_select($f,$s,"starttime", NULL, NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), ($completedmode ? "DISABLED" : "")); ?></td>
			</tr>
			<tr>
				<td>&nbsp;&nbsp;Latest <?= help('Job_PhoneLatestTime', NULL, 'small') ?></td>
				<td><? time_select($f,$s,"endtime", NULL, NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), ($completedmode ? "DISABLED" : "")); ?></td>
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
				<td width="30%" valign="top">Default message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
				<td>
<? 	
					NewFormItem($f, $s, "messageselect", "radio", NULL, "create","id='radio_create' onclick=\"if(this.checked == true) {checkboxhelper('all'); show('newphonetext');hide('selectphonemessage');hide('multilingualphoneoption'); }\"");	echo "Create a Message"; 					
					NewFormItem($f, $s, "messageselect", "radio", NULL, "select","id='radio_select' onclick=\"if(this.checked == true) { hide('newphonetext');show('selectphonemessage'); show('multilingualphoneoption');}\""); echo "Select a Message";  
?>				<div id='selectphonemessage' style="display: none">
<?
					message_select('phone',$f, $s,"phonemessageid", "id='phonemessageid'");
?>				
				</div>
				<div id='newphonetext' style="display: block">
					Type Your English Message Here | 
					<? NewFormItem($f,$s,"translatecheck","checkbox",1, NULL,"id='translatecheckone' onclick=\"automatictranslation()\"") ?>
					Automaticaly Translate 
					<br />
					<table>
					<tr>
					<td><? NewFormItem($f,$s,"phonetextarea", "textarea", 50, 5,"id='phonetextarea'"); ?></td>
					<td valign="bottom"><?=	button('Play', "previewlanguage('english',true,true)");?></td>
					</tr>
					</table>
					Preferred Voice:
					<? NewFormItem($f, $s, "voiceselect", "radio", NULL, "female","id='female_voice' checked"); ?> Female 
					<? NewFormItem($f, $s, "voiceselect", "radio", NULL, "male","id='male_voice'"); ?> Male
					<br />
					<div id='translationdetails' style="display: block">
						<a href="#" onclick="translationoptions(true); return false; ">Show&nbsp;translation&nbsp;options</a>
					</div>
					<div id='translationbasic' style="display: none">
						<a href="#"	onclick="translationoptions(false); return false; ">Hide&nbsp;translation&nbsp;options</a>
					</div>

					<div id='translationoptions' style="display: none">
					<br />
					
						<table border="0" cellpadding="2" cellspacing="0" width="100%">
<?
foreach($languagearray as $language => $messageid) {
	$languageisset = $messageid?1:($jobid?0:1);
?>				
							<tr>
								<td style="white-space:nowrap;" valign="top" class="bottomBorder"><? NewFormItem($f,$s,"translate_$language","checkbox",NULL, NULL,"id='translate_$language' onclick=\"translationlanguage('$language')\""); echo "&nbsp;" . ucfirst($language) . ": ";?>
								</td>
								<td valign="top" class="bottomBorder">
									<table width="100%" style="table-layout:fixed;">
									<tr>
										<td>
									<div class="chop" id='language_<? echo $language?>' style="<? if($languageisset) echo "display:block"; else  echo "display:none";?>">			
										<?echo GetFormData($f, $s, "translationtext_$language"); ?> 
									</div>
										</td>
									</tr>
									</table>
									<div id='languageexpand_<? echo $language?>' style="display: none">
										Translation <?= help('Job_Translation',NULL,"small"); ?> <br />
										<? NewFormItem($f,$s,"translationtextexpand_$language", "textarea", 45, 3,"id='translationtextexpand_$language'  disabled"); ?>
										<br />
										<? NewFormItem($f,$s,"tr_edit_$language","checkbox",1, NULL,"id='tr_edit_$language' onclick=\"editlanguage('$language')\"") ?> Edit Translation <?= help('Job_EditTranslation',NULL,"small"); ?> 
										
										<br /><br />
										Retranslation <?= help('Job_Retranslation',NULL,"small"); ?> <br />
										<? NewFormItem($f,$s,"retranslationtext_$language", "textarea", 45, 3," disabled"); ?>
									</div>						
								</td>
								<td style="white-space:nowrap;" valign="top" class="bottomBorder">
									<div id='translationdetails_<? echo $language?>' style=<? if($languageisset) echo "display:block"; else  echo "display:none";?>>
										<table border="0"> 
										<tr>
										<td><?=	button('Play', "previewlanguage('$language'," . (isset($voicearray["female"][$language])?"'true'":"'false'") . "," . (isset($voicearray["male"][$language])?"'true'":"'false'") . ")");?>
										</td>
										<td>
										<a href="#"	onclick="langugaedetails('<? echo $language;?>',true); return false;">Show&nbsp;details</a>
										</td>
										</tr>
										</table>
									</div>
									<div id='translationbasic_<? echo $language?>' style="display: none">
										<table border="0"> 
										<tr>
										<td><?=	button('Play', "previewlanguage('$language'," . (isset($voicearray["female"][$language])?"'true'":"'false'") . "," . (isset($voicearray["male"][$language])?"'true'":"'false'") . ")");?>
										</td>
										<td>
										<a href="#" onclick="langugaedetails('<? echo $language;?>',false); return false;">Hide&nbsp;details</a>	
										</td>
										</tr>
										</table>
									</div>
								</td>
							</tr>							
<?}	?>
							</table>
							

				</div>
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
		<div id='multilingualphoneoption' style="display: none">
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
				<td width="30%">Default message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
				<td><? message_select('email',$f, $s,"emailmessageid"); ?></td>
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
		<table border="0" cellpadding="2" cellspacing="0" width=100%>
		<? if($USER->authorize('sendmulti')) { ?>
			<tr>
				<td width="30%">Multilingual message options <?= help('Job_MultilingualEmailOption',NULL,"small"); ?></td>
				<td><? alternate('email'); ?></td>
			</tr>
			<? } ?>
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
				<td><? message_select('sms',$f, $s,"smsmessageid", "onclick='if(this.value == 0){ show(\"newsmstext\") }else{ hide(\"newsmstext\") }'"); ?>
				<div id='newsmstext'><? NewFormItem($f,$s,"smsmessagetxt", "textarea", 20, 3, 'id="bodytext" onkeydown="limit_chars(this);" onkeyup="limit_chars(this);"' . ($submittedmode ? " DISABLED " : "")); ?>
				<span id="charsleft"><?= 160 - strlen(GetFormData($f,$s,"smsmessagetxt")) ?></span>
				characters remaining.</div>
				</td>
			</tr>
		</table>
		</div>
		</td>
	</tr>
	<? } ?>
</table>

<script language="javascript">
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
			hide('newsmstext');
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

		if(isCheckboxChecked('radio_create')) {
			show('newphonetext');
			hide('selectphonemessage');
			hide('multilingualphoneoption'); 
		} else {
			hide('newphonetext');
			show('selectphonemessage');
			show('multilingualphoneoption');
		}
		<?
		if ($_SESSION['jobid'] != null) {
			$diffvalues = $job->compareWithDefaults();
		}
		if ((isset($diffvalues['phonelang']) ||
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
		if ((isset($diffvalues['emaillang']) ||
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

	checkboxhelper('loading');
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


<? // If Automatic translation is selected ?>
function automatictranslation(){
	if(isCheckboxChecked('translatecheckone')){
		checkboxhelper('all');
	} else {
		checkboxhelper('none');
	}
}
<? // Show Translation options ?>
function translationoptions(details){
	if (details) {
		show('translationoptions');
		hide('translationdetails');
		show('translationbasic');
	} else {
		hide('translationoptions');
		show('translationdetails');
		hide('translationbasic');
	}
}

<? 
/* 
* Checkbox helper will show and hide language options
* - Set all languages if automatic translation is clicked 
* - On loading the page. This will ensure that the right boxes show up if there is an error message to the user
* - if not checkall and loading the checkbox helper will unselect automatic translation if no Languages are selected
*/ ?>
function checkboxhelper(mode) {
<?
	$languagestring = "";
	foreach($languagearray as $language => $messageid) { $languagestring .= ",'$language'";} 
	$languagestring = substr($languagestring,1);
?>
	var languagelist=new Array(<? echo $languagestring; ?>);

	if(mode == 'all'){
		for (i = 0; i < languagelist.length; i++) {	
			var language = languagelist[i]
			setChecked('translate_' + language);
			show('language_' + language);
			show('translationdetails_' + language);
			hide('languageexpand_' + language);
			hide('translationbasic_' + language);
		}
		var x = new getObj('translatecheckone');
		x.obj.checked = true;
	} else if(mode == 'none'){		
		for (i = 0; i < languagelist.length; i++) {	
			var language = languagelist[i]
			var x = new getObj('translate_' + language);
			x.obj.checked = false;			
			hide('language_' + language);
			hide('translationdetails_' + language);
			hide('languageexpand_' + language);
			hide('translationbasic_' + language);	
		}
	} else if(mode == 'loading') {
		var checked = false;
		for (i = 0; i < languagelist.length; i++) {
			var language = languagelist[i]
			if(isCheckboxChecked('translate_' + language)){
				show('language_' + language);
				show('translationdetails_' + language);
				checked = true;
			} else {
				hide('language_' + language);
				hide('translationdetails_' + language);
			}
			hide('languageexpand_' + language);
			hide('translationbasic_' + language);	
		}
		if(!checked) {
			var x = new getObj('translatecheckone');
			x.obj.checked = false;
		}
	} else { // default
		var checked = false;
		for (i = 0; i < languagelist.length; i++) {
			if(isCheckboxChecked('translate_' + languagelist[i])){
				checked = true;
			}
		}
		if(!checked) {
			var x = new getObj('translatecheckone');
			x.obj.checked = false;
		}
	}
}

<? // If language checkbox is selected ?>
function translationlanguage(language){
	checkboxhelper('default');
	if (isCheckboxChecked('translate_' + language)){
		setChecked('translatecheckone');
		show('language_' + language);
		show('translationdetails_' + language);
	} else {
		hide('language_' + language);
		hide('translationdetails_' + language);
	}	
	hide('languageexpand_' + language);
	hide('translationbasic_' + language);		
}

<? //If language details is clicked ?>
function langugaedetails(language, details){
	if(details){
		hide('language_' + language);
		show('languageexpand_' + language);
		show('translationbasic_' + language);
		hide('translationdetails_' + language);
	} else {
		show('language_' + language);
		hide('languageexpand_' + language);
		hide('translationbasic_' + language);
		show('translationdetails_' + language);	
	}
}

function editlanguage(language) {
	var textbox = new getObj('translationtextexpand_' + language).obj;	
	textbox.disabled = !isCheckboxChecked('tr_edit_' + language);	
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
	if(isCheckboxChecked('male_voice') && male) {
		voice = 'male';
	} else if(isCheckboxChecked('female_voice') && female) {
		voice = 'female';
	}
	var text;
	if(language == 'english')
		text = new getObj('phonetextarea').obj;
	else 
		text = new getObj('translationtextexpand_' + language).obj;
	 
	popup('previewmessage.php?text=' + text.value + '&language=' + language +'&gender=' + voice, 400, 400);
}



</script>
<script SRC="script/calendar.js"></script>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>
