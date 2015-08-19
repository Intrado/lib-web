<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Phone.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/reportutils.inc.php");
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/date.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/form.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/JobSummaryReport.obj.php");
require_once("obj/JobDetailReport.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/Person.obj.php");
require_once("inc/rulesutils.inc.php");

function fmt_dst_src($row, $index){
	if($row[$index] != null){
		$type = $row[5];
		$maxtypes = fetch_max_types();
		$actualsequence = isset($maxtypes[$type]) ? ($row[$index] % $maxtypes[$type]) : $row[$index];
		return escapehtml(destination_label($row[5], $actualsequence));
	}
	else
		return "";
}

if (!isset($_REQUEST["api"])) {
	header("HTTP/1.1 404 Not Found");
	exit(json_encode(Array("code" => "resourceNotFound")));
}

if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	header("HTTP/1.1 403 Forbidden");
	header('Content-Type: application/json');
	exit(json_encode(Array("code" => "accessDenied")));
}

// viewsystemreports
if (!$USER->authorize('viewsystemreports')) {
	header("HTTP/1.1 403 Forbidden");
	header('Content-Type: application/json');
	exit(json_encode(Array("code" => "accessDenied")));
}

if (!isset($_GET['jobid'])) {
	header("HTTP/1.1 404 Not Found");
	header('Content-Type: application/json');
	exit(json_encode(Array("code" => "resourceNotFound")));
}

$jobid = $_GET['jobid'];

if (!isset($_GET['reporttype'])) {
	header("HTTP/1.1 404 Not Found");
	header('Content-Type: application/json');
	exit(json_encode(Array("code" => "resourceNotFound")));
}

$reporttype = $_GET['reporttype'];

switch ($reporttype) {
case "email":
	$reportspec = "emaildetail";
	break;
case "phone":
	$reportspec = "phonedetail";
	break;
case "sms":
	$reportspec = "smsdetail";
	break;
case "device":
	$reportspec = "devicedetail";
	break;
default:
	header("HTTP/1.1 404 Not Found");
	header('Content-Type: application/json');
	exit(json_encode(Array("code" => "resourceNotFound")));
}

// Check userowns
if (!userOwns("job", $jobid)) {
	header("HTTP/1.1 404 Not Found");
	header('Content-Type: application/json');
	exit(json_encode(Array("code" => "notificationNotFound")));
}

$job = new Job($jobid);

$options = Array(
	"jobid" => $jobid,
	"reporttype" => $reportspec,
	"order1" => 'rp.pkey',
	"pagestart" => 0,
	"activefields" => null
);

$instance = new ReportInstance();
$instance->setParameters($options);
$reportgenerator = new JobDetailReport();
$reportgenerator->reportinstance = $instance;
$reportgenerator->userid = $USER->id;
$reportgenerator->generateQuery();
$report = $reportgenerator->getData();

header('Content-Type: application/json');
exit(json_encode($report));

?>
