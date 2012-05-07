<?

require_once("common.inc.php");

include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_bar.php");

if(!$MANAGERUSER->authorized("aspcallgraphs"))
	exit("Not Authorized");

$table = $SETTINGS['aspcalls']['callstable'];

$query = "
select left(startdate,10) as day,
sum(result='answered') as answered,
sum(result='machine') as machine,
sum(result='busy') as busy,
sum(result='noanswer') as noanswer,
sum(result='badnumber') as noanswer,
sum(result='fail') as noanswer,
sum(result='trunkbusy') as trunkbusy,
sum(result='unknown') as unknown

from $table

where startdate > now() - interval 24 month 

group by day
";

$conn = SetupASPDB();
$qdata = QueryAll($query, $conn);

$data = array();
$titles = array();
$x = 0;
$interval = count($qdata)/30;
$interval = 8;
foreach ($qdata as $row) {
	$titles[] = $x++%$interval == 0 ? $row[0] : "";

	$data["A"][] = $row[1];
	$data["M"][] = $row[2];
	$data["B"][] = -$row[3];
	$data["N"][] = -$row[4];
	$data["X"][] = -$row[5];
	$data["F"][] = -$row[6];
	$data["C"][] = -$row[7];
	$data["U"][] = -$row[8];
}

$cpcolors = array(
	"A" => "lightgreen",
	"M" => "#1DC10",
	"B" => "orange",
	"N" => "tan",
	"X" => "black",
	"F" => "red",
	"C" => "yellow",
	"U" => "dodgerblue"
);


// Create the graph. These two calls are always required
$graph = new Graph(2000, 400,"auto");
$graph->SetScale("textlin");
$graph->SetFrame(false);

//$graph->SetShadow();
$graph->img->SetMargin(60,90,20,70);

$b1plot = new BarPlot($data["A"]);
$b1plot->SetFillColor($cpcolors["A"]);

$b1plot->SetLegend("Answered");
$b2plot = new BarPlot($data["M"]);
$b2plot->SetFillColor($cpcolors["M"]);
$b2plot->SetLegend("Machine");

$b3plot = new BarPlot($data["X"]);
$b3plot->SetFillColor($cpcolors["X"]);
$b3plot->SetLegend("Disconnect");

$b4plot = new BarPlot($data["F"]);
$b4plot->SetFillColor($cpcolors["F"]);
$b4plot->SetLegend("Fail");

$b5plot = new BarPlot($data["B"]);
$b5plot->SetFillColor($cpcolors["B"]);
$b5plot->SetLegend("Busy");

$b6plot = new BarPlot($data["N"]);
$b6plot->SetFillColor($cpcolors["N"]);
$b6plot->SetLegend("No Answer");


$b7plot = new BarPlot($data["C"]);
$b7plot->SetFillColor($cpcolors["C"]);
$b7plot->SetLegend("Congestion");

$b8plot = new BarPlot($data["U"]);
$b8plot->SetFillColor($cpcolors["U"]);
$b8plot->SetLegend("Unknown");

// Create the grouped bar plot
$gbplot = new AccBarPlot(array($b6plot,$b5plot,$b4plot,$b3plot,$b8plot,$b7plot,$b2plot,$b1plot));
$gbplot->SetWidth(0.7);


// ...and add it to the graPH
$graph->Add($gbplot);

$graph->title->Set("By Day");
$graph->xaxis->SetTickLabels($titles);
$graph->xaxis->SetLabelAngle(90);

$graph->xaxis->SetPos("min");
$graph->yaxis->title->Set("");
$graph->yaxis->HideFirstTickLabel();
$graph->yaxis->SetTextLabelInterval(3);
$graph->legend->Pos(0.00,0.25,"right","center");


$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

// Display the graph
$graph->Stroke();



?>
