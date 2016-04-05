<?
require_once("common.inc.php");

include("../jpgraph/jpgraph.php");
include("../jpgraph/jpgraph_bar.php");

if (!$MANAGERUSER->authorized("aspreportgraphs")) {
	exit("Not Authorized");
}


$customerid = $_GET['customerid'] + 0;

$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date("Ymd", time() - 60 * 60 * 24 * 7); //default 7 days
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : date("Ymd");

////////////////////////////////////////////////////////////////////////////////
// data handling
////////////////////////////////////////////////////////////////////////////////

if (is_null($aspdb = SetupASPReportsDB())) {
	die("aspreports not configured");
}

$query = "SELECT attempted, date 
      FROM billable 
      WHERE customerid = ?
      AND billable.date BETWEEN ? AND ?";

$res = Query($query, $aspdb, array($customerid, $startdate, $enddate));
$datay = array();
$datax = array();

while ($row = DBGetRow($res)) {
	$datay[] = $row[0];
	$datax[] = $row[1];
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

 
// Create the graph. These two calls are always required
$graph = new Graph(350,300);
$graph->SetScale('textint');
 
// Add a drop shadow
//$graph->SetShadow();
 
// Adjust the margin a bit to make more room for titles
$graph->SetMargin(80,40,20,80);
 
// Create a bar pot
$bplot = new BarPlot($datay);

// Adjust fill color
$bplot->SetFillColor('orange');
$graph->Add($bplot);
 
// Setup the titles
$graph->title->Set('# Attempted Last 7 days');
$graph->xaxis->title->Set('');
$graph->yaxis->title->Set('');
$graph->xaxis->SetTickLabels($datax);
$graph->xaxis->SetLabelAngle(90);
$graph->yaxis->SetLabelFormatCallback('number_format'); 
 
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
 
// Display the graph
$graph->Stroke();
?>