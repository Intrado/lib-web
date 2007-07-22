<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/form.inc.php");
require_once("inc/reportutils.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/JobSummaryReport.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$f="reports";
$s="jobs";
$reload=0;

if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	unset($_SESSION['report']['options']);
	unset($_SESSION['reportid']);
	redirect();
}

if(isset($_REQUEST['reportid'])){
	$_SESSION['reportid'] = $_REQUEST['reportid']+0;
	redirect();
}

if(isset($_SESSION['reportid'])){
	if(!userOwns("reportsubscription", $_SESSION['reportid']+0)){
		redirect('unauthorized.php');
	}
	$subscription = new ReportSubscription($_SESSION['reportid']+0);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	
	$_SESSION['saved_report'] = true;
	
	if($options['reporttype']!="jobreport"){
		error_log("Expected job report, got something else");
	}
} else {
	$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	$options['reporttype'] = "jobsummaryreport";
	$_SESSION['saved_report'] = false;

	$subscription = new ReportSubscription();
	$subscription->createDefaults(fmt_report_name($options['reporttype']));
	$instance = new ReportInstance();

}

$_SESSION['report']['options'] = $options;

if(CheckFormSubmit($f, $s) || CheckFormSubmit($f, "save") || CheckFormSubmit($f, "run") || CheckFormSubmit($f, "saveview"))
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
		} else if(GetFormData($f, $s, "radioselect") != "1" && !strtotime($datestart)){
			error('Beginning Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if(GetFormData($f, $s, "radioselect") != "1" && !strtotime($dateend)){
			error('Ending Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else {
			$error=false;
			$radio = GetFormData($f, $s, "radioselect");
			switch($radio){
				case "1":
					$check = GetFormData($f, $s, "check_archived");
					if($check)
						$options['jobid'] = GetFormData($f, $s, "jobid_archived");
					else
						$options['jobid'] = GetFormData($f, $s, "jobid");
					if(!$options['jobid']){
						error("You Must Pick A job");
						$error = true;
					}
					$options['archived'] = $check;
					break;
				case "2":
					unset($options['archived']);
					unset($options['jobid']);
					$options['datestart'] = $datestart;
					$options['dateend'] = $dateend;
					break;
			}
			
			foreach($options as $index => $option){
				if($option == "")
					unset($options[$index]);
			}
			
			$options['reporttype'] = "jobsummaryreport";
			$_SESSION['report']['options'] = $options;
			
			if(!$error && (CheckFormSubmit($f, "save") || CheckFormSubmit($f, "saveview"))){
				
				$instance->setParameters($options);
				$instance->update();
				$subscription->reportinstanceid = $instance->id;
				$subscription->update();
				$_SESSION['reportid'] = $subscription->id;
				if(CheckFormSubmit($f, "save"))
					redirect("reportedit.php?reportid=" . $subscription->id);
			}
			if(!$error && (CheckFormSubmit($f, "run") || CheckFormSubmit($f, "saveview")))
				redirect("reportjobsummary.php");
		}
	}
} else {
	$reload=1;
}


if($reload){
	ClearFormData($f, $s);
	if(!isset($options['datestart']))
		$radio = 1;
	else
		$radio = 2;
	PutFormData($f, $s, "radioselect", $radio);
	PutFormData($f, $s, "datestart", isset($options['datestart']) ? $options['datestart'] : "", "text");
	PutFormData($f, $s, "dateend", isset($options['dateend']) ? $options['dateend'] : "", "text");
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

	$TITLE = "Job Report" . ((isset($jobid) && $jobid) ? " - " . $job->name : "");

include_once("nav.inc.php");
NewForm($f);
buttons( button('done', "location.href='reports.php'"), submit($f, "save", "save", "save"),
			isset($_SESSION['reportid']) ? submit($f, "saveview", "saveview", "Save and View") : submit($f, "run", "View Report", "View Report"));

//--------------- Select window ---------------
startWindow("Select", NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Job Report Options:</th>
		<td class="bottomBorder">
			<table>
				<tr>
					<td>
						<table>
							<tr>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "1", "onclick='hide(\"daterange\"); show(\"jobs\")'");?> Job</td>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "2", "onclick='hide(\"jobs\"); show(\"daterange\")'");?> DateRange</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellpadding="3" cellspacing="0" width="100%" id="daterange" >
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
								<?
									NewFormItem($f, $s, "jobid", "selectstart", null, null, "id='jobid'");
									NewFormItem($f, $s, "jobid", "selectoption", "-- Select a Job --", "");
									$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') order by id desc");
							
									foreach ($jobs as $job) {
										NewFormItem($f, $s, "jobid", "selectoption", $job->name, $job->id);
									}
									NewFormItem($f, $s, "jobid", "selectend");
									NewFormItem($f, $s, "jobid_archived", "selectstart", null, null, "id='jobid_archived' style='display: none'");
									NewFormItem($f, $s, "jobid_archived", "selectoption", "-- Select a Job --", "");
									$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 2 and status!='repeating' order by id desc");
									foreach ($jobs as $job) {
										NewFormItem($f, $s, "jobid_archived", "selectoption", $job->name, $job->id);
									}
									NewFormItem($f, $s, "jobid_archived", "selectend");
								?>
								</td>
								<td aligh="left"><? NewFormItem($f, $s, "check_archived", "checkbox", null, null, "id='check_archived' onclick = \"setHiddenIfChecked(this, 'jobid'); setVisibleIfChecked(this, 'jobid_archived');\"") ?>
								Show archived jobs</td>
							</tr>
						</table>
					</td>
				</tr>
				
			</table>
		</td>
	</tr>
</table>
<script>
	setHiddenIfChecked('check_archived', 'jobid');
	setVisibleIfChecked('check_archived', 'jobid_archived');
	<?
		if(!isset($options['datestart'])){
			?>hide('daterange');<?
		} else {
			?>hide('jobs');<?
		}
	?>
</script>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>