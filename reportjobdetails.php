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
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/form.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("inc/date.inc.php");
require_once("obj/JobDetailReport.obj.php");
require_once("obj/JobType.obj.php");
require_once("inc/rulesutils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$fields = FieldMap::getOptionalAuthorizedFieldMaps();

unset($_SESSION['report']['edit']);
$redirect = 0;
if(isset($_GET['reportid'])){
	$_SESSION['reportid'] = $_GET['reportid']+0;
	if(!userOwns("reportsubscription", $_SESSION['reportid'])){
		redirect("unauthorized.php");
	}
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	$activefields = array();
	if(isset($options['activefields'])){
		$activefields = explode(",", $options['activefields']) ;
	}
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['report']['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['report']['fields'][$field->fieldnum] = false;
		}
	}
	$_SESSION['report']['options'] = $options;
	redirect();
}

if(isset($_GET['type'])){
	$_SESSION['report']['jobdetail']=1;
	$options = $_SESSION['report']['options'];
	if($_GET['type'] == "phone"){
		$options['reporttype'] = "phonedetail";
	} else if($_GET['type'] == "email"){
		$options['reporttype'] = "emaildetail";
	} else if($_GET['type'] == "sms"){
		$options['reporttype'] = "smsdetail";
	}
	unset($options['result']);
	unset($options['status']);
	$options['order1'] = 'rp.pkey';
	$_SESSION['report']['options'] = $options;
	$redirect = 1;
}

if(isset($_GET['status'])){
	$_SESSION['report']['jobdetail']=1;
	unset($_SESSION['reportid']);
	$options = $_SESSION['report']['options'];
	$options['status'] = DBSafe($_GET['status']);
	$options['order1'] = 'rp.pkey';
	$_SESSION['report']['options'] = $options;
	$redirect = 1;
}

if(isset($_GET['result'])){
	$_SESSION['report']['jobdetail']=1;
	unset($_SESSION['reportid']);
	$options = $_SESSION['report']['options'];
	$options['result'] = DBSafe($_GET['result']);

	if($_GET['result'] == "undelivered"){
		$options['reporttype'] = "notcontacted";
	} else {
		$options['reporttype']="phonedetail";
	}
	$options['order1'] = 'rp.pkey';
	$_SESSION['report']['options'] = $options;
	$redirect = 1;
}

if($redirect)
	redirect();

$ordering = JobDetailReport::getOrdering();
$ordercount=3;

$pagestartflag=0;
$pagestart=0;
if(isset($_GET['pagestart'])){
	$pagestart = $_GET['pagestart']+0;
	$pagestartflag=1;
}

$options = $_SESSION['report']['options'];
$options["pagestart"] = $pagestart;

if(!isset($_SESSION['reportid']))
	$_SESSION['saved_report'] = false;

$activefields = array();
foreach($fields as $field){
	// used in pdf,csv
	if(isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
		$activefields[] = $field->fieldnum; 
	}
}
$options['activefields'] = implode(",",$activefields);
$instance = new ReportInstance();


if(isset($_SESSION['reportid'])){
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}
if(isset($options['jobid'])){
	$jobid = $options['jobid'];
	if (!(userOwns("job",$jobid) || $USER->authorize('viewsystemreports')))
		redirect('unauthorized.php');
}

if(isset($jobid)){
	$job = new Job($jobid);	
}

$_SESSION['report']['options'] = $options;

$options['pagestart'] = $pagestart;

$instance->setParameters($options);
$reportgenerator = new JobDetailReport();
$reportgenerator->reportinstance = $instance;
$reportgenerator->userid = $USER->id;

if(isset($_GET['csv']) && $_GET['csv']){
	$reportgenerator->format = "csv";
} else if(isset($_GET['pdf']) && $_GET['pdf']){
	$reportgenerator->format = "pdf";
} else {
	$reportgenerator->format = "html";
}


$f="reports";
$s="jobs";
$reload = 0;
$submit=0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, "save"))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$submit=1;
			$options = $instance->getParameters();
			$hideinprogress = GetFormData($f, $s, "hideinprogress");
			if($hideinprogress)
				$options['hideinprogress'] = "true";
			else
				$options['hideinprogress'] = "false";
			for($i=1; $i<=$ordercount; $i++){
				$options["order$i"] = DBSafe(GetFormData($f, $s, "order$i"));
			}		
			$_SESSION['report']['options']= $options;
			
			if(CheckFormSubmit($f, "save")){
				$_SESSION['report']['edit'] = 1;
				ClearFormData($f);
				redirect("reportedit.php");
			}
			redirect();
		}
	}
} else {
	$reload = 1;
}

