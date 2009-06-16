<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_bar.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

$big = isset($_GET['big']) ? true : false;

$cpcolors = array(
	"A" => "lightgreen",
	"M" => "#1DC10",
	"B" => "orange",
	"N" => "tan",
	"X" => "black",
	"F" => "#8AA6B6",
	"C" => "yellow",
	"duplicate" => "lightgray",
	"fail" => "#8AA6B6",
	"queued" => "blue",
	"inprogress" => "blue"
);


$query = "select 	hour,
		sum(answered)/28 as answered,
		sum(machine)/28 as machine,
		sum(busy)/28 as busy,
		sum(noanswer)/28 as noanswer,
		sum(failed)/28 as failed,
		sum(disconnect)/28 as disconnect
		from systemstats
		where date > date_sub(curdate(),interval 4 week)
		and attempt = '0'
		group by hour
";


$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array(), "F" => array(), "X" => array());

$x_titles = array();
while ($row = DBGetRow($result)) {
	$data["A"][$row[0]] = $row[1];
	$data["M"][$row[0]] = $row[2];
	$data["B"][$row[0]] = $row[3];
	$data["N"][$row[0]] = $row[4];
	$data["F"][$row[0]] = $row[5];
	$data["X"][$row[0]] = $row[6];
}

//var_dump($data);
//exit();

$max = 0;
for ($x = 0; $x < 24; $x++) {

	foreach (array("A","M","B","N", "F", "X") as $type) {
		if (!isset($data[$type][$x]))
			$data[$type][$x] = 0;
		//show non a/m as negative
		if ($type == "B" || $type == "N" || $type == "X" || $type == "F")
			$data[$type][$x] = -$data[$type][$x];
	}

	$max = max($max, $data["A"][$x] + $data["M"][$x]);

	$x_titles[$x] = date("g:00 a", strtotime("$x:00:00"));
}


// Create the graph. These two calls are always required
$graph = new Graph($big ? 750 : 370, $big ? 450 : 300,"auto");
$graph->SetScale("textlin");
$graph->SetFrame(false);

//$graph->SetShadow();
$graph->img->SetMargin(60,90,20,70);

// Create the bar plots
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
$b4plot->SetLegend("Unknown");

$b5plot = new BarPlot($data["B"]);
$b5plot->SetFillColor($cpcolors["B"]);
$b5plot->SetLegend("Busy");

$b6plot = new BarPlot($data["N"]);
$b6plot->SetFillColor($cpcolors["N"]);
$b6plot->SetLegend("No Answer");

// Create the grouped bar plot
$gbplot = new AccBarPlot(array($b6plot, $b5plot, $b4plot,$b3plot,$b2plot,$b1plot));
$gbplot->SetWidth(0.7);


// ...and add it to the graPH
$graph->Add($gbplot);

$graph->title->Set("By Time of Day");
$graph->xaxis->SetTickLabels($x_titles);
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetPos("min");
$graph->yaxis->title->Set("");
$graph->yaxis->HideFirstTickLabel();
$graph->yaxis->SetTextLabelInterval($big ? 3 : 2);
$graph->legend->Pos(0.00,0.25,"right","center");


$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

// Display the graph
$graph->Stroke();
?>