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
require_once("obj/SurveyReport.obj.php");
require_once("obj/JobSummaryReport.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_survey_graph($row, $index){
	global $jobid;
	echo "<div><img src=\"graph_survey_result.png.php?jobid=" . $jobid . "&question=" . ($row[0] -1) . "&valid=".$row[14] ."\"></div>";
	
}

function fmt_question($row, $index){
	return "<div style='text-decoration: underline'>$row[$index]</div>";	
}

function fmt_answer($row, $index){
	$offset = $index+12;
	return "<div style='font-weight:bold; text-decoration: underline'>" . (isset($row[$offset]) ? $row[$offset] : "") . "</div><br><div>$row[$index]</div>";	
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$fields = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");

$orders = array("order1", "order2", "order3");


if(isset($_REQUEST['reportid'])){
	if(!userOwns("reportsubscription", $_REQUEST['reportid']+0)){
		redirect('unauthorized.php');
	}
	$subscription = new ReportSubscription($_REQUEST['reportid']+0);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();

	$jobid = isset($options['jobid']) ? $options['jobid'] : 0;
	
	$activefields = $instance->getActiveFields();
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['fields'][$field->fieldnum] = false;
		}
	}
	if($jobid)
		$job = new Job($jobid);
	$_SESSION['reportid'] = $_REQUEST['reportid']+0;
} else {
	$jobid = 0;
	$options = array();
	if (isset($_GET['jobid'])) {
		$jobid = $_GET['jobid'] + 0;
		$options["reporttype"] = "jobsummaryreport";
	} else {
		$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
		$jobid = isset($options['jobid']) ? $options['jobid'] : 0;
	}
	
	if($jobid){
		
		//check userowns or customerowns and viewsystemreports
		if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports') && customerOwns("job",$jobid))) {
			redirect('unauthorized.php');
		}
		
		unset($_SESSION['jobstats'][$jobid]);
		$job = new Job($jobid);
		$options['jobid'] = $jobid;

		$_SESSION['report']['options'] = $options;
	}
	
	$activefields = array();
	$fieldlist = array();
	foreach($fields as $field){
		// used in html
		$fieldlist[$field->fieldnum] = $field->name;
		
		// used in pdf
		if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum; 
		}
	}
	
	$instance = new ReportInstance();
	$instance->setParameters($options);
	$subscription = new ReportSubscription();
	$subscription->createDefaults(fmt_report_name($options['reporttype']));
}


$generator = new JobSummaryReport();
$generator->reportinstance = $instance;
$generator->userid = $USER->id;

if(isset($_SESSION['reportid'])){
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}

$_SESSION['report']['options'] = $options;

if(isset($_REQUEST['csv']) && $_REQUEST['csv']){
	$generator->format = "csv";
} else if(isset($_REQUEST['pdf']) && $_REQUEST['pdf']){
	$generator->format = "pdf";
} else {
	$generator->format = "html";
}

$reload=0;
$f="jobsurvey";
$s="save";

if(CheckFormSubmit($f,$s)){
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$instance->setFields($fieldlist);
			$instance->setActiveFields($activefields);
			$instance->setParameters($options);
			$instance->update();
			$subscription->reportinstanceid = $instance->id;
			$subscription->update();
			$_SESSION['reportid'] = $subscription->id;
			redirect("reportedit.php?reportid=" . $subscription->id);
		}
	}
} else {
	$reload=1;
}

if($reload)
	ClearFormData($f);
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

if($generator->format != "html"){
	if($generator->format == "pdf"){
		$name = secure_reportname();
		$params = createPdfParams($name);
		$result = $generator->generate($params);
		
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
		$generator->generate();
	}
} else {
	
	$PAGE = "reports:reports";
	$TITLE = "Job Summary Report" . ((isset($jobid) && $jobid) ? " - " . $job->name : "");
	include_once("nav.inc.php");
	NewForm($f);
	//TODO buttons for notification log: download csv, view call details
	buttons(button('back', 'window.history.go(-1)'),button('done', null, 'reports.php'), submit($f, $s, "save", "save"),button('refresh', 'window.location.reload()'));
	
		startWindow("Related Links", "padding: 3px;");
		?>
		<table border="0" cellpadding="3" cellspacing="0" width="100%">
			<tr>
				<td>
					<a href="reportjobsummary.php?pdf=1">PDF</a>
				</td>
				<td>
					<a href="" onclick="popup('report_graph_hourly.png.php',500,500)"/>Time Distribution</a>
				</td>
			</tr>
		</table>
		<?
	endWindow();
	
	?><br><?

	$generator->generate();

	buttons();
	endForm();
	include_once("navbottom.inc.php");
}
?>