if($reload){
	ClearFormData($f);
	for($i=1;$i<=$ordercount;$i++){
		$order="order$i";
		if($i==1){
			if(!isset($options[$order])){
				if(isset($_SESSION['reportid']))
					$orderquery = "";
				else
					$orderquery = "rp.pkey";
			} else
				$orderquery = $options[$order];
			PutFormData($f, $s, $order, $orderquery);
		} else {
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "");
		}
	}
	$hideinprog = 0;
	if(isset($options['hideinprogress']) && $options['hideinprogress'] == 'true')
		$hideinprog = 1;
	
	PutFormData($f, $s, "hideinprogress", $hideinprog, "bool", "0", "1");

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$error = false;
if($reportgenerator->format != "html"){

	if($reportgenerator->format == "pdf"){
		if($result = $reportgenerator->testSize()){
			error($result);
			$error = true;
		} else {
			$name = secure_tmpname("report", ".pdf");
			$params = createPdfParams($name);
	
			header("Pragma: private");
			header("Cache-Control: private");
			header("Content-disposition: attachment; filename=report.pdf");
			header("Content-type: application/pdf");	
			session_write_close();
			$reportgenerator->generate($params);
			@readfile($name);
			unlink($name);
		}
	} else {
		$reportgenerator->generate();
	}
}

if($error || $reportgenerator->format == "html"){
	$reportgenerator->format = "html";
	$PAGE = "reports:reports";
	$TITLE = "Phone Log";
	if(isset($_SESSION['report']['options']['reporttype']) && $_SESSION['report']['options']['reporttype'] == "phonedetail"){
		$TITLE = "Phone Log";
	} else if(isset($_SESSION['report']['options']['reporttype']) && $_SESSION['report']['options']['reporttype'] == "emaildetail"){
		$TITLE = "Email Log";
	} else if(isset($_SESSION['report']['options']['reporttype']) && $_SESSION['report']['options']['reporttype'] == "smsdetail"){
		$TITLE = "SMS Log";
	} else if(isset($_SESSION['report']['options']['reporttype']) && $_SESSION['report']['options']['reporttype'] == "notcontacted"){
		$TITLE = "Recipients Not Contacted";
	}
	if(isset($_SESSION['reportid'])){
		$subscription = new ReportSubscription($_SESSION['reportid']);
		$TITLE .= " - " . $subscription->name;
	} else if(isset($jobid)){
		$TITLE .= " - " . $job->name;
	}
	if(isset($options['reldate'])){
		list($startdate, $enddate) = getStartEndDate($options['reldate'], $options);
		$DESCRIPTION = "From: " . date("m/d/Y", $startdate) . " To: " . date("m/d/Y", $enddate);
	}
	
	include_once("nav.inc.php");
	NewForm($f);
	
	//check to see if referer came from summary page.  if so, go to history instead of referer
	if(isset($_SESSION['report']['jobdetail']) || $error || $submit || $pagestartflag)
		$back = button("Back", "window.history.go(-1)");
	else {
		$fallbackUrl = "reports.php";
		$back = button("Back", "location.href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");
	}
	buttons($back, submit($f, $s, "Refresh"), submit($f, "save", "Save/Schedule"));
	startWindow("Display Options", "padding: 3px;", "true");
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Display Fields:</th>
			<td class="bottomBorder">
	<? 		
				select_metadata('reportdetailstable', 9, $fields);
	?>
			</td>
		</tr>
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Sort By:</th>
			<td class="bottomBorder" >
<?
				selectOrderBy($f, $s, $ordercount, $ordering);
?>
			</td>
		<tr><th align="right" class="windowRowHeader bottomBorder">Output Format:</th>
			<td class="bottomBorder"><a href="reportjobdetails.php/report.csv?csv=true">CSV</a>&nbsp;|&nbsp;<a href="reportjobdetails.php/report.pdf?pdf=true">PDF</a></td>
		</tr>
<?
		if(isset($_SESSION['report']['options']['reporttype']) && $_SESSION['report']['options']['reporttype'] == "notcontacted"){
?>
			<tr><th align="right" class="windowRowHeader">Finalized Notifications:</th>
				<td class="bottomBorder"><?=NewFormItem($f, $s, "hideinprogress", "checkbox");?>Only Show Finalized Calls</td>
			</tr>
<?
		}
?>
	</table>
	<?
	endWindow();
	?>
	<br>
	<?
	
	if(isset($reportgenerator)){
		$reportgenerator->generate("detailed");
	}
	buttons();
	endForm();
	include_once("navbottom.inc.php");
}
?>
