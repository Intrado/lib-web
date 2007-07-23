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
	"F" => "red",
	"C" => "yellow",
	"duplicate" => "lightgray",
	"fail" => "red",
	"queued" => "blue",
	"inprogress" => "blue"
);


$query = "
select 	dayofmonth(date) as dayofmonth,
				sum(answered)/12 as answered,
				sum(machine)/12 as machine,
				sum(busy)/12 as busy,
				sum(noanswer)/12 as noanswer
			from systemstats
			where date > date_sub(now(), interval 28 day)
			group by dayofmonth
";


$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array());

$x_titles = array();
while ($row = DBGetRow($result)) {
	$data["A"][$row[0]-1] = $row[1];
	$data["M"][$row[0]-1] = $row[2];
	$data["B"][$row[0]-1] = $row[3];
	$data["N"][$row[0]-1] = $row[4];
}

//var_dump($data);
//exit();

$max = 0;
for ($x = 0; $x < 28; $x++) {

	foreach (array("A","M","B","N") as $type) {
		if (!isset($data[$type][$x]))
			$data[$type][$x] = 0;
		//show non a/m as negative
		if ($type == "B" || $type == "N")
			$data[$type][$x] = -$data[$type][$x];
	}

	$max = max($max, $data["A"][$x] + $data["M"][$x]);

	$x_titles[$x] = $x+1;
}


// Create the graph. These two calls are always required
$graph = new Graph($big ? 750 : 550, $big ? 450 : 400,"auto");
$graph->SetScale("textlin");
$graph->SetFrame(false);

//$graph->SetShadow();
$graph->img->SetMargin(40,75,20,70);

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
$gbplot->SetWidth(0.7);


// ...and add it to the graPH
$graph->Add($gbplot);

$graph->title->Set("By Day of Month (last 28 days)");
$graph->xaxis->SetTextTickInterval(6,0);
$graph->xaxis->SetTickLabels($x_titles);
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetPos("min");
$graph->yaxis->title->Set("");
$graph->yaxis->HideFirstTickLabel();
$graph->yaxis->SetTextLabelInterval($big ? 3 : 2);

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

// Display the graph
$graph->Stroke();
?>