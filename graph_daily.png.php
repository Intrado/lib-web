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
select 	date as date,
		sum(answered) as answered,
		sum(machine) as machine,
		sum(busy) as busy,
		sum(noanswer) as noanswer,
		sum(failed) as failed,
		sum(disconnect) as disconnect
		from systemstats
		where date > date_sub(curdate(), interval 4 week)
		and attempt = '0'
		group by date
";


$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array(), "X" => array(), "F" => array());

$x_titles = array();

for($x=0; $x < 28; $x++){
	$data["A"][$x] = 0;
	$data["M"][$x] = 0;
	$data["B"][$x] = 0;
	$data["N"][$x] = 0;
	$data["F"][$x] = 0;
	$data["X"][$x] = 0;
}



while ($row = DBGetRow($result)) {
	$daysoffset = (strtotime("midnight") - strtotime($row[0])) / (60*60*24);
	$newday = 28-$daysoffset-1;
	
	$data["A"][$newday] = $row[1];
	$data["M"][$newday] = $row[2];
	$data["B"][$newday] = $row[3];
	$data["N"][$newday] = $row[4];
	$data["F"][$newday] = $row[5];
	$data["X"][$newday] = $row[6];
}

//var_dump($data);
//exit();

$max = 0;

for ($x=0; $x < 28; $x++) {

	foreach (array("A","M","B","N","X","F") as $type) {
		if (!isset($data[$type][$x]))
			$data[$type][$x] = 0;
		//show non a/m as negative
		if ($type == "B" || $type == "N" || $type == "X" || $type == "F")
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
$gbplot = new AccBarPlot(array($b6plot,$b5plot,$b4plot,$b3plot,$b2plot,$b1plot));
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