<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/SurveyQuestionnaire.obj.php");
include_once("obj/Phone.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hassurvey', true) || !$USER->authorize('survey')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	setCurrentSurvey($_GET['id']);
	if ($jobid = getCurrentSurvey()) {
		$job = new Job($jobid);
		$_SESSION['scheduletemplate'] = $job->questionnaireid;
	} else {
		$_SESSION['scheduletemplate'] = null;
	}
	redirect();
}

if (isset($_GET['scheduletemplate'])) {
	$questionnaireid = $_GET['scheduletemplate'] + 0;

	if (userOwns("surveyquestionnaire",$questionnaireid)) {
		$_SESSION['scheduletemplate'] = $questionnaireid;
		setCurrentSurvey("new");
	}
	redirect();
}

$completedmode = false;
$submittedmode = false;

if (getCurrentSurvey() != NULL) {
	$job = new Job(getCurrentSurvey());

	if ('complete' == $job->status || 'cancelled' == $job->status || 'cancelling' == $job->status) {
		$completedmode = true;
	}

	if ($job->status == 'active' || $job->status == 'procactive' || $job->status == 'processing' || $job->status == 'scheduled' || $completedmode) {
		$submittedmode = true;
	}
}

$VALIDJOBTYPES = JobType::getUserJobTypes(true);
$PEOPLELISTS = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");
$QUESTIONNAIRES = array();
// if submitted or completed, gather only the selected questionnaireid
// because the schedulemanager copies the questionnaire setting deleted=1 when job is due to start
if ($submittedmode || $completedmode) {
	$QUESTIONNAIRES = DBFindMany("SurveyQuestionnaire", "from surveyquestionnaire where id=$job->questionnaireid");
} else {
	$QUESTIONNAIRES = DBFindMany("SurveyQuestionnaire", "from surveyquestionnaire where userid=$USER->id and deleted = 0 order by name");
}

/****************** main message section ******************/

$f = "survey";
$s = "main";
$reloadform = 0;


