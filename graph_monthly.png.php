<?
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
select 	month(date) as month,
		sum(answered) as answered,
		sum(machine) as machine,
		sum(busy) as busy,
		sum(noanswer) as noanswer,
		sum(failed) as failed,
		sum(disconnect) as disconnect
		from systemstats
		where date > date_sub(curdate(), interval 12 month)
		and attempt = '0'
		group by month
";


$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array(), "F" => array(), "X" => array());

$x_titles = array();

for($x=0; $x <= 11; $x++){
	$data["A"][$x] = 0;
	$data["M"][$x] = 0;
	$data["B"][$x] = 0;
	$data["N"][$x] = 0;
	$data["F"][$x] = 0;
	$data["X"][$x] = 0;
}


$thismonth = date("n", strtotime("today"));
while ($row = DBGetRow($result)) {
	
	$offsetmonth = $row[0] - $thismonth-1;
	if($offsetmonth < 0)
		$offsetmonth = $offsetmonth + 12;

	$data["A"][$offsetmonth] = $row[1];
	$data["M"][$offsetmonth] = $row[2];
	$data["B"][$offsetmonth] = $row[3];
	$data["N"][$offsetmonth] = $row[4];
	$data["F"][$offsetmonth] = $row[5];
	$data["X"][$offsetmonth] = $row[6];
}

//var_dump($data);
//exit();

$months=array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");


$max = 0;
for ($x = 0; $x < 12; $x++) {

	foreach (array("A","M","B","N","F","X") as $type) {
		if (!isset($data[$type][$x]))
			$data[$type][$x] = 0;
		//show non a/m as negative
		if ($type == "B" || $type == "N" || $type == "F" || $type == "X")
			$data[$type][$x] = -$data[$type][$x];
	}

	$max = max($max, $data["A"][$x] + $data["M"][$x]);
	$offset = 11-$x;
	$x_titles[$x] = date("F", strtotime("-$offset month"));
}


// Create the graph. These two calls are always required
$graph = new Graph($big ? 750 : 570, $big ? 450 : 300,"auto");
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