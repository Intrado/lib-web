<?

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
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

/****************** main message section ******************/

$f = "notification";
$s = "main" . $JOBTYPE;
$reloadform = 0;

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
			if($type == "sms")
				continue;
			SetRequired($f, $s, $type . "messageid", (bool)GetFormData($f, $s, 'send' . $type));
		}
		SetRequired($f, $s, "smsmessagetxt", GetFormData($f, $s, 'sendsms') && GetFormData($f, $s, 'smsmessageid') == "");
		//do check

		$sendphone = GetFormData($f, $s, "sendphone");
		$sendemail = GetFormData($f, $s, "sendemail");
		$sendsms = getSystemSetting("_hassms", false) ? GetFormData($f, $s, "sendsms") : 0;
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(!$submittedmode && !$sendphone && !$sendemail && !$sendsms){
			error("Plese select a delivery type");
		} else if ($JOBTYPE == 'normal' && (strtotime(GetFormData($f,$s,"startdate")) === -1 || strtotime(GetFormData($f,$s,"startdate")) === false)) {
			error('The start date is invalid');
		} else if ($JOBTYPE=='normal' && (strtotime(GetFormData($f,$s,"starttime")) === -1 || strtotime(GetFormData($f,$s,"starttime")) === false)) {
			error('The start time is invalid');
		} else if ($JOBTYPE=='normal' && (strtotime(GetFormData($f,$s,"endtime")) === -1 || strtotime(GetFormData($f,$s,"endtime")) === false)) {
			error('The end time is invalid');
		} else if (strtotime(GetFormData($f,$s,"endtime")) <= strtotime(GetFormData($f,$s,"starttime")) ) {
			error('The end time cannot be before or the same as the start time');
		} else if (strtotime(GetFormData($f, $s,"endtime"))-(30*60) < strtotime(GetFormData($f,$s,"starttime"))){
			error('The end time must be at least 30 minutes after the start time');
		} else if ($JOBTYPE == "normal" &&  (strtotime(GetFormData($f,$s,"startdate"))+((GetFormData($f,$s,"numdays")-1)*86400) < strtotime("today")) && !$completedmode){
			error('The end date has already passed. Please correct this problem before proceeding');
		} else if ($JOBTYPE == "normal" && (strtotime(GetFormData($f,$s,"startdate"))+((GetFormData($f,$s,"numdays")-1)*86400) == strtotime("today")) && (strtotime(GetFormData($f,$s,"endtime")) < strtotime("now")) && !$completedmode) {
			error('The end time has already passed. Please correct this problem before proceeding');
		} else if (QuickQuery("select id from job where deleted = 0 and name = '" . DBsafe(GetFormData($f,$s,"name")) . "' and userid = $USER->id and status in ('new','scheduled','processing','procactive','active','repeating') and id != " . ( 0+ $_SESSION['jobid']))) {
			error('A job named \'' . GetFormData($f,$s,"name") . '\' already exists');
		} else if (GetFormData($f,$s,"callerid") != "" && strlen(Phone::parse(GetFormData($f,$s,"callerid"))) != 10) {
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
				PopulateObject($f,$s,$job,array("name", "description"));
			} else if ($submittedmode) {
				PopulateObject($f,$s,$job,array("name", "description","startdate", "starttime", "endtime"));
			} else {
				if($hassms && $USER->authorize('sendsms') && GetFormData($f, $s, "sendsms") && GetFormData($f, $s, 'smsmessageid') == "" ){
					$newsmsmessage = new Message();
					$parts = $newsmsmessage->parse(GetFormData($f, $s, 'smsmessagetxt'));
					$newsmsmessage->userid = $USER->id;
					$newsmsmessage->type = 'sms';
					$newsmsmessage->name = GetFormData($f, $s,'name');
					$newsmsmessage->description = "SMS Message " . date("M j, Y g:i:s", strtotime("now"));
					$newsmsmessage->create();

					foreach($parts as $part){
						$part->messageid = $newsmsmessage->id;
						$part->create();
					}

					//Do a putform on message select so if there is an error later on, another message does not get created
					PutFormData($f, $s, 'smsmessageid', $newsmsmessage->id, 'number', 'nomin', 'nomax');
				}

				$fieldsarray = array("name", "jobtypeid", "description", "listid", "phonemessageid",
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
			if($USER->authorize('sendmulti')) {
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
	$job->startdate = date("F jS, Y", strtotime($job->startdate));
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

	PutFormData($f,$s,"maxcallattempts",$job->getOptionValue("maxcallattempts"), "number",1,$ACCESS->getValue('callmax'),true);
	PutFormData($f,$s,"skipduplicates",$job->isOption("skipduplicates"), "bool",0,1);
	PutFormData($f,$s,"skipemailduplicates",$job->isOption("skipemailduplicates"), "bool",0,1);

	PutFormData($f,$s,"sendreport",$job->isOption("sendreport"), "bool",0,1);
	PutFormData($f, $s, 'numdays', (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400, 'number', 1, ($ACCESS->getValue('maxjobdays') != null ? $ACCESS->getValue('maxjobdays') : "7"), true);
	PutFormData($f,$s,"callerid", Phone::format($job->getOptionValue("callerid")), "phone", 10, 10);

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

	PutFormData($f,$s,"smsmessagetxt", "", "text", 0, 160);
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
	$joblangs['phone'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'phone' and jobid = " . $job->id);
	$joblangs['email'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'email' and jobid = " . $job->id);
	$joblangs['print'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'print' and jobid = " . $job->id);
	$joblangs['sms'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'sms' and jobid = " . $job->id);
}

$languages = DBFindMany("Language","from language");

$peoplelists = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function message_select($type, $form, $section, $name, $extrahtml = "") {
	global $messages, $submittedmode;
?>
	<table border=0 cellpadding=3 cellspacing=0><tr><td>
<?
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
?>
	</td>
<?	if ($type == "phone") { ?>
		<td><?= button('Play', "var audio = new getObj('$name').obj; if(audio.selectedIndex >= 1) popup('previewmessage.php?id=' + audio.options[audio.selectedIndex].value, 400, 400);") ?>
		</td>
<?	} ?>
	</tr></table>
<?
}

function language_select($form, $section, $name, $skipusedtype) {
	global $languages, $joblangs, $submittedmode;

	NewFormItem($form, $section, $name, 'selectstart', NULL, NULL, ($submittedmode ? "DISABLED" : ""));
	NewFormItem($form, $section, $name, 'selectoption'," -- Select a Language -- ","");
	foreach ($languages as $language) {
		$used = false;
		foreach ($joblangs[$skipusedtype] as $joblang) {
			if ($joblang->language == $language->name) {
				$used = true;
				break;
			}
		}

		if ($used)
		continue;
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
$id = $type . 'messageid';
//just show the selected options? allowing to edit could cause the page to become slow
//with many languages/messages
foreach($joblangs[$type] as $joblang) {
?>
			<tr valign="middle">
				<td><?= $joblang->language ?>
				</td>
				<td>
<? if ($type == "phone") { ?>
					<div style="float: right;"><?= button('Play', "popup('previewmessage.php?id=" . $joblang->messageid . "', 400, 400);"); ?></div>
<? } ?>
					<?= $messages[$type][$joblang->messageid]->name ?>
				</td>
				<td>
				<? if (!$submittedmode) { ?>
							<a href="<?= ($JOBTYPE == "repeating" ? "jobrepeating.php" : "job.php") ?>?deletejoblang=<?= $joblang->id ?>">Delete</a>
					<? } ?>
				</td>
			</tr>
<?
}
?>
		<tr valign="middle">
			<td><? language_select($f,$type,"newlang" . $type, $type); ?>
			</td>
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
$TITLE = ($JOBTYPE == 'repeating' ? 'Repeating Job Editor: ' : 'Job Editor: ') . ($jobid == NULL ? "New Job" : $job->name);
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
		<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td align=center><div class='alertmessage noprint'>The System Administrator has disabled all Repeating Jobs. <br>No Repeating Jobs can be run while this setting remains in effect.</div></td></tr></table>
<?
	}

?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Delivery Type:<br></th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" >
				<tr>
<?
				if($USER->authorize('sendphone')){
?>
						<td align="center" style="padding-left:15px"><div <?=$submittedmode ? "" : "onclick=\"clickIcon('phone')\"" ?> ><img src="img/themes/<?=getBrandTheme()?>/icon_phone.gif" align="absmiddle"></div></td>
<?
				}
				if($USER->authorize('sendemail')){
?>
						<td align="center" style="padding-left:15px"><div <?=$submittedmode ? "" : "onclick=\"clickIcon('email')\"" ?> ><img src="img/themes/<?=getBrandTheme()?>/icon_email.gif" align="absmiddle"></div></td>
<?
				}
				if($hassms && $USER->authorize('sendsms')){
?>
						<td align="center" style="padding-left:15px"><div <?=$submittedmode ? "" : "onclick=\"clickIcon('sms')\""?> ><img src="img/themes/<?=getBrandTheme()?>/icon_sms.gif" align="absmiddle"></div></td>
<?
				}
?>
				</tr>
				<tr>
<?
				if($USER->authorize('sendphone')){
?>
					<td style="padding-left:15px">Phone:<? NewFormItem($f,$s,"sendphone","checkbox",NULL,NULL,"id='sendphone' " . ($submittedmode ? "DISABLED" : "") . " onclick=\"if(this.checked) displaySection('phone'); else hideSection('phone')\""); ?></td>
<?
				}
				if($USER->authorize('sendemail')){
?>

					<td style="padding-left:15px">Email:<? NewFormItem($f,$s,"sendemail","checkbox",NULL,NULL,"id='sendemail' " . ($submittedmode ? "DISABLED" : "") . " onclick=\"if(this.checked) displaySection('email'); else hideSection('email');\""); ?></td>
<?
				}
				if($hassms && $USER->authorize('sendsms')){
?>
					<td style="padding-left:15px">SMS:<? NewFormItem($f,$s,"sendsms","checkbox",NULL,NULL,"id='sendsms' " . ($submittedmode ? "DISABLED" : "") . " onclick=\"if(this.checked) displaySection('sms'); else hideSection('sms');\""); ?></td>
<?
				}
?>
				</tr>
			</table>
		</td>
	</tr>

	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:<br></th>
		<td class="bottomBorder">
			&nbsp;
			<div id="settings" style="display:none">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td width="30%" >Job Name</td>
					<td><? NewFormItem($f,$s,"name","text", 30,$JOBTYPE == "repeating" ? 30:50); ?></td>
				</tr>
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
						<table border="0" cellpadding="2px" cellspacing="1px" class="list" id="jobtypetable">
							<tr class="listHeader" align="left" valign="bottom">
								<th>Name</th>
								<th style="padding-left:6px;">Info</th>
<?
						if(getSystemSetting('_dmmethod', "asp")=='hybrid'){
?>
								<th>Delivery System</th>
<?
						}
?>
							</tr>
							<tr valign="top">
								<td>
									<?

									NewFormItem($f,$s,"jobtypeid", "selectstart", NULL, NULL, "id=jobtypeid " . ($submittedmode ? "DISABLED" : "") . " onchange='display_jobtype_info(this.value)' ");
									NewFormItem($f,$s,"jobtypeid", "selectoption", " -- Select a Job Type -- ", "");
									foreach ($VALIDJOBTYPES as $item) {
										NewFormItem($f,$s,"jobtypeid", "selectoption", $item->name, $item->id);
									}
									NewFormItem($f,$s,"jobtypeid", "selectend");
									?>
								</td>

								<td style="padding-left:6px;"><div id="jobtypeinfo" style="float:left;"></div></td>
<?
							if(getSystemSetting('_dmmethod', "asp")=='hybrid'){
?>
								<td><div id="addinfo"></div></td>
<?
							}
?>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>List <?= help('Job_SettingsList',NULL,"small"); ?></td>
					<td>
						<?
						NewFormItem($f,$s,"listid", "selectstart", NULL, NULL, ($submittedmode ? "DISABLED" : ""));
						NewFormItem($f,$s,"listid", "selectoption", "-- Select a list --", NULL);
						foreach ($peoplelists as $plist) {
							NewFormItem($f,$s,"listid", "selectoption", $plist->name, $plist->id);
						}
						NewFormItem($f,$s,"listid", "selectend");
						?>
					</td>
				</tr>
			</table>
			</div>

			<div id='displaysettingsdetails' style="display:none">
				<a href="#" onclick="displaySection('settings', true); ">Show advanced options</a>
			</div>

			<div id='displaysettingsbasic' style="display:none">
				<a href="#" onclick="displaySection('settings', false); ">Hide advanced options</a>
			</div>

			<div id="settingsdetails" style="display:none">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<? if ($JOBTYPE != "repeating") { ?>
					<tr>
						<td width="30%">Start date <?= help('Job_SettingsStartDate',NULL,"small"); ?></td>
						<td><? NewFormItem($f,$s,"startdate","text", 30, NULL, ($completedmode ? "DISABLED" : "")); ?></td>
					</tr>
				<? } ?>

				<tr>
					<td width="30%">Number of days to run <?= help('Job_SettingsNumDays', NULL, "small"); ?></td>
					<td>
					<?
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
					?>
					<!--
					<span id="job_end_date">You have scheduled this job to end on <?= isset($job) ? $job->enddate : "" ?></span>
					-->
					</td>
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
<?
					if(!$submittedmode){
?>
						<a href="#" onclick="displaySection('phone'); new getObj('sendphone').obj.checked=true; return false;">Click here</a> or select checkbox above.
<?
					} else {
?>
						&nbsp;
<?
					}
?>
			</div>
			<div id='phoneoptions' style="display:none">
				<table border="0" cellpadding="2" cellspacing="0" width=100%>
					<tr>
						<td width="30%" >Default message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
						<td><? message_select('phone',$f,$s,"phonemessageid"); ?></td>
					</tr>
				</table>
			</div>

			<div id='displayphonedetails' style="display:none">
				<a href="#" onclick="displaySection('phone', true); ">Show advanced options</a>
			</div>

			<div id='displayphonebasic' style="display:none">
				<a href="#" onclick="displaySection('phone', false); ">Hide advanced options</a>
			</div>

			<div id='phonedetails' style="display:none">
				<table border="0" cellpadding="2" cellspacing="0" width=100%>


<? if($USER->authorize('sendmulti')) { ?>

					<tr>
						<td width="30%">Multilingual message options <?= help('Job_MultilingualPhoneOption',NULL,"small"); ?></td>
						<td><? alternate('phone'); ?></td>
					</tr>
<? } ?>
					<tr>
						<td width="30%">Maximum attempts <?= help('Job_PhoneMaxAttempts', NULL, 'small')  ?></td>
						<td>
							<?
							$max = first($ACCESS->getValue('callmax'), 1);
							NewFormItem($f,$s,"maxcallattempts","selectstart", NULL, NULL, ($completedmode ? "DISABLED" : ""));
							for($i = 1; $i <= $max; $i++)
							NewFormItem($f,$s,"maxcallattempts","selectoption",$i,$i);
							NewFormItem($f,$s,"maxcallattempts","selectend");
							?>
						</td>
					</tr>
					<? if ($USER->authorize('setcallerid')) { ?>
						<tr>
								<td>Caller&nbsp;ID <?= help('Job_CallerID',NULL,"small"); ?></td>
								<td><? NewFormItem($f,$s,"callerid","text", 20, 20, ($submittedmode ? "DISABLED" : "")); ?></td>
						</tr>
					<? } ?>

					<tr>
						<td>Skip duplicate phone numbers <?=  help('Job_PhoneSkipDuplicates', NULL, 'small') ?></td>
						<td><? NewFormItem($f,$s,"skipduplicates","checkbox",1, NULL, ($submittedmode ? "DISABLED" : "")); ?>Skip Duplicates</td>
					</tr>

					<? if($USER->authorize('leavemessage')) { ?>
						<tr>
							<td> Allow call recipients to leave a message <?= help('Jobs_VoiceResponse', NULL, 'small') ?> </td>
							<td> <? NewFormItem($f, $s, "leavemessage", "checkbox", 0, NULL, ($submittedmode ? "DISABLED" : "")); ?> Accept Voice Responses </td>
						</tr>
					<?
						}
						if ($USER->authorize("messageconfirmation")){
					?>
					<tr>
						<td> Allow message confirmation by recipients <?= help('Job_MessageConfirmation', NULL, 'small') ?> </td>
						<td> <? NewFormItem($f, $s, "messageconfirmation", "checkbox", 0, NULL, ($submittedmode ? "DISABLED" : "")); ?> Request Message Confirmation </td>
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
			<div id='displayemailoptions'>
<?
					if(!$submittedmode){
?>
						<a href="#" onclick="displaySection('email'); new getObj('sendemail').obj.checked=true; return false;">Click here</a> or select checkbox above.
<?
					} else {
?>
						&nbsp;
<?
					}
?>
			</div>

			<div id='emailoptions' style="display:none">
				<table border="0" cellpadding="2" cellspacing="0" width=100%>
					<tr>
						<td width="30%" >Default message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
						<td><? message_select('email',$f, $s,"emailmessageid"); ?></td>
					</tr>
				</table>
			</div>

			<div id='displayemaildetails' style="display:none">
				<a href="#" onclick="displaySection('email', true); ">Show advanced options</a>
			</div>

			<div id='displayemailbasic' style="display:none">
				<a href="#" onclick="displaySection('email', false); ">Hide advanced options</a>
			</div>

			<div id='emaildetails' style="display:none">
				<table border="0" cellpadding="2" cellspacing="0" width=100%>
<? if($USER->authorize('sendmulti')) { ?>
					<tr>
						<td width="30%">Multilingual message options <?= help('Job_MultilingualEmailOption',NULL,"small"); ?></td>
						<td><? alternate('email'); ?></td>
					</tr>
<? } ?>
					<tr>
						<td width="30%">Skip duplicate email addresses <?=  help('Job_EmailSkipDuplicates', NULL, 'small') ?></td>
						<td><? NewFormItem($f,$s,"skipemailduplicates","checkbox",1, NULL, ($submittedmode ? "DISABLED" : "")); ?>Skip Duplicates</td>
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
			<div id='displayprintoptions'>
<?
					if(!$submittedmode){
?>
						<a href="#" onclick="displaySection('print'); new getObj('sendprint').obj.checked=true; return false;">Click here</a> or select checkbox above.
<?
					} else {
?>
						&nbsp;
<?
					}
?>
			</div>
			<div id='printoptions' style="display:none">
				<table border="0" cellpadding="2" cellspacing="0" width=100%>
					<tr>
						<td width="30%" >Send printed letters <? print help('Job_PrintOptions', null, 'small'); ?></td>
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
						<td colspan="2"><? NewFormItem($f,$s,"printall","radio",NULL,"1"); ?> Send to all valid addresses in this list</td>
					</tr>
					<tr>
						<td colspan="2"><? NewFormItem($f,$s,"printall","radio",NULL,"0"); ?> After job completes, print letters for anyone who was not contacted</td>
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
			<div id='displaysmsoptions'>
<?
					if(!$submittedmode){
?>
						<a href="#" onclick="displaySection('sms'); new getObj('sendsms').obj.checked=true; return false;">Click here</a> or select checkbox above.
<?
					} else {
?>
						&nbsp;
<?
					}
?>
			</div>
			<div id='smsoptions' style="display:none">
				<table border="0" cellpadding="2" cellspacing="0" width=100%>
					<tr>
						<td width="30%" >Default message <?= help('Job_SMSDefaultMessage', NULL, 'small') ?></td>
						<td>
							<? message_select('sms',$f, $s,"smsmessageid", "onclick='if(this.value == 0){ show(\"newsmstext\") }else{ hide(\"newsmstext\") }'"); ?>
							<div id='newsmstext'><? NewFormItem($f,$s,"smsmessagetxt", "textarea", 20, 3, 'id="bodytext" onkeydown="limit_chars(this);" onkeyup="limit_chars(this);"' . ($submittedmode ? " DISABLED " : "")); ?>
							<span id="charsleft"><?= 160 - strlen(GetFormData($f,$s,"smsmessagetxt")) ?></span> characters remaining.</div>
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
	var displayphonedetailsstate = 'visible';
	var displayemaildetailsstate = 'visible';
	var jobtypetablestyle = new getObj("jobtypetable").obj.style.border;
	var jobtypeinfo = new Array();

	jobtypeinfo[""] = new Array("", "");
<?
	foreach($infojobtypes as $infojobtype){
		$info = htmlentities($infojobtype->info);
		$info = str_replace(array("\r\n","\n","\r"),"<br>",$info);
?>
		jobtypeinfo[<?=$infojobtype->id?>] = new Array("<?=$infojobtype->systempriority?>", "<?=$info?>");
<?
	}
?>
	display_jobtype_info(new getObj('jobtypeid').obj.value);
	smscheck = false;
<?
	if($hassms && $USER->authorize('sendsms')) {
?>
		var smsmessageobj = new getObj('smsmessageid').obj;
		if(smsmessageobj && smsmessageobj.value != ""){
				new getObj('sendsms').obj.checked = true;
		}
		smscheck = new getObj('sendsms').obj.checked;
		var smsmessagedropdown = new getObj('smsmessageid').obj;
		if(smsmessagedropdown.value != ""){
			hide('newsmstext');
		}
		if(smscheck){
			show('smsoptions');
			hide('displaysmsoptions');
		}
<?
	}
?>
	phonecheck = false;
	emailcheck = false;
	var phonemessageobj = new getObj('phonemessageid').obj;
	if(phonemessageobj  && phonemessageobj .value != ""){
		new getObj('sendphone').obj.checked = true;
	}
	var emailmessageobj = new getObj('emailmessageid').obj;
	if(emailmessageobj && emailmessageobj .value != ""){
			new getObj('sendemail').obj.checked = true;
	}
	if(new getObj('sendphone').obj){
		phonecheck = new getObj('sendphone').obj.checked;
		if(phonecheck){
			show('phoneoptions');
<?

	if ($_SESSION['jobid'] != null) {
		$diffvalues = $job->compareWithDefaults();
	}
	if (isset($diffvalues['phonelang']) ||
		isset($diffvalues['maxcallattempts']) ||
		isset($diffvalues['callerid']) ||
		isset($diffvalues['skipduplicates']) ||
		isset($diffvalues['leavemessage']) ||
		isset($diffvalues['messageconfirmation'])
		) {
		?> displayphonedetailsstate = 'hidden'; <?
	}
?>
			if (displayphonedetailsstate == 'visible') {
				show('displayphonedetails');
			} else {
				show('phonedetails');
				show('displayphonebasic');
			}
		}
	}
	if(new getObj('sendemail').obj){
		emailcheck = new getObj('sendemail').obj.checked;
		if(emailcheck){
			show('emailoptions');
<?
	if ($_SESSION['jobid'] != null) {
		$diffvalues = $job->compareWithDefaults();
	}
	if (isset($diffvalues['emaillang']) ||
		isset($diffvalues['skipemailduplicates'])
		) {
		?> displayemaildetailsstate = 'hidden'; <?
	}
?>
			if (displayemaildetailsstate == 'visible') {
				show('displayemaildetails');
			} else {
				show('emaildetails');
				show('displayemailbasic');
			}
		}
	}
	if( phonecheck || emailcheck || smscheck ){
		show('settings');
<?
	if ($_SESSION['jobid'] != null) {
		$diffvalues = $job->compareWithDefaults();
	}
	if (isset($diffvalues['startdate']) ||
		isset($diffvalues['enddate']) ||
		isset($diffvalues['starttime']) ||
		isset($diffvalues['endtime']) ||
		isset($diffvalues['sendreport'])
		) {
		?> displaysettingsdetailsstate = 'hidden'; <?
	}
?>
		if (displaysettingsdetailsstate == 'visible') {
			show('displaysettingsdetails');
		} else {
			show('settingsdetails');
			show('displaysettingsbasic');
		}
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
	phonecheck = false;
	emailcheck = false;
	smscheck = false;

	if(new getObj('sendphone').obj)
		phonecheck = new getObj('sendphone').obj.checked;
	if(new getObj('sendemail').obj)
		emailcheck = new getObj('sendemail').obj.checked;
	if(new getObj('sendsms').obj)
		smscheck = new getObj('sendsms').obj.checked;

	if(!phonecheck && !emailcheck && !smscheck){
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
</script>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>
