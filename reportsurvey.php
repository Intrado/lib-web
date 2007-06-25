<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Phone.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("inc/form.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/SurveyReport.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

$_SESSION['reporttype'] = "surveyreport";

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	//unset($_SESSION['report']['options']);
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Standard Survey Report" . ((isset($jobid) && $jobid) ? " - " . $job->name : "");

include_once("nav.inc.php");
echo buttons(button('done', 'location.href="reports.php"'));


//--------------- Select window ---------------
startWindow("Select", NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
<tr>
	<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Job:</th>
	<td width="1%">
	<select name="jobid" id="jobid" onchange="location.href='reportjobsurvey.php?jobid=' + this.value">
			<option value='0'>-- Select a Survey --</option>
<?
	$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') and questionnaireid is not null order by id desc");


foreach ($jobs as $job) {
	echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
}
?>
	</select>
	<select id="jobid_archived" style="display: none" onchange="location.href='?jobid=' + this.value">
			<option value='0'>-- Select a Job --</option>
<?
	$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 2 and status in ('active','complete','cancelled','cancelling') and questionnaireid is not null order by id desc");

foreach ($jobs as $job) {
	echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
}
?>
	</select></td>
	<td aligh="left"><input id="check_archived" type="checkbox" name="check_archived" value="true" onclick = "setHiddenIfChecked(this, 'jobid'); setVisibleIfChecked(this, 'jobid_archived'); ">
	Show archived jobs</td>
	</tr>
	</table>

<?
endWindow();
?>
<br>
<?