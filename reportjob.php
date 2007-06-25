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
require_once("obj/JobReport.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	unset($_SESSION['report']['options']);
	redirect();
}
$reload=0;

$f="reports";
$s="jobs";

if(CheckFormSubmit($f, $s))
{
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		
		$datestart = GetformData($f, $s, "datestart");
		$dateend = GetFormData($f, $s, "dateend");
		
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if($datestart != "" && !strtotime($datestart)){
			error('Beginning Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if($dateend != "" && !strtotime($dateend)){
			error('Ending Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else {
			$options = array();
			$options['datestart'] = $datestart;
			$options['dateend'] = $dateend;
			$options['reporttype'] = "jobreport";
			$_SESSION['report']['options'] = $options;
			redirect("reportjobsurvey.php");
		}
	}
} else {
	$reload=1;
}


if($reload){
	ClearFormData($f, $s);
	PutFormData($f, $s, "radioselect", "1");
	PutFormData($f, $s, "datestart", "", "text");
	PutFormData($f, $s, "dateend", "", "text");

}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";

	$TITLE = "Standard Job Report" . ((isset($jobid) && $jobid) ? " - " . $job->name : "");

include_once("nav.inc.php");
NewForm($f);
echo buttons( button('done', 'location.href="reports.php"'), submit($f, $s, "Search", "search"));

//--------------- Select window ---------------
startWindow("Select", NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Job Report Options:</th>
		<td>
			<table>
				<tr >
					<td><? NewFormItem($f, $s, "radioselect", "radio", null, "1", "onclick='hide(\"daterange\"); show(\"jobs\")'");?> Job</td>
					<td><? NewFormItem($f, $s, "radioselect", "radio", null, "2", "onclick='hide(\"jobs\"); show(\"daterange\")'");?> DateRange</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellpadding="3" cellspacing="0" width="100%" id="daterange">
							<tr>
								<td>Date From: <? NewFormItem($f, $s, "datestart", "text", "20")?> To: <? NewFormItem($f, $s, "dateend", "text", "20") ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellpadding="3" cellspacing="0" width="100%" id="jobs">
							<tr>
								<td width="1%">
								<select name="jobid" id="jobid" onchange="location.href='reportjobsurvey.php?jobid=' + this.value">
										<option value='0'>-- Select a Job --</option>
						<?
								$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') order by id desc");
						
								foreach ($jobs as $job) {
										echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
								}
						?>
								</select>
								<select id="jobid_archived" style="display: none" onchange="location.href='?jobid=' + this.value">
										<option value='0'>-- Select a Job --</option>
						<?
						
								$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 2 and status!='repeating' order by id desc");
						
								foreach ($jobs as $job) {
										echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
								}
						?>
								</select>
								</td>
								<td aligh="left"><input id="check_archived" type="checkbox" name="check_archived" value="true" onclick = "setHiddenIfChecked(this, 'jobid'); setVisibleIfChecked(this, 'jobid_archived'); ">
								Show archived jobs</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?
endWindow();
echo buttons();
EndForm();
include_once("navbottom.inc.php");
?>

<script>
	hide("daterange");
</script>