if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'send'))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		$name = trim(GetFormData($f,$s,"name"));
		if ( empty($name) ) {
			PutFormData($f,$s,"name",'',"text",1,50,true);
		}

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (strtotime(GetFormData($f,$s,"startdate")) === -1 || strtotime(GetFormData($f,$s,"startdate")) === false) {
			error('The start date is invalid');
		} else if (strtotime(GetFormData($f,$s,"starttime")) === -1 || strtotime(GetFormData($f,$s,"starttime")) === false) {
			error('The start time is invalid');
		} else if (strtotime(GetFormData($f,$s,"endtime")) === -1 || strtotime(GetFormData($f,$s,"endtime")) === false) {
			error('The end time is invalid');
		} else if (strtotime(GetFormData($f,$s,"endtime")) < strtotime(GetFormData($f,$s,"starttime")) ) {
			error('The end time cannot be before the start time');
		} else if ((strtotime(GetFormData($f,$s,"startdate"))+((GetFormData($f,$s,"numdays")-1)*86400) < strtotime("today")) && !$completedmode){
			error('The end date has already passed. Please correct this problem before proceeding');
		} else if ( (strtotime(GetFormData($f,$s,"startdate"))+((GetFormData($f,$s,"numdays")-1)*86400) == strtotime("today")) && (strtotime(GetFormData($f,$s,"endtime")) < strtotime("now")) && !$completedmode) {
			error('The end time has already passed. Please correct this problem before proceeding');
		} else if (QuickQuery("select count(*) from job where deleted = 0 and name = '" . DBsafe($name) . "' and userid = $USER->id and status in ('new','scheduled','processing','procactive','active','repeating') and id!= " . (0 + getCurrentSurvey()))) {
			error('A job or survey named \'' . $name . '\' already exists');
		} else if (GetFormData($f,$s,"callerid") != "" && strlen(Phone::parse(GetFormData($f,$s,"callerid"))) != 10) {
			error('The Caller ID must be exactly 10 digits long (including area code)');
		} else {

			$questionnaireid = GetFormData($f,$s,"questionnaireid") + 0;
			if (!userOwns("surveyquestionnaire",$questionnaireid))
				exit();

			$questionnaire = new SurveyQuestionnaire($questionnaireid);

			//submit changes
			$jobid = getCurrentSurvey();
			if ($jobid == null) {
				$job = Job::jobWithDefaults();
			} else {
				$job = new Job($jobid);
			}

			$job->questionnaireid = $questionnaireid;

			//set unchangable fields
			$job->type="survey";
			$job->sendphone = false;
			$job->phonemessageid = NULL;
			$job->sendemail = false;
			$job->emailmessageid = NULL;
			$job->sendprint = false;
			$job->printmessageid = NULL;

			//always skip dupes
			$job->setOption("skipduplicates",true);

			//only allow editing some fields
			if ($completedmode) {
				$job->name = $name;
				$job->description = trim(GetFormData($f,$s,"description"));
			} else if ($submittedmode) {
				$job->name = $name;
				$job->description = trim(GetFormData($f,$s,"description"));
				$fieldsarray = array("startdate", "starttime", "endtime");
				PopulateObject($f,$s,$job,$fieldsarray);
				if ($questionnaire->hasphone)
					$job->setOption("maxcallattempts", GetFormData($f, $s, 'maxcallattempts'));
				$job->startdate = GetFormData($f, $s, 'startdate');
								$numdays = GetFormData($f, $s, 'numdays');
				$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
			} else {
				$job->name = $name;
				$job->description = trim(GetFormData($f,$s,"description"));
				$fieldsarray = array("jobtypeid","listid",
							"starttime", "endtime","startdate");
				PopulateObject($f,$s,$job,$fieldsarray);
				if ($questionnaire->hasphone)
					$job->setOption("maxcallattempts", GetFormData($f, $s, 'maxcallattempts'));
				$job->startdate = GetFormData($f, $s, 'startdate');
				$numdays = GetFormData($f, $s, 'numdays');
				$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($numdays - 1) * 86400));
				if($USER->authorize("leavemessage")){
					if($questionnaire->leavemessage)
						$job->setOption("leavemessage", true);
					else
						$job->setOption("leavemessage", false);
				}
			}

			if(!$completedmode){
				$job->setOption("sendreport",GetFormData($f,$s,"sendreport"));
			}

			if ($questionnaire->hasphone && $USER->authorize('setcallerid')) {
				$job->setOptionValue("callerid",Phone::parse(GetFormData($f,$s,"callerid")));
			} else if ($questionnaire->hasphone) {
				$callerid = $USER->getSetting("callerid",getSystemSetting('callerid'));
				$job->setOptionValue("callerid", $callerid);
			}


			//reformat the dates & times to DB friendly format
			$job->startdate = date("Y-m-d", strtotime($job->startdate));
			$job->enddate = date("Y-m-d", strtotime($job->enddate));
			$job->starttime = date("H:i", strtotime($job->starttime));
			$job->endtime = date("H:i", strtotime($job->endtime));

			$job->update();

			setCurrentSurvey($job->id);

			ClearFormData($f);
			if (CheckFormSubmit($f,'send')) {
				redirect("surveyconfirm.php?id=" . $job->id);
			} else {
				redirect("surveys.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	$jobid = getCurrentSurvey();
	if ($jobid == null) {
		$job = Job::jobWithDefaults();
		$job->questionnaireid = $_SESSION['scheduletemplate'];
	} else {
		$job = new Job($jobid);
	}

	//beautify the dates & times
	$job->startdate = date("m/j/Y", strtotime($job->startdate));
	$job->enddate = date("F jS, Y", strtotime($job->enddate));
	$job->starttime = date("g:i a", strtotime($job->starttime));
	$job->endtime = date("g:i a", strtotime($job->endtime));


	$fields = array(
		array("name","text",1,50,true),
		array("description","text",1,50,false),
		array("jobtypeid","number","nomin","nomax", true),
		array("questionnaireid","number","nomin","nomax",true),
		array("listid","number","nomin","nomax",true),
		array("starttime","text",1,50,true),
		array("endtime","text",1,50,true),
		array('startdate','text', 1, 50, true),
	);

	PutFormData($f,$s,"maxcallattempts",$job->getOptionValue("maxcallattempts"), "number",1,$ACCESS->getValue('callmax'),true);

	PutFormData($f,$s,"callerid", Phone::format($job->getOptionValue("callerid")), "phone", 10, 10, false);

	PopulateForm($f,$s,$job,$fields);

	PutFormData($f,$s,"sendreport",$job->isOption("sendreport"), "bool",0,1);
	PutFormData($f, $s, 'numdays', (86400 + strtotime($job->enddate) - strtotime($job->startdate) ) / 86400, 'number', 1, $ACCESS->getValue('maxjobdays'), true);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:survey";
$TITLE = "Survey Scheduler: " . (getCurrentSurvey() == NULL ? "New Survey" : escapehtml($job->name));

include_once("nav.inc.php");
NewForm($f);

if ($submittedmode)
	buttons(submit($f, $s, 'Save'));
else
	buttons(submit($f, $s, 'Save For Later'),submit($f, 'send','Proceed To Confirmation'));

startWindow('Survey Information');
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:<br></th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td width="30%" >Survey Name <?= help('SurveyScheduler_SurveyName',NULL,"small"); ?></td>
					<td><? NewFormItem($f,$s,"name","text", 30,50); ?></td>
				</tr>
				<tr>
					<td>Description <?= help('SurveyScheduler_SurveyDesc',NULL,"small"); ?></td>
					<td><? NewFormItem($f,$s,"description","text", 30,50); ?></td>
				</tr>
				<tr>
					<td>Job Type <?= help('SurveyScheduler_Priority',NULL,"small"); ?></td>
					<td>
						<?

						NewFormItem($f,$s,"jobtypeid", "selectstart", NULL, NULL, ($submittedmode ? "DISABLED" : ""));
						NewFormItem($f,$s,"jobtypeid", "selectoption", " -- Select a Job Type -- ", "");
						foreach ($VALIDJOBTYPES as $item) {
							NewFormItem($f,$s,"jobtypeid", "selectoption", $item->name, $item->id);
						}
						NewFormItem($f,$s,"jobtypeid", "selectend");
						?>
					</td>
				</tr>
				<tr>
					<td>Survey Template <?= help('SurveyScheduler_SurveyTemplate',NULL,"small"); ?></td>
					<td>
						<?
						NewFormItem($f,$s,"questionnaireid", "selectstart", NULL, NULL, ($submittedmode ? "DISABLED" : 'id="questionnaireselect" onchange="checkphonesurvey(this.options[this.selectedIndex].value);"'));
						NewFormItem($f,$s,"questionnaireid", "selectoption", "-- Select a Template --", NULL);
						foreach ($QUESTIONNAIRES as $questionnaire) {
							NewFormItem($f,$s,"questionnaireid", "selectoption", $questionnaire->name, $questionnaire->id);
						}
						NewFormItem($f,$s,"questionnaireid", "selectend");
						?>
					</td>
				</tr>
				<tr>
					<td>List <?= help('SurveyScheduler_List',NULL,"small"); ?></td>
					<td>
						<?
						NewFormItem($f,$s,"listid", "selectstart", NULL, NULL, ($submittedmode ? "DISABLED" : ""));
						NewFormItem($f,$s,"listid", "selectoption", "-- Select a List --", NULL);
						foreach ($PEOPLELISTS as $plist) {
							NewFormItem($f,$s,"listid", "selectoption", $plist->name, $plist->id);
						}
						NewFormItem($f,$s,"listid", "selectend");
						?>
					</td>
				</tr>
				<tr>
					<td>Start Date <?= help('SurveyScheduler_StartDate',NULL,"small"); ?></td>
					<td><? NewFormItem($f,$s,"startdate","text", 30, NULL, ($completedmode ? "DISABLED" : "onfocus=\"this.select();lcs(this,false,true)\" onclick=\"event.cancelBubble=true;this.select();lcs(this,false,true)\"")); ?>
					</td>
				</tr>
				<tr>
					<td>Number of days to run <?= help('SurveyScheduler_NumberOfDays', NULL, "small"); ?></td>
					<td>
					<?
					NewFormItem($f, $s, 'numdays', "selectstart", NULL, NULL, ($completedmode ? "DISABLED" : ""));
					$maxdays = $ACCESS->getValue('maxjobdays');
					if ($maxdays == null) {
						$maxdays = 7; // Max out at 7 days if the permission is not set.
					}
					for ($i = 1; $i <= $maxdays; $i++) {
						NewFormItem($f, $s, 'numdays', "selectoption", $i, $i);
					}
					NewFormItem($f, $s, 'numdays', "selectend");
					?>
					</td>
				</tr>
				<tr>
					<td colspan="2">Survey Time Window:</td>
				<tr>
					<td>&nbsp;&nbsp;Earliest <?= help('SurveyScheduler_Earliest', NULL, 'small') ?></td>
					<td><? time_select($f,$s,"starttime", NULL, NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), ($completedmode ? "DISABLED" : "")); ?></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;Latest <?= help('SurveyScheduler_Latest', NULL, 'small') ?></td>
					<td><? time_select($f,$s,"endtime", NULL, NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), ($completedmode ? "DISABLED" : "")); ?></td>
				</tr>
				<tr>
					<td>Email a report when the survey completes <?= help('SurveyScheduler_EmailReport', NULL, 'small'); ?></td>
					<td><? NewFormItem($f,$s,"sendreport","checkbox",1, NULL, ($completedmode ? "DISABLED" : "")); ?>Report</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader">Phone:</th>
		<td>
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td width="30%">Maximum attempts <?= help('SurveyScheduler_MaxAttempts', NULL, 'small')  ?></td>
					<td>
						<?
						$max = first($ACCESS->getValue('callmax'), 1);
						NewFormItem($f,$s,"maxcallattempts","selectstart", NULL, NULL, ($completedmode ? "DISABLED" : 'dependson="phonesurvey"'));
						for($i = 1; $i <= $max; $i++) {
							NewFormItem($f,$s,"maxcallattempts","selectoption",$i,$i);
						}
						NewFormItem($f,$s,"maxcallattempts","selectend");
						?>
					</td>
				</tr>
<? if ($USER->authorize('setcallerid')) { ?>
					<tr>
						<td>Caller&nbsp;ID <?= help('SurveyScheduler_CallerID',NULL,"small"); ?></td>
						<td><? NewFormItem($f,$s,"callerid","text", 20, 20, ($completedmode ? "DISABLED" : 'dependson="phonesurvey"')); ?></td>
					</tr>
<? } ?>
			</table>
		</td>
	</tr>
</table>
<?
endWindow();


buttons();
EndForm();



$ids = array();
foreach ($QUESTIONNAIRES as $questionnaire) {
	$ids[] = $questionnaire->id . ":" . ($questionnaire->hasphone ? "true" : "false");
}

?>

<script>

var ids = {<?= implode(",",$ids) ?>};

function checkphonesurvey(id) {
	var callback = function(obj) {obj.disabled = !ids[id];};
	modifyMarkedNodes (document.forms[0],"dependson","phonesurvey",callback)
}

</script>
<script SRC="script/calendar.js"></script>

<?
include_once("navbottom.inc.php");
?>