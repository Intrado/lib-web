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

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_attempts ($row,$index) {

	return $row[$index];
	/*
	if ($row[$index] !== NULL && $row[$index] !== "") {
		if ($row[3] == "phone") {
			return $row[$index] . "/" . $row[];
		} else {
			return $row[$index] . "/1";
		}
	} else {
		return "";
	}
	*/
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$f="report";
$s="order";

$reload = 0;
$pagestart=0;
if(isset($_GET['pagestart'])){
	$pagestart = $_GET['pagestart'];
}

$fields = FieldMap::getOptionalAuthorizedFieldMaps();
$ordering = JobDetailReport::getOrdering();
$ordercount=3;


if(isset($_REQUEST['reportid'])){
	if(!userOwns("reportsubscription", $_REQUEST['reportid']+0)){
		redirect("unauthorized.php");
	}
	$_SESSION['reportid'] = $_REQUEST['reportid']+0;
	
	$subscription = new ReportSubscription($_SESSION['reportid']+0);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	if($options['reporttype'] == "phonedetail"){
		$_SESSION['report']['type'] = "phone";
	} else if($options['reporttype'] == "emaildetail"){
		$_SESSION['report']['type'] = "email";
	} else {
		error_log("Wrong report type recieved: " . $options['reporttype'] . " Check links on other page.");
	}
	$activefields = array();
	if(isset($options['activefields'])){
		$activefields = explode(",", $options['activefields']) ;
	}
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['fields'][$field->fieldnum] = false;
		}
	}
	redirect();
} else if(isset($_REQUEST['type'])){

	$options = $_SESSION['report']['options'];
	$_SESSION['report']['type'] = $_REQUEST['type'];
	if($_REQUEST['type'] == "phone"){
		$options['reporttype'] = "phonedetail";
	} else if($_REQUEST['type'] == "email"){
		$options['reporttype'] = "emaildetail";
	}
	unset($options['result']);

	$_SESSION['report']['options'] = $options;
	redirect();
} else if(isset($_REQUEST['result'])){
	unset($_SESSION['reportid']);
	unset($_SESSION['report']['type']);
	$options = $_SESSION['report']['options'];
	$options['result'] = $_REQUEST['result'];

	if($_REQUEST['result'] == "sent" || $_REQUEST['result'] == "unsent"){
		$options['reporttype']="emaildetail";
	} else if($_REQUEST['result'] == "undelivered"){
		$options['reporttype'] = "notcontacted";
	} else {
		$options['reporttype']="phonedetail";
	}
	$_SESSION['report']['options'] = $options;
	redirect();
} else {

	$options = $_SESSION['report']['options'];
	$options["pagestart"] = $pagestart;

	if(!isset($_SESSION['reportid']))
		$_SESSION['saved_report'] = false;
	

	$activefields = array();
	foreach($fields as $field){
		// used in pdf,csv
		if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum; 
		}
	}
	$options['activefields'] = implode(",",$activefields);
	$instance = new ReportInstance();
}

if(isset($_SESSION['reportid'])){
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}
if(isset($options['jobid'])){
	$jobid = $options['jobid'];
	if (!(userOwns("job",$jobid) || $USER->authorize('viewsystemreports')) && customerOwns("job",$jobid))
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

if(isset($_REQUEST['csv']) && $_REQUEST['csv']){
	$reportgenerator->format = "csv";
} else if(isset($_REQUEST['pdf']) && $_REQUEST['pdf']){
	$reportgenerator->format = "pdf";
} else {
	$reportgenerator->format = "html";
}

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, "save"))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$options = $instance->getParameters();
			for($i=1; $i<=$ordercount; $i++){
				$options["order$i"] = GetFormData($f, $s, "order$i");
			}		
			$_SESSION['report']['options']= $options;
			
			if(CheckFormSubmit($f, "save")){
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

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

if($reportgenerator->format != "html"){

	if($reportgenerator->format == "pdf"){
		$name = secure_tmpname("report", ".pdf");
		$params = createPdfParams($name);

		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=$name");
		header("Content-type: application/pdf");	
		session_write_close();
		$reportgenerator->generate($params);
		@readfile($name);
		unlink($name);
	} else {
		$reportgenerator->generate();
	}
} else {
	
	$PAGE = "reports:reports";
	$TITLE = "Phone Log";
	if(isset($_SESSION['report']['options']['reporttype'])){
		if($_SESSION['report']['options']['reporttype'] == "phonedetail"){
			$TITLE = "Phone Log";
		} else if($_SESSION['report']['options']['reporttype'] == "emaildetail"){
			$TITLE = "Email Log";
		}
	} else if(isset($options['reporttype']) && $options['reporttype'] == "notcontacted"){
		$TITLE = "Not Contacted";
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
	buttons(button("Back", "window.history.go(-1)"), submit($f, "save", "Save/Schedule"), submit($f, $s, "Refresh"));
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
			<td class="bottomBorder"><a href="reportjobdetails.php?csv=true">CSV</a>&nbsp;|&nbsp;<a href="reportjobdetails.php?pdf=true">PDF</a></td>
		</tr>
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
