<?
require_once("common.inc.php");

include("../jpgraph/jpgraph.php");
include("../jpgraph/jpgraph_bar.php");

if (!$MANAGERUSER->authorized("aspreportgraphs")) {
	exit("Not Authorized");
}
$aspdb = SetupASPReportsDB();
if (is_null($aspdb)) {
	exit("aspreports is not configured");
}

$customerid = $_GET['customerid'] + 0;

$startdate = date("Y-m-d", time() - 60 * 60 * 24 * 365);

$query = "select max(system_contacts), max(manual_adds), max(addrbook), max(uploadlist), max(deleted), 
		  year(date) as year_of_date, month(date) as month_of_date 
		  from contacts
		  where customerid = ? and date >= ?
		  group by year_of_date, month_of_date";

$res = Query($query, $aspdb, array($customerid, $startdate));
$data = array();


$cpcolors = array(
	"S" => "lightgreen",
	"M" => "lightblue",
	"A" => "orange",
	"U" => "tan",
	"D" => "black"
);

while ($row = DBGetRow($res)) {
	$data["S"][] = $row[0];    
	$data["M"][] = $row[1];  
	$data["A"][] = $row[2];  
	$data["U"][] = $row[3];  
	$data["D"][] = $row[4];  
	$data["YD"][] = $row[6] . "/". $row[5];  
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$graph = new Graph(600, 400,"auto");
$graph->SetScale("textlin");
$graph->SetFrame(false);

//$graph->SetShadow();
$graph->img->SetMargin(60,130,20,70);

$b1plot = new BarPlot($data["S"]);
$b1plot->SetFillColor($cpcolors["S"]);
$b1plot->SetLegend("System Contacts");

$b2plot = new BarPlot($data["M"]);
$b2plot->SetFillColor($cpcolors["M"]);
$b2plot->SetLegend("Manual Adds");

$b3plot = new BarPlot($data["U"]);
$b3plot->SetFillColor($cpcolors["U"]);
$b3plot->SetLegend("Upload List");

$b4plot = new BarPlot($data["A"]);
$b4plot->SetFillColor($cpcolors["A"]);
$b4plot->SetLegend("Address Book");

$b5plot = new BarPlot($data["D"]);
$b5plot->SetFillColor($cpcolors["D"]);
$b5plot->SetLegend("Deleted");


// Create the grouped bar plot
$gbplot = new AccBarPlot(array($b1plot, $b5plot,$b3plot,$b2plot,$b4plot));
$gbplot->SetWidth(0.7);


// ...and add it to the graPH
$graph->Add($gbplot);

$graph->title->Set("Contacts By Month");
$graph->xaxis->SetTickLabels($data["YD"]);
$graph->xaxis->SetLabelAngle(90);

$graph->xaxis->SetPos("min");
$graph->yaxis->title->Set("");
$graph->yaxis->HideFirstTickLabel();
$graph->yaxis->SetTextLabelInterval(3);
$graph->legend->Pos(0.00,0.25,"right","center");
$graph->yaxis->SetLabelFormatCallback('number_format'); 


$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

// Display the graph
$graph->Stroke();

?>