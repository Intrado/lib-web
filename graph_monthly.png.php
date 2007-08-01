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
select 	month(date) as month,
				sum(answered) as answered,
				sum(machine) as machine,
				sum(busy) as busy,
				sum(noanswer) as noanswer
			from systemstats
			where unix_timestamp(date) > unix_timestamp(date_sub(now(), interval 12 month))
			group by month
			order by date
";


$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array());

$x_titles = array();
$thismonth = date("n", strtotime("today"));
while ($row = DBGetRow($result)) {
	$offsetmonth = $row[0] - $thismonth-1;
	if($offsetmonth < 0)
		$offsetmonth = $offsetmonth + 12;

	$data["A"][$offsetmonth] = $row[1];
	$data["M"][$offsetmonth] = $row[2];
	$data["B"][$offsetmonth] = $row[3];
	$data["N"][$offsetmonth] = $row[4];
}

//var_dump($data);
//exit();

$months=array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");


$max = 0;
for ($x = 0; $x < 12; $x++) {

	foreach (array("A","M","B","N") as $type) {
		if (!isset($data[$type][$x]))
			$data[$type][$x] = 0;
		//show non a/m as negative
		if ($type == "B" || $type == "N")
			$data[$type][$x] = -$data[$type][$x];
	}

	$max = max($max, $data["A"][$x] + $data["M"][$x]);
	$offset = 11-$x;
	$x_titles[$x] = date("F", strtotime("-$offset month"));
}


// Create the graph. These two calls are always required
$graph = new Graph($big ? 750 : 550, $big ? 450 : 300,"auto");
$graph->SetScale("textlin");
$graph->SetFrame(false);

//$graph->SetShadow();
$graph->img->SetMargin(40,80,20,70);

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

$graph->title->Set("Monthly");
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