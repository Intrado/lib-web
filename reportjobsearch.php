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
require_once("obj/JobType.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

//if this user can see systemwide reports, then lock them to the customerid
//otherwise lock them to jobs that they own
if (!$USER->authorize('viewsystemreports')) {
	$userJoin = " and userid = $USER->id ";
} else {
	$userJoin = "";
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$f="reports";
$s="jobs";
$reload=0;
$clear = 0;

if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	unset($_SESSION['report']['options']);
	unset($_SESSION['reportid']);
	$clear = 1;
}

if($clear)
	redirect();

$jobtypeobjs = DBFindMany("JobType", "from jobtype");
$jobtypes = array();
foreach($jobtypeobjs as $jobtype){
	$jobtypes[$jobtype->id] = $jobtype->name;
}

if(isset($_REQUEST['reportid'])){
	if(!userOwns("reportsubscription", $_REQUEST['reportid']+0)){
		redirect('unauthorized.php');
	}
	$_SESSION['reportid'] = $_REQUEST['reportid']+0;
	$subscription = new ReportSubscription($_SESSION['reportid']+0);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	
	$_SESSION['saved_report'] = true;
	$_SESSION['report']['options'] = $options;
} else {
	$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	if(isset($_SESSION['reportid'])){
		$subscription = new ReportSubscription($_SESSION['reportid']);
		$_SESSION['saved_report'] = true;
	} else {
		$_SESSION['saved_report'] = false;
	}
}

if(CheckFormSubmit($f, $s) || CheckFormSubmit($f, "save") || CheckFormSubmit($f, "view") || CheckFormSubmit($f, "saveview"))
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
		
		$startdate = GetformData($f, $s, "startdate");
		$enddate = GetFormData($f, $s, "enddate");
		
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(GetFormData($f, $s, "radioselect") == "date" && (GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($startdate)){
			error('Beginning Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if(GetFormData($f, $s, "radioselect") == "date" && (GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($enddate)){
			error('Ending Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if(GetFormData($f, $s, "radioselect") == "date" && (GetFormData($f, $s, "relativedate") == "xdays") && GetFormData($f, $s, "xdays") == ""){
			error('You must enter a number');
		} else if(GetFormData($f, $s, "radioselect") == "job" && !GetFormData($f, $s, "jobid_archived") && !GetFormData($f, $s, "jobid")){
			error('You must pick a job');
		} else {
			$options = array();
			$radio = GetFormData($f, $s, "radioselect");
			switch($radio){
				case "job":
					$check = GetFormData($f, $s, "check_archived");
					if($check)
						$options['jobid'] = GetFormData($f, $s, "jobid_archived");
					else
						$options['jobid'] = GetFormData($f, $s, "jobid");
					$options['archived'] = $check;
					break;
				case "date":
					$options['reldate'] = GetFormData($f, $s, "relativedate");
					
					if($options['reldate'] == "xdays"){
						$options['lastxdays'] = GetFormData($f, $s, "xdays");
					} else if($options['reldate'] == "daterange"){
						$options['startdate'] = $startdate;
						$options['enddate'] = $enddate;
					}
					break;
			}
			$savedjobtype = GetFormData($f, $s, "jobtypes");
			if($savedjobtype)
				$options['jobtypes'] = implode("','", $savedjobtype);
			else
				$options['jobtypes'] = "";
			
			foreach($options as $index => $option){
				if($option == "")
					unset($options[$index]);
			}
			
			$options['reporttype'] = "jobsummaryreport";
			$_SESSION['report']['options'] = $options;
			
			if(CheckFormSubmit($f, "save"))
				redirect("reportedit.php");
			if(CheckFormSubmit($f, "view"))
				redirect("reportjobsummary.php");
		}
	}
} else {
	$reload=1;
}


if($reload){
	ClearFormData($f, $s);
	if(!isset($options['reldate']))
		$radio = "job";
	else
		$radio = "date";
	PutFormData($f, $s, "radioselect", $radio);
	PutFormData($f, $s, "relativedate", isset($options['reldate']) ? $options['reldate'] : "today");
	PutFormData($f, $s, 'xdays', isset($options['lastxdays']) ? $options['lastxdays'] : "", "number");
	PutFormData($f, $s, "startdate", isset($options['startdate']) ? $options['startdate'] : "", "text");
	PutFormData($f, $s, "enddate", isset($options['enddate']) ? $options['enddate'] : "", "text");
	if(isset($options['archived']) && $options['archived']){
		PutFormData($f, $s, "jobid", "");
		PutFormData($f, $s, "jobid_archived", isset($options['jobid']) ? $options['jobid'] : "");
	} else {
		PutFormData($f, $s, "jobid", isset($options['jobid']) ? $options['jobid']: "");
		PutFormData($f, $s, "jobid_archived", "");
	}
	$savedjobtypes = array();
	if(isset($options['jobtypes'])){
		$savedjobtypes = explode("','", $options['jobtypes']);
	}
	PutFormData($f, $s, 'jobtype', isset($options['jobtypes']) && $options['jobtypes'] !="" ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, 'jobtypes', $savedjobtypes, "array", array_keys($jobtypes));
	
	PutFormData($f, $s, "check_archived", isset($options['archived']) ? $options['archived'] : 0, "bool", "0", "1");
	
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";

$TITLE = "Notification Summary";
if(isset($_SESSION['reportid'])){
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$TITLE .= " - " . $subscription->name;
} else if((isset($jobid) && $jobid)){
	$TITLE .= " - " . $job->name;
}
include_once("nav.inc.php");
NewForm($f);
buttons( button('Back', "location.href='reports.php'"), submit($f, "save", "Save/Schedule"),
			 submit($f, "view", "View Report"));

//--------------- Select window ---------------
startWindow("Select", NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Report Options:</th>
		<td class="bottomBorder">
			<table>
				<tr>
					<td>
						<table>
							<tr>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "job", "id=\"job\" onclick='hide(\"daterange\"); show(\"jobs\")'");?> Job</td>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "date", "onclick='hide(\"jobs\"); show(\"daterange\")'");?> Date</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
<?
					dateOptions($f, $s, "daterange");
?>						
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
									$jobs = DBFindMany("Job","from job where deleted = 0 and status in ('active','complete','cancelled','cancelling') $userJoin and questionnaireid is null order by id desc ");
							
									foreach ($jobs as $job) {
										NewFormItem($f, $s, "jobid", "selectoption", $job->name, $job->id);
									}
									NewFormItem($f, $s, "jobid", "selectend");
									NewFormItem($f, $s, "jobid_archived", "selectstart", null, null, "id='jobid_archived' style='display: none'");
									NewFormItem($f, $s, "jobid_archived", "selectoption", "-- Select a Job --", "");
									$jobs = DBFindMany("Job","from job where deleted = 2 and status!='repeating' $userJoin and questionnaireid is null order by id desc");
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
	<tr valign="top">
		<th align="right" class="windowRowHeader">Filter by:</th>
		<td>
			<table>
				<tr>
					<td>
						<table>
							<tr valign="top">
								<td><? NewFormItem($f,$s,"jobtype","checkbox",NULL,NULL,'id="jobtype" onclick="clearAllIfNotChecked(this,\'jobtypeselect\');"'); ?></td>
								<td>Job Type: </td>
								<td>
									<?
									NewFormItem($f, $s, 'jobtypes', 'selectmultiple', count($jobtypes), $jobtypes, 'id="jobtypeselect" onmousedown="setChecked(\'jobtype\');"');
									?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<script>
		setHiddenIfChecked('check_archived', 'jobid');
		setVisibleIfChecked('check_archived', 'jobid_archived');
		if(new getObj("job").obj.checked){
			hide("daterange");
		} else {
			hide("jobs");
		}
	</script>
</table>

<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>