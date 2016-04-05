<?
require_once("common.inc.php");

include("../jpgraph/jpgraph.php");
include("../jpgraph/jpgraph_pie.php");

if (!$MANAGERUSER->authorized("aspreportgraphs")) {
	exit("Not Authorized");
}

$customerid = $_GET['customerid']+0;

////////////////////////////////////////////////////////////////////////////////
// data handling
////////////////////////////////////////////////////////////////////////////////

if (is_null($aspdb = SetupASPReportsDB())) {
	die("aspreports not configured");
}

$query = "select indexsize, datasize  
	 from disk_usage 
	 where customerid = ?
	 and date=curdate() - interval 1 day";

$datay = Query($query, $aspdb, array($customerid));
$datay = array();

while ($row = DBGetRow($res)) {
	$datay[] = $row;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
//Create the graph
$graph = new PieGraph(400, 300);

$graph->title->Set("Disk Usage Yesterday");

$p1 = new PiePlot($datay[0]);
$p1->SetLegends(array("index","data"));
$graph->Add($p1);
$graph->Stroke();

?>