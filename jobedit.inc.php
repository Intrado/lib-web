<?

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_POST['addjob_x'])) {
	$_SESSION['jobid'] = NULL;
}

if (isset($_GET['id'])) {
	setCurrentJob($_GET['id']);
	redirect();
}

$jobid = $_SESSION['jobid'];


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

	if ($job->status == 'active' || $completedmode) {
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

/****************** main message section ******************/

$f = "notification";
$s = "main" . $JOBTYPE;
$reloadform = 0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'phone') || CheckFormSubmit($f,'email') || CheckFormSubmit($f,'print') || CheckFormSubmit($f,'send'))
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
		foreach (array("phone","email","print") as $type)
			MergeSectionFormData($f, $type);

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($JOBTYPE == 'normal' && (strtotime(GetFormData($f,$s,"startdate")) === -1 || strtotime(GetFormData($f,$s,"startdate")) === false)) {
			error('The start date is invalid');
		} else if (strtotime(GetFormData($f,$s,"starttime")) === -1 || strtotime(GetFormData($f,$s,"starttime")) === false) {
			error('The start time is invalid');
		} else if (strtotime(GetFormData($f,$s,"endtime")) === -1 || strtotime(GetFormData($f,$s,"endtime")) === false) {
			error('The end time is invalid');
		} else if (strtotime(GetFormData($f,$s,"endtime")) < strtotime(GetFormData($f,$s,"starttime")) ) {
			error('The end time cannot be before the start time');
		} else if ((strtotime(GetFormData($f,$s,"startdate"))+((GetFormData($f,$s,"numdays")-1)*86400) < strtotime("today")) && ($JOBTYPE == "normal") && !$completedmode){
			error('The end date has already passed. Please correct this problem before proceeding.');
		} else if ( (strtotime(GetFormData($f,$s,"startdate"))+((GetFormData($f,$s,"numdays")-1)*86400) == strtotime("today")) && (strtotime(GetFormData($f,$s,"endtime")) < strtotime("now")) && ($JOBTYPE == "normal") && !$completedmode) {
			error('The end time has already passed. Please correct this problem before proceeding.');
		} else if (QuickQuery("select id from job where deleted = 0 and name = '" . DBsafe(GetFormData($f,$s,"name")) . "' and userid = $USER->id and status in ('new','active','repeating') and id != " . ( 0+ $_SESSION['jobid']))) {
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
			}
			else if ($submittedmode) {
				PopulateObject($f,$s,$job,array("name", "description","startdate", "starttime", "endtime",
				"maxcallattempts"));
			} else {
				$fieldsarray = array("name", "jobtypeid", "description", "listid", "phonemessageid",
				"emailmessageid","printmessageid", "starttime", "endtime",
				"maxcallattempts", "sendphone", "sendemail", "sendprint",
				"skipduplicates", "printall", "printunnotified");
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
			$job->type=implode(",",$jobtypes);

			//repopulate the form with these linked values in case of a validation error.
			$fields = array(
				array("sendphone","bool",0,1),
				array("sendemail","bool",0,1),
				array("sendprint","bool",0,1)
			);

			PopulateForm($f,$s,$job,$fields);


			$job->setOption("callall",GetFormData($f,$s,"callall"));
			$job->setOption("callfirst",!GetFormData($f,$s,"callall"));
			$job->setOption("skipduplicates",GetFormData($f,$s,"skipduplicates"));
			$job->setOption("skipemailduplicates",GetFormData($f,$s,"skipemailduplicates"));

			$job->setOption("sendreport",GetFormData($f,$s,"sendreport"));
			if ($USER->authorize('setcallerid') && GetFormData($f,$s,"callerid")) {
				$job->setOptionValue("callerid",Phone::parse(GetFormData($f,$s,"callerid")));
			} else {
				$callerid = $USER->getSetting("callerid",getSystemSetting('callerid'));
				$job->setOptionValue("callerid", $callerid);
			}

			if (getSystemSetting('retry') != "")
				$job->setOptionValue("retry",getSystemSetting('retry'));

			if ($JOBTYPE == "repeating") {
				$schedule = new Schedule($job->scheduleid);
				$schedule->time = date("H:i", strtotime(GetFormData($f,$s,"scheduletime")));
				$schedule->triggertype = "job";
				$schedule->type = "R";
				$schedule->userid = $USER->id;
				$schedule->update();
				$job->scheduleid = $schedule->id;

				$data = QuickQueryList("select dow from scheduleday where scheduleid=$schedule->id");
				for ($x = 1; $x < 8; $x++) {
					if(GetFormData($f,$s,"dow$x")) {
						if (!in_array($x,$data))
						QuickUpdate("insert into scheduleday (scheduleid, dow) values ($schedule->id,$x)");
					} else {
						if (in_array($x,$data))
						QuickUpdate("delete from scheduleday where scheduleid=$schedule->id and dow=$x");
					}
				}

				$schedule->nextrun = $schedule->calcNextRun();
				$schedule->update(array("nextrun"));

				$numdays = GetFormData($f, $s, 'numdays');

				// 86,400 seconds in a day - precaution b/c windows doesn't
				//	like dates before 1970, and using 0 makes windows think it's 12/31/69
				$job->startdate = date("Y-m-d", 86400);
				$job->enddate = date("Y-m-d", ($numdays * 86400));
			} else if ($JOBTYPE == 'normal') {
				$numdays = GetFormData($f, $s, 'numdays');
				$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
			}

			//reformat the dates & times to DB friendly format
			$job->startdate = date("Y-m-d", strtotime($job->startdate));
			$job->enddate = date("Y-m-d", strtotime($job->enddate));
			$job->starttime = date("H:i", strtotime($job->starttime));
			$job->endtime = date("H:i", strtotime($job->endtime));
			$job->userid = $USER->id;

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
				if ($job->phonemessageid || $job->emailmessageid || $job->printmessageid)	{
					redirect("jobconfirm.php?id=" . $job->id);
				} else {
					error("Please select at least one message for at least one type");
				}
			} else if (!$addlang) {
				redirect('jobs.php');
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
	array("jobtypeid","number","nomin","nomax"),
	array("listid","number","nomin","nomax",true),
	array("phonemessageid","number","nomin","nomax"),
	array("emailmessageid","number","nomin","nomax"),
	array("printmessageid","number","nomin","nomax"),
	array("starttime","text",1,50,true),
	array("endtime","text",1,50,true),
	array("maxcallattempts","number",1,$ACCESS->getValue('callmax'),true),
	array("sendphone","bool",0,1),
	array("sendemail","bool",0,1),
	array("sendprint","bool",0,1)
	);

	PopulateForm($f,$s,$job,$fields);

	PutFormData($f,$s,"callall",$job->isOption("callall"), "bool",0,1);
	PutFormData($f,$s,"skipduplicates",$job->isOption("skipduplicates"), "bool",0,1);
	PutFormData($f,$s,"skipemailduplicates",$job->isOption("skipemailduplicates"), "bool",0,1);

	PutFormData($f,$s,"sendreport",$job->isOption("sendreport"), "bool",0,1);
	PutFormData($f, $s, 'numdays', (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400, 'number', 1, $ACCESS->getValue('maxjobdays'), true);
	PutFormData($f,$s,"callerid", Phone::format($job->getOptionValue("callerid")), "text", 0, 20);

	if ($JOBTYPE == "repeating") {
		$schedule = new Schedule($job->scheduleid);

		$scheduledows = array();
		if ($schedule->id == NULL) {
			$schedule->time = $USER->getCallEarly();
		} else {
			$data = QuickQueryList("select dow from scheduleday where scheduleid=$schedule->id");
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

}

$messages = array();
$messages['phone'] = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='phone' order by name");
$messages['email'] = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='email' order by name");
$messages['print'] = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='print' order by name");

$joblangs = array("phone" => array(), "email" => array(), "print" => array());
if ($job->id) {
	$joblangs['phone'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'phone' and jobid = " . $job->id);
	$joblangs['email'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'email' and jobid = " . $job->id);
	$joblangs['print'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'print' and jobid = " . $job->id);
}

$languages = DBFindMany("Language","from language where customerid=" . $USER->customerid);

$peoplelists = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function message_select($type, $form, $section, $name) {
	global $messages, $submittedmode;

	NewFormItem($form,$section,$name, "selectstart", NULL, NULL, "id='$name' " . ($submittedmode ? "DISABLED" : ""));
	NewFormItem($form,$section,$name, "selectoption", '- Select a Message -', "0");
	foreach ($messages[$type] as $message) {
		NewFormItem($form,$section,$name, "selectoption", $message->name, $message->id);
	}
	NewFormItem($form,$section,$name, "selectend");

	if ($type == "phone")
		echo button('play', "var audio = new getObj('$name').obj; if(audio.selectedIndex >= 1) popup('previewmessage.php?id=' + audio.options[audio.selectedIndex].value, 400, 400);");
}

function language_select($form, $section, $name, $skipusedtype) {
	global $languages, $joblangs, $submittedmode;

	NewFormItem($form, $section, $name, 'selectstart', NULL, NULL, ($submittedmode ? "DISABLED" : ""));
	NewFormItem($form, $section, $name, 'selectoption',"- Select a Language -","");
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
					<div style="float: right;"><?= button('play', "popup('previewmessage.php?id=" . $joblang->messageid . "', 400, 400);"); ?></div>
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
						echo submit($f, $type, 'Add', 'add'); ?></td>
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
$DESCRIPTION = "Job status: " . fmt_status($job, NULL);

include_once("nav.inc.php");

NewForm($f);

if ($JOBTYPE == "normal") {
	if ($submittedmode)
	buttons(submit($f, $s, 'save', 'save'));
	else
	buttons(submit($f, $s, 'saveforlater', 'saveforlater'),submit($f, 'send','confirmation', 'confirmation'));
} else {
	buttons(submit($f, $s, 'save', 'save'));
}


startWindow('Job Information');
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:<br></th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td width="30%" >Name</td>
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
							<tr class="listHeader" align="left" valign="bottom"><td>Su</td>
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
					<td>Priority <?= help('Job_SettingsType',NULL,"small"); ?></td>
					<td>
						<?

						NewFormItem($f,$s,"jobtypeid", "selectstart", NULL, NULL, ($submittedmode ? "DISABLED" : ""));
						foreach ($VALIDJOBTYPES as $item) {
							NewFormItem($f,$s,"jobtypeid", "selectoption", $item->name, $item->id);
						}
						NewFormItem($f,$s,"jobtypeid", "selectend");
						?>
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
				<? if ($JOBTYPE != "repeating") { ?>
					<tr>
						<td>Start Date <?= help('Job_SettingsStartDate',NULL,"small"); ?></td>
						<td><? NewFormItem($f,$s,"startdate","text", 30, NULL, ($completedmode ? "DISABLED" : "")); ?></td>
					</tr>
				<? } ?>
				<tr>
					<td>Number of days to run <?= help('Job_SettingsNumDays', NULL, "small"); ?></td>
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
					<span id="job_end_date">You have scheduled this job to end on <?= $job->enddate ?></span>
					-->
					</td>
				</tr>
				<tr>
					<td colspan="2">Delivery Window:</td>
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
		</td>
	</tr>
<? if($USER->authorize('sendphone')) { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Phone:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%" >Send Phone Calls <? print help('Job_PhoneOptions', null, 'small'); ?></td>
					<td><? NewFormItem($f,$s,"sendphone","checkbox",NULL,NULL,"id='sendphone' " . ($submittedmode ? "DISABLED" : "")); ?>Phone</td>
				</tr>
				<tr>
					<td>Default Message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
					<td><? message_select('phone',$f,$s,"phonemessageid", NULL, NULL, ($submittedmode ? "DISABLED" : "")); ?></td>
				</tr>
<? if($USER->authorize('sendmulti')) { ?>

				<tr>
					<td>Multilingual message options <?= help('Job_MultilingualPhoneOption',NULL,"small"); ?></td>
					<td><? alternate('phone'); ?></td>
				</tr>
<? } ?>
				<tr>
					<td>Maximum attempts <?= help('Job_PhoneMaxAttempts', NULL, 'small')  ?></td>
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
							<td><? NewFormItem($f,$s,"callerid","text", 20, 20, ($completedmode ? "DISABLED" : "")); ?></td>
					</tr>
				<? } ?>

				<tr>
					<td>Skip Duplicate Phone Numbers <?=  help('Job_PhoneSkipDuplicates', NULL, 'small') ?></td>
					<td><? NewFormItem($f,$s,"skipduplicates","checkbox",1, NULL, ($completedmode ? "DISABLED" : "")); ?>Skip Duplicates</td>
				</tr>
				<tr>
					<td>Call every available phone number for each person <?= help('Job_PhoneCallAll', NULL, 'small') ?></td>
					<td><? NewFormItem($f,$s,"callall","checkbox",1, NULL, ($completedmode ? "DISABLED" : "")); ?>Call all phone numbers</td>
				</tr>
			</table>
		</td>
	</tr>
<? } ?>
<? if($USER->authorize('sendemail')) { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Email:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%" >Send Emails <? print help('Job_EmailOptions', null, 'small'); ?></td>
					<td><? NewFormItem($f,$s,"sendemail","checkbox",NULL,NULL,"id='sendemail' " . ($submittedmode ? "DISABLED" : "")); ?>Email</td>
				</tr>
				<tr>
					<td>Default Message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
					<td><? message_select('email',$f, $s,"emailmessageid", NULL, NULL, ($submittedmode ? "DISABLED" : "")); ?></td>
				</tr>
<? if($USER->authorize('sendmulti')) { ?>
				<tr>
					<td>Multilingual message options <?= help('Job_MultilingualEmailOption',NULL,"small"); ?></td>
					<td><? alternate('email'); ?></td>
				</tr>
<? } ?>
				<tr>
					<td>Skip Duplicate Email Addresses</td>
					<td><? NewFormItem($f,$s,"skipemailduplicates","checkbox",1, NULL, ($completedmode ? "DISABLED" : "")); ?>Skip Duplicates</td>
				</tr>
			</table>
		</td>
	</tr>
<? } ?>
<? if($USER->authorize('sendprint')) { ?>
	<tr valign="top">
		<th align="right" valign="top" class="windowRowHeader">Print</th>
		<td>
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%" >Send Printed Letters <? print help('Job_PrintOptions', null, 'small'); ?></td>
					<td><? NewFormItem($f,$s,"sendprint","checkbox",NULL,NULL,"id='sendprint' " . ($submittedmode ? "DISABLED" : "")); ?>Print</td>
				</tr>
				<tr>
					<td>Default Message <?= help('Job_PhoneDefaultMessage', NULL, 'small') ?></td>
					<td><? message_select('print', $f, $s, "printmessageid", NULL, NULL, ($submittedmode ? "DISABLED" : "")); ?></td>
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
		</td>
	</tr>
<? } ?>
</table>
<?
endWindow();

buttons();
EndForm();
include_once("navbottom.inc.php");

?>
<script language="javascript">
sections = Array('phone', 'email', 'print');
for(section in sections) {
	var chk = new getObj('send' + sections[section]).obj;
	if (chk) {
		chk.sel = new getObj(sections[section] + 'messageid').obj;
		chk.sel.chk = chk;
		chk.onchange = fchk;
		chk.sel.onchange = fsel;
	}
}
function fchk() { if(this.sel.options.length < 2) this.checked = false; }
function fsel() { this.chk.checked = this.selectedIndex; }

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

</script>
