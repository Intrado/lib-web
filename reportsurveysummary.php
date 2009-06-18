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

function fmt_questionnumber($row, $index){
	return "Question #" . ($row[$index]);
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$fields = FieldMap::getOptionalAuthorizedFieldMaps();
unset($_SESSION['report']['edit']);

if(isset($_GET['reportid'])){
	$reportid = $_GET['reportid'] +0;
	if(!userOwns("reportsubscription", $reportid)){
		redirect('unauthorized.php');
	}
	$subscription = new ReportSubscription($reportid);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();

	$jobid = isset($options['jobid']) ? $options['jobid'] : 0;

	if($jobid)
		$job = new Job($jobid);
	$_SESSION['reportid'] = $reportid;
	$_SESSION['report']['options'] = $options;
	$_SESSION['report']['surveysummary'] = 1;
} else {
	$jobid = 0;

	if (isset($_GET['jobid'])) {
		$jobid = $_GET['jobid'] + 0;
		$_SESSION['report']['surveysummary'] = 1;
	} else {
		$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
		$jobid = isset($options['jobid']) ? $options['jobid'] : 0;
	}
	$options['reporttype'] = "surveyreport";
	if($jobid){

		//check userowns and viewsystemreports
		if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports'))) {
			redirect('unauthorized.php');
		}

		unset($_SESSION['jobstats'][$jobid]);
		$job = new Job($jobid);
		$options['jobid'] = $jobid;
	}
	$_SESSION['report']['options'] = $options;
}

$instance = new ReportInstance();
$instance->setParameters($options);
$generator = new SurveyReport();
$generator->reportinstance = $instance;
$generator->userid = $USER->id;

if(isset($_SESSION['reportid'])){
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}

$_SESSION['report']['options'] = $options;

if(isset($_GET['csv']) && $_GET['csv']){
	$generator->format = "csv";
} else if(isset($_GET['pdf']) && $_GET['pdf']){
	$generator->format = "pdf";
} else {
	$generator->format = "html";
}

$reload=0;
$f="reports";
$s="survey";

if(CheckFormSubmit($f,$s)){
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$_SESSION['report']['edit'] = 1;
			redirect("reportedit.php");
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
		$name = secure_tmpname("report", ".pdf");
		$params = createPdfParams($name);
		$result = $generator->generate($params);

		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.pdf");
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
	$TITLE = "Survey Results" . ((isset($jobid) && $jobid) ? " - " . escapehtml($job->name) : "");

	include_once("nav.inc.php");
	NewForm($f);
	//TODO buttons for notification log: download csv, view call details
	if(isset($_SESSION['report']['surveysummary']))
		$back = button("Back", "window.history.go(-1)");
	else {
		$fallbackUrl = "reports.php";
		$back = button("Back", "location.href=' " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");
	}
	buttons($back, submit($f, $s, "Save/Schedule"),button('Refresh', 'window.location.reload()'));

		startWindow("Related Links", "padding: 3px;");
		?>
		<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<td>
					<a href="reportsurveysummary.php/report.pdf?pdf=1">PDF</a>&nbsp;|&nbsp;<a href="reportjobsummary.php?jobid=<?=$job->id?>&survey=true"/>Notification&nbsp;Summary</a>&nbsp;|&nbsp<a href="reportsurveysummary.php?csv=1"/>CSV</a>
				</td>
			</tr>
		</table>
		<?
	endWindow();

	$generator->generate();

	buttons();
	endForm();
	include_once("navbottom.inc.php");
}
?>
