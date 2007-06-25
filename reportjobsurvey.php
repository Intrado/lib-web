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
require_once("obj/JobReport.obj.php");
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
	return "<div style='font-weight:bold; text-decoration: underline'>$row[$index]</div><br><div>$row[2]</div>";	
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
	$reportinstance = new ReportInstance($_REQUEST['reportid']);
	$options = $reportinstance->getParameters();
	if($options['reporttype'] == "surveyreport"){
		$reportgenerator = new SurveyReport();
	} else {
		$reportgenerator = new JobReport();
	}
	$reportgenerator->reportinstance = $reportinstance;
	$reportgenerator->format = "html";
	$reportgenerator->userid = $USER->id;

	$jobid = $options['jobid'];
	
	$activefields = $reportinstance->getActiveFields();
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['fields'][$field->fieldnum] = false;
		}
	}
	$job = new Job($jobid);
	$_SESSION['saved_report'] = true;
} else {
	if (isset($_GET['jobid'])) {
		$jobid = $_GET['jobid'] + 0;
		//check userowns or customerowns and viewsystemreports
		if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports') && customerOwns("job",$jobid))) {
			redirect('unauthorized.php');
		}
		if ($jobid) {
		
			$options = array("jobid" => $jobid);
			unset($_SESSION['jobstats'][$jobid]);
			$job = new Job($jobid);	
			
			$options["reporttype"] = $_SESSION['reporttype'];

		}
		$_SESSION['saved_report'] = false;
		$options=array("jobid" => $jobid);
		$_SESSION['report']['options'] = $options;
	} else {
		$options = $_SESSION['report']['options'];
	}
	$reportinstance = new ReportInstance();
	$reportinstance->setParameters($options);
	if($options['reporttype'] == "surveyreport"){
		$reportgenerator = new SurveyReport();
	} else {
		$reportgenerator = new JobReport();
	}
	$reportgenerator->reportinstance = $reportinstance;
	$reportgenerator->format = "html";
	$reportgenerator->userid = $USER->id;
}
$_SESSION['reporttype'] = $options['reporttype'];

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
if($_SESSION['reporttype'] == "surveyreport"){
	$TITLE = "Standard Survey Report" . ((isset($jobid) && $jobid) ? " - " . $job->name : "");
} else {
	$TITLE = "Standard Job Report" . ((isset($jobid) && $jobid) ? " - " . $job->name : "");
}
include_once("nav.inc.php");

//TODO buttons for notification log: download csv, view call details
if (isset($jobid) && $jobid)
	echo buttons(button('refresh', 'window.location.reload()'), button('done', 'location.href=\'reports.php\''));
else
	buttons();

if(isset($reportgenerator)){
	$reportgenerator->generate();
}

echo buttons();
endForm();
include_once("navbottom.inc.php");
?>
