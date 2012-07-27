<?
require_once("common.inc.php");

include("../jpgraph/jpgraph.php");
include("../jpgraph/jpgraph_line.php");

if(!$MANAGERUSER->authorized("aspreportgraphs"))
	exit("Not Authorized");

$customerid = $_GET['customerid']+0;

$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date("Ymd", time() - 60*60*24*365); //default 365 days
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : date("Ymd");

////////////////////////////////////////////////////////////////////////////////
// data handling
////////////////////////////////////////////////////////////////////////////////

global $SETTINGS;
$conn = mysql_connect($SETTINGS['aspreports']['host'], $SETTINGS['aspreports']['user'], $SETTINGS['aspreports']['pass']);
mysql_select_db($SETTINGS['aspreports']['db'], $conn);

$query = "select attempted, date 
		  from billable 
		  where customerid=$customerid
		  and billable.date between $startdate and $enddate";

$res = mysql_query($query, $conn)  or die(mysql_error());
$datay = array();
$datax = array();

while($row = mysql_fetch_row($res)) {
	$datay[] = $row[0];    
    $datax[] = $row[1];
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

 
// Create the graph. These two calls are always required
$graph = new Graph(600,400);
$graph->SetScale('textint');
 
// Add a drop shadow
//$graph->SetShadow();
 
// Adjust the margin a bit to make more room for titles
$graph->SetMargin(80,40,20,80);
 
// Create a bar pot
$bplot = new LinePlot($datay);

// Adjust fill color
$bplot->SetFillColor('orange');
$graph->Add($bplot);
 
// Setup the titles
$graph->title->Set('# Attempted Last 365 days');
$graph->xaxis->title->Set('');
$graph->yaxis->title->Set('');
$graph->xaxis->SetTickLabels($datax);
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetTextLabelInterval(10);
$graph->yaxis->SetLabelFormatCallback('number_format'); 

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
 
// Display the graph
$graph->Stroke();
?>