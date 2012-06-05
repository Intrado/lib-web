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
require_once("inc/reportutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/form.inc.php");
require_once("obj/UserSetting.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

if (!$USER->authorize('viewsystemreports')) {
	$userjoin = " and j.userid = $USER->id ";
} else {
	$userjoin = "";
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['clear']) && $_GET['clear']){
	unset($_SESSION['reportid']);
	unset($_SESSION['report']['options']);
	redirect();
}
if(isset($_GET['reportid'])){
	$_SESSION['reportid'] = $_GET['reportid']+0;
	redirect();
}

if(isset($_SESSION['reportid'])){
	if(!userOwns("reportsubscription", $_SESSION['reportid'])){
		redirect('unauthorized.php');
	}
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	if($options['reporttype']!="surveyreport"){
		error_log("Expected survey report, got something else");
	}
	$_SESSION['saved_report'] = true;
} else {
	$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	$options['reporttype'] = "surveyreport";
	$_SESSION['saved_report'] = false;
}

$_SESSION['report']['options'] = $options;

$reload=0;

$f="reports";
$s="survey";

if(CheckFormSubmit($f, $s) || CheckFormSubmit($f, "save") || CheckFormSubmit($f, "run"))
{
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
				
		$check = GetFormData($f, $s, "check_archived");
		
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if((!$check && !GetFormData($f, $s, "jobid")) || ($check && !GetFormData($f, $s, "jobid_archived"))){
			error('You must pick a survey');
		} else {
			if($check)
				$options['jobid'] = GetFormData($f, $s, "jobid_archived")+0;
			else
				$options['jobid'] = GetFormData($f, $s, "jobid")+0;
			$options['archived'] = $check;
			$_SESSION['report']['options'] = $options;
			ClearFormData($f);
			if(CheckFormSubmit($f, "run"))
				redirect("reportsurveysummary.php");
			if((CheckFormSubmit($f, "save")))
				redirect("reportedit.php");
		}
	}
} else {
	$reload=1;
}


if($reload){
	ClearFormData($f, $s);
	if(isset($options['archived']) && $options['archived']){
		PutFormData($f, $s, "jobid", "");
		PutFormData($f, $s, "jobid_archived", isset($options['jobid']) ? $options['jobid'] : "");
	} else {
		PutFormData($f, $s, "jobid", isset($options['jobid']) ? $options['jobid']: "");
		PutFormData($f, $s, "jobid_archived", "");
	}
	PutFormData($f, $s, "check_archived", isset($options['archived']) ? $options['archived'] : 0, "bool", "0", "1");
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Survey Report" . ((isset($jobid) && $jobid) ? " - " . escapehtml($job->name) : "");

include_once("nav.inc.php");
NewForm($f);
buttons(button('Back', null, "reports.php"), submit($f, "run", "View Report"), submit($f, "save", "Save/Schedule"));


//--------------- Select window ---------------
startWindow("Select ".help('ReportSurvey_Select'), NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Survey:</th>
		<td>
		<table border="0" cellpadding="3" cellspacing="0" width="100%" id="jobs">
			<tr>
			<td width="1%">
				<table border="0" cellpadding="3" cellspacing="0" width="100%" id="jobs">
					<tr>
						<td width="1%">
						<?
							NewFormItem($f, $s, "jobid", "selectstart", null, null, "id='jobid'");
							NewFormItem($f, $s, "jobid", "selectoption", "-- Select a Survey --", "");
							$jobs = DBFindMany("Job","from job j where deleted = 0 and status in ('active','complete','cancelled','cancelling') and questionnaireid is not null $userjoin order by id desc limit 500");

							foreach ($jobs as $job) {
								NewFormItem($f, $s, "jobid", "selectoption", $job->name, $job->id);
							}
							NewFormItem($f, $s, "jobid", "selectend");
							NewFormItem($f, $s, "jobid_archived", "selectstart", null, null, "id='jobid_archived' style='display: none'");
							NewFormItem($f, $s, "jobid_archived", "selectoption", "-- Select a Survey --", "");
							$jobs = DBFindMany("Job","from job j where deleted = 2 and status in ('active','complete','cancelled','cancelling') and questionnaireid is not null $userjoin order by id desc limit 500");
							foreach ($jobs as $job) {
								NewFormItem($f, $s, "jobid_archived", "selectoption", $job->name, $job->id);
							}
							NewFormItem($f, $s, "jobid_archived", "selectend");
						?>
						</td>
						<td align="left"><? NewFormItem($f, $s, "check_archived", "checkbox", null, null, "id='check_archived' onclick = \"setHiddenIfChecked(this, 'jobid'); setVisibleIfChecked(this, 'jobid_archived');\"") ?>
						Show archived Surveys</td>
					</tr>
				</table>
			</td>
			</tr>
		</table>
		</td>
	</tr>
	<script>
		setHiddenIfChecked(new getObj('check_archived').obj, 'jobid');
		setVisibleIfChecked(new getObj('check_archived').obj, 'jobid_archived');
	</script>
</table>

<?
endWindow();
buttons();
EndForm();
include("navbottom.inc.php");
?>