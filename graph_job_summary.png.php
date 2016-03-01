<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");

include_once ("jpgraph/jpgraph.php");
include_once ("jpgraph/jpgraph_pie.php");
include_once ("jpgraph/jpgraph_pie3d.php");
include_once ("jpgraph/jpgraph_canvas.php");

require_once('inc/graph.inc.php');
require_once('inc/graphjob.inc.php');

require_once("obj/ReportGenerator.obj.php");
require_once("obj/JobSummaryReport.obj.php");

// MARCO use ternary?
$type = "phone";
if (isset($_GET['type'])) {
	$type = $_GET['type'];
}

$size = "medium";
if (isset($_GET['size'])) {
	$size = $_GET['size'];
}

$jobIds = $_GET['jobId'];
$jobIdList = explode(",",$jobIds);
foreach ($jobIdList as $jobId) {
	if (!userOwns("job", $jobId) && !$USER->authorize('viewsystemreports')) {
		redirect("unauthorized.php");
	}
}

$graph = graph_job_summary($type, $size, $jobIds);

echo $graph;

?>