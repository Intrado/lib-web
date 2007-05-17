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

if ($IS_COMMSUITE) {
$query = "
select count(*)/30 as cnt,
	hour( from_unixtime(starttime/1000)) as hour,
	result
from reportcontact
where result in ('A','M','B','N')
and starttime > 1000 * unix_timestamp(date_sub(now(),interval 30 day))
group by hour, result
";
} /*CSDELETEMARKER_START*/ else {
$query = "
select count(*)/30 as cnt,
	hour( from_unixtime(rc.starttime/1000)) as hour,
	rc.result
from reportcontact rc
where rc.result in ('A','M','B','N')
and rc.starttime > 1000 * unix_timestamp(date_sub(now(),interval 30 day))
group by hour, rc.result
";
} /*CSDELETEMARKER_END*/

$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array());

$x_titles = array();
while ($row = DBGetRow($result)) {
	$data[$row[2]][$row[1]] = $row[0];
}

//var_dump($data);
//exit();

$max = 0;
for ($x = 0; $x < 24; $x++) {

	foreach (array("A","M","B","N") as $type) {
		if (!isset($data[$type][$x]))
			$data[$type][$x] = 0;
		//show non a/m as negative
		if ($type == "B" || $type == "N")
			$data[$type][$x] = -$data[$type][$x];
	}

	$max = max($max, $data["A"][$x] + $data["M"][$x]);

	$x_titles[$x] = date("g:00 a", strtotime("$x:00:00"));
}


// Create the graph. These two calls are always required
$graph = new Graph($big ? 750 : 350, $big ? 450 : 300,"auto");
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

$graph->title->Set("By Time of Day (last 30 days)");
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