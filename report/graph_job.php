<?
$time = microtime(true);

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

require_once("XML/RPC.php");
require_once("XML/RPC/Server.php");

require_once("../inc/auth.inc.php");
require_once("../inc/sessionhandler.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBMappedObjectHelpers.php");
require_once("../inc/DBRelationMap.php");

include_once("../inc/securityhelper.inc.php");

include_once ("../jpgraph/jpgraph.php");
include_once ("../jpgraph/jpgraph_pie.php");
include_once ("../jpgraph/jpgraph_pie3d.php");
include_once ("../jpgraph/jpgraph_canvas.php");

require_once('../inc/reportutils.inc.php');
require_once('../inc/graph.inc.php');
require_once('../inc/graphjob.inc.php');

require_once("../obj/ReportGenerator.obj.php");
require_once("../obj/JobSummaryReport.obj.php");


// GraphJobSummary
// Params:
//		params[0] = sessionid
//		params[1] = type (phone, sms, email, device)
//		params[2] = size (small, medium, large)
//		params[3] = jobId
function graphJobSummary($msg){

	$ERROR="";
	$SESSIONID = $msg->getParam(0);
	session_id($SESSIONID->scalarval());
	doStartSession();

	$type = $msg->getParam(1)->scalarval();
	$size = $msg->getParam(2)->scalarval();
	$jobId = $msg->getParam(3)->scalarval();

	$graph = graph_job_summary($type, $size, $jobId);

	$resultcode = "success";
	$result = new XML_RPC_Value(array(
		"image" => new XML_RPC_Value(base64_encode($graph), "string"),
		"resultcode" => new XML_RPC_Value($resultcode, "string"),
		"resultdescription" => new XML_RPC_Value($ERROR, "string")
	), "struct");

	$retval = new XML_RPC_Response($result);

	return $retval;
}
//do the xmlrpc stuff

$functionMap = array(
	"graphJobSummary" => array("function" => "graphJobSummary", "signature" => array(array("string","string","string","string","string")), "docstring" => "")

);

// Note this call already outputs the correct XML_RPC_Response as returned by the function we called.
new XML_RPC_Server($functionMap);

?>
