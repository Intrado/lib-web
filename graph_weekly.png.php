<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_bar.php");

$big = isset($_GET['big']) ? true : false;

$cpcolors = array(
	"A" => "lightgreen",
	"M" => "#1DC10",
	"B" => "orange",
	"N" => "tan",
	"X" => "black",
	"F" => "red",
	"C" => "yellow",
	"duplicate" => "lightgray",
	"fail" => "red",
	"queued" => "blue",
	"inprogress" => "blue"
);


$query = "





select count(*)/30 as cnt,
	dayofweek(from_unixtime(cl.starttime/1000)) as dayofweek, cl.callprogress
from calllog cl
where cl.callprogress in ('A','M','B','N')
and cl.starttime >= unix_timestamp(date_sub(now(), interval 30 day)) * 1000
group by dayofweek, cl.callprogress
";

$daysofweek = array( 1 => "Sunday", 2 => "Monday", 3 => "Tuesday", 4 => "Wednesday", 5 => "Thursday", 6 => "Friday", 7 => "Saturday");

$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array());

$x_titles = array();
while ($row = DBGetRow($result)) {
	$data[$row[2]][$row[1]-1] = $row[0];
}

//var_dump($data);
//exit();

$max = 0;
for ($x =0; $x < 7; $x++) {

	foreach (array("A","M","B","N") as $type) {
		if (!isset($data[$type][$x]))
			$data[$type][$x] = 0;
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
$graph->img->SetMargin(40,90,20,70);

// Create the bar plots
$b1plot = new BarPlot($data["A"]);
$b1plot->SetFillColor($cpcolors["A"]);
$b1plot->SetLegend("Answered");

$b2plot = new BarPlot($data["M"]);
$b2plot->SetFillColor($cpcolors["M"]);
$b2plot->SetLegend("Machine");

$b3plot = new BarPlot($data["B"]);
$b3plot->SetFillColor($cpcolors["B"]);
$b3plot->SetLegend("Busy");

$b4plot = new BarPlot($data["N"]);
$b4plot->SetFillColor($cpcolors["N"]);
$b4plot->SetLegend("No Answer");


// Create the grouped bar plot
$gbplot = new AccBarPlot(array($b4plot,$b3plot,$b2plot,$b1plot));

// ...and add it to the graph
$graph->Add($gbplot);

$graph->title->Set("By Day of Week (last 30 days)" );
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