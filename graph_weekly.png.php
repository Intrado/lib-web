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

$query = "
select 	dayofweek(date) as dayofweek, 
		sum(answered)/4 as answered,
		sum(machine)/4 as machine,
		sum(busy)/4 as busy,
		sum(noanswer)/4 as noanswer,
		sum(failed)/4 as failed,
		sum(disconnect)/4 as disconnect
		from systemstats
		where date >= date_sub(curdate(), interval 4 week)
		and attempt = '0'
		group by dayofweek
";

$daysofweek = array( 1 => "Sunday", 2 => "Monday", 3 => "Tuesday", 4 => "Wednesday", 5 => "Thursday", 6 => "Friday", 7 => "Saturday");

$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array(), "F" => array(), "X" => array());

$x_titles = array();
while ($row = DBGetRow($result)) {
	$data["A"][$row[0]-1] = $row[1];
	$data["M"][$row[0]-1] = $row[2];
	$data["B"][$row[0]-1] = $row[3];
	$data["N"][$row[0]-1] = $row[4];
	$data["F"][$row[0]-1] = $row[5];
	$data["X"][$row[0]-1] = $row[6];
}

//var_dump($data);
//exit();

$max = 0;
for ($x =0; $x < 7; $x++) {

	foreach (array("A","M","B","N","F","X") as $type) {
		if (!isset($data[$type][$x]))
			$data[$type][$x] = 0;
		if ($type == "B" || $type == "N" || $type == "F" || $type == "X")
			$data[$type][$x] = -$data[$type][$x];
	}

	$max = max($max, $data["A"][$x] + $data["M"][$x]);

	$x_titles[$x] = $daysofweek[$x + 1];
}

//var_dump($data);
//var_dump($x_titles);
//exit();


// Create the graph. These two calls are always required
$graph = new Graph($big ? 750 : 350, $big ? 450 : 300,"auto");
$graph->SetScale("textlin");
$graph->SetFrame(false);

//$graph->SetShadow();
$graph->img->SetMargin(60,105,20,70);

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

// ...and add it to the graph
$graph->Add($gbplot);

$graph->title->Set("By Day of Week" );
$graph->xaxis->SetTickLabels($x_titles);
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetPos("min");
$graph->yaxis->title->Set("");
$graph->yaxis->HideFirstTickLabel();
$graph->yaxis->SetTextLabelInterval(2);


$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

// Display the graph
$graph->Stroke();
?>