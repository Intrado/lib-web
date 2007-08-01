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
		date as date,
				sum(answered) as answered,
				sum(machine) as machine,
				sum(busy) as busy,
				sum(noanswer) as noanswer
			from systemstats
			where date > date_sub(now(), interval 4 week)
			group by date
			order by date
";


$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array());

$x_titles = array();
$unix_today = strtotime("today");
$today = date("d", $unix_today);

$month = date("n", strtotime("-1 month"));
$numdays = cal_days_in_month(CAL_GREGORIAN, $month, date("Y", $unix_today));
$offset = $today - 28;

if($offset < 0)
	$offset = -$offset;
while ($row = DBGetRow($result)) {
	$newday = $row[0] + $offset-1;
	if($newday > $today)
		$newday = $newday % $numdays;
	$data["A"][$newday] = $row[2];
	$data["M"][$newday] = $row[3];
	$data["B"][$newday] = $row[4];
	$data["N"][$newday] = $row[5];
}

//var_dump($data);
//exit();

$max = 0;

for ($x=0; $x < 28; $x++) {

	foreach (array("A","M","B","N") as $type) {
		if (!isset($data[$type][$x]))
			$data[$type][$x] = 0;
		//show non a/m as negative
		if ($type == "B" || $type == "N")
			$data[$type][$x] = -$data[$type][$x];
	}

	$max = max($max, $data["A"][$x] + $data["M"][$x]);
	$offset = 27-$x;
	$x_titles[$x] = date("m/d/y", strtotime("-$offset day"));
	
}
//var_dump($data);
//var_dump($x_titles);

// Create the graph. These two calls are always required
$graph = new Graph($big ? 750 : 650, $big ? 450 : 300,"auto");
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
$gbplot->SetWidth(0.7);


// ...and add it to the graPH
$graph->Add($gbplot);

$graph->title->Set("Daily");
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