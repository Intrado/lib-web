<?
require_once("common.inc.php");

include("../jpgraph/jpgraph.php");
include("../jpgraph/jpgraph_pie.php");

if(!$MANAGERUSER->authorized("aspreportgraphs"))
	exit("Not Authorized");

$customerid = $_GET['customerid']+0;

////////////////////////////////////////////////////////////////////////////////
// data handling
////////////////////////////////////////////////////////////////////////////////

global $SETTINGS;
$conn = mysql_connect($SETTINGS['aspreports']['host'], $SETTINGS['aspreports']['user'], $SETTINGS['aspreports']['pass']);
mysql_select_db($SETTINGS['aspreports']['db'], $conn);

$query = "select indexsize, datasize  
	 from disk_usage 
	 where customerid=$customerid
	 and date=curdate() - interval 1 day";
		  
$res = mysql_query($query, $conn)  or die(mysql_error());
$datay = array();

while($row = mysql_fetch_row($res)) {
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