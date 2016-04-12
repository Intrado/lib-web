<?
require_once("common.inc.php");

include("../jpgraph/jpgraph.php");
include("../jpgraph/jpgraph_line.php");

if (!$MANAGERUSER->authorized("aspreportgraphs")) {
	exit("Not Authorized");
}
$aspdb = SetupASPReportsDB();
if (is_null($aspdb)) {
	exit("aspreports is not configured");
}

$customerid = $_GET['customerid'] + 0;

$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date("Ymd", time() - 60 * 60 * 24 * 365); //default 365 days
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : date("Ymd");

$query = "select total, date 
	 from disk_usage 
	 where customerid = ?
	 and disk_usage.date between ? and ?";

$res = Query($query, $aspdb, array($customerid, $startdate, $enddate));
$datay = array();
$datax = array();

while ($row = DBGetRow($res)) {
	$datay[] = $row[0] / 1000000;
	$datax[] = $row[1];
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

 
// Create the graph. These two calls are always required
$graph = new Graph(600,400);
$graph->SetScale('textlin');
 
// Add a drop shadow
//$graph->SetShadow();
 
// Adjust the margin a bit to make more room for titles
$graph->SetMargin(80,30,20,80);
 
// Create a bar pot
$bplot = new LinePlot($datay);

// Adjust fill color
$bplot->SetFillColor('orange');
$graph->Add($bplot);
 
// Setup the titles
$graph->title->Set('Total disk usage (MB) Last 365 days');
$graph->xaxis->title->Set('');
$graph->yaxis->title->Set('');
$graph->xaxis->SetTickLabels($datax);
$graph->xaxis->SetTextLabelInterval(10);
$graph->xaxis->SetLabelAngle(90);
$graph->yaxis->SetLabelFormatCallback('number_format'); 


$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
 
// Display the graph
$graph->Stroke();
?>