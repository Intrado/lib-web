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
require_once("inc/form.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("inc/date.inc.php");
require_once("obj/JobReport.obj.php");

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

function fmt_message ($row,$index) {
	return '<img src="img/icon_' . $row[$index] . '_12.gif" align="bottom" />&nbsp;' . htmlentities($row[$index+1]);
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

$fields = getFieldMaps();
$ordering = JobReport::getOrdering();

$orders = array("order1", "order2", "order3");

if(isset($_SESSION['reportid'])){
	$subscription = new ReportSubscription($_SESSION['reportid']+0);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	
	$activefields = $instance->getActiveFields();
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['fields'][$field->fieldnum] = false;
		}
	}
	foreach($orders as $order){
		$_SESSION[$order] = isset($options[$order]) ? $options[$order] : "";
	}
} else {
	$options = $_SESSION['report']['options'];
	
	if(isset($_REQUEST['result'])){

		$options['result'] = $_REQUEST['result'];
		$_SESSION['report']['options'] = $options;
		redirect();
	}
	$options["detailed"] = true;
	$options["pagestart"] = $pagestart;
	
	$activefields = array();
	$fieldlist = array();
	foreach($fields as $field){
		// used in html
		$fieldlist[$field->fieldnum] = $field->name;
		
		// used in pdf,csv
		if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum; 
		}
	}
	$instance = new ReportInstance();
	$instance->setParameters($options);
	$instance->setFields($fieldlist);
	$instance->setActiveFields($activefields);
	$subscription = new ReportSubscription();
	$subscription->createDefaults("Job Report");
}
if(isset($options['jobid'])){
	$jobid = $options['jobid'];
	if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports') && customerOwns("job",$jobid)))
		redirect('unauthorized.php');
}

if(isset($jobid)){
	$job = new Job($jobid);	
}

$reportgenerator = new JobReport();
$reportgenerator->reportinstance = $instance;
$reportgenerator->userid = $USER->id;

if(isset($_REQUEST['csv']) && $_REQUEST['csv']){
	$reportgenerator->format = "csv";
} else if(isset($_REQUEST['pdf']) && $_REQUEST['pdf']){
	$reportgenerator->format = "pdf";
} else {
	$reportgenerator->format = "html";
}



if(CheckFormSubmit($f,$s))
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
			$orderquery = "";
			$options = $reportinstance->getParameters();
			foreach($orders as $order){
				$options[$order] = GetFormData($f, $s, $order);
			}

			$_SESSION['report']['options']= $options;
			redirect();
		}
	}
} else {
	$reload = 1;
}

if($reload){
	ClearFormData($f);
	foreach($orders as $order){
		if($order == "order1"){
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "rp.pkey");
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
		$reportgenerator->generate($params);
	
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=$name");
		header("Content-type: application/pdf");	
		session_write_close();
		$fp = fopen($name, "r");
		while($line = fgets($fp)){
			echo $line;
		}
		unlink($name);
	} else {
		$reportgenerator->generate();
	}
} else {
	
	$PAGE = "reports:reports";
	$TITLE = "Standard Job Report" . (isset($jobid) ? " - " . $job->name : "");
	
	include_once("nav.inc.php");
	NewForm($f);	
	buttons(button("back", "window.history.go(-1)"), submit($f, $s, "filter", "refresh"));
	startWindow("Display Options", "padding: 3px;");
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Fields:</th>
			<td class="bottomBorder">
	<? 		
				select_metadata('reportdetailstable', 7, $fields);
	?>
			</td>
		</tr>
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Sort by:</th>
			<td class="bottomBorder">
				<table>
					<tr>
	<?
					foreach($orders as $order){
	?>
					<td>
	<?
						NewFormItem($f, $s, $order, 'selectstart');
						NewFormItem($f, $s, $order, 'selectoption', " -- Not Selected --", "");
						foreach($ordering as $index => $item){
							NewFormItem($f, $s, $order, 'selectoption', $index, $item);
						}						
						NewFormItem($f, $s, $order, 'selectend');
	?>
					</td>
	<?
				}
	?>
				
					</tr>
				</table>
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
