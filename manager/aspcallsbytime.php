<?

require_once("common.inc.php");

include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_line.php");
include ("../jpgraph/jpgraph_log.php"); 

if(!$MANAGERUSER->authorized("aspcallgraphs"))
	exit("Not authorized");

$starthour = isset($_GET['starthour']) ?  $_GET['starthour'] + 0 : 0;
$endhour = isset($_GET['endhour']) ? $_GET['endhour'] + 0 : 24;
$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date("Y-m-d", time() - 60*60*24*30); //default 30 days
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : date("Y-m-d");

$dm = isset($_GET['dm']) ? $_GET['dm'] : ""; 

$dmid = false;
if ($dm) {
	$res = mysql_query("select id from dms where dm='". mysql_real_escape_string($dm) . "'");
	while ($row = mysql_fetch_row($res)) {
		$dmid = $row[0];
	}
}


$days = (strtotime($enddate) - strtotime($startdate))/(60*60*24);
$dmfiltersql = $dmid ? "and dmid='".mysql_real_escape_string($dmid)."'" : "";

$table = $SETTINGS['aspcalls']['callstable']; 

$query = "
select round(minute(startdate) + hour(startdate) *60) as minofday,
sum(result='answered') as answered,
sum(result='machine') as machine,
sum(result='busy') as busy,
sum(result='noanswer') as noanswer,
sum(result='badnumber') as noanswer,
sum(result='fail') as noanswer,
sum(result='trunkbusy') as trunkbusy,
sum(result='unknown') as unknown

from $table 
where startdate between '$startdate' and '$enddate'
and hour(startdate) between $starthour and $endhour
$dmfiltersql
group by minofday
";

$conn = SetupASPDB();
$qdata = QueryAll($query, $conn);

$data = array();


$titles = array();
foreach ($qdata as $row) {
	$data["A"][$row[0]] = $row[1] / $days;
	$data["M"][$row[0]] = $row[2] / $days;
	$data["B"][$row[0]] = -$row[3] / $days;
	$data["N"][$row[0]] = -$row[4] / $days;
	$data["X"][$row[0]] = -$row[5] / $days;
	$data["F"][$row[0]] = -$row[6] / $days;
	$data["C"][$row[0]] = -$row[7] / $days;
	$data["U"][$row[0]] = -$row[8] / $days;
}


for ($x = $starthour*60; $x <= $endhour * 60; $x++) {
	$titles[] = $x;
	@$data["A"][$x] += 0;
	@$data["M"][$x] += 0;
	@$data["B"][$x] += 0;
	@$data["N"][$x] += 0;
	@$data["X"][$x] += 0;
	@$data["F"][$x] += 0;
	@$data["C"][$x] += 0;
	@$data["U"][$x] += 0;
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

function TimeCallback($val) {
	$mins = $val % 60;
	$hours = floor($val/60);
	return sprintf("%02d:%02d",$hours,$mins);
}


// Create the graph. These two calls are always required
$graph = new Graph(1200, 650,"auto");
if ($dm)
	$graph->SetScale("textlin");
else
	$graph->SetScale("textlin",-4000,8000);
$graph->SetFrame(false);

//$graph->SetShadow();
$graph->img->SetMargin(60,90,20,70);

$b1plot = new LinePlot($data["A"]);
$b1plot->SetFillColor($cpcolors["A"]);
$b1plot->SetLegend("Answered");
//$b1plot->SetLineWeight(0);

$b2plot = new LinePlot($data["M"]);
$b2plot->SetFillColor($cpcolors["M"]);
$b2plot->SetLegend("Machine");
//$b2plot->SetLineWeight(0);

$b3plot = new LinePlot($data["X"]);
$b3plot->SetFillColor($cpcolors["X"]);
$b3plot->SetLegend("Disconnect");
//$b3plot->SetLineWeight(0);

$b4plot = new LinePlot($data["F"]);
$b4plot->SetFillColor($cpcolors["F"]);
$b4plot->SetLegend("Fail");
//$b4plot->SetLineWeight(0);

$b5plot = new LinePlot($data["B"]);
$b5plot->SetFillColor($cpcolors["B"]);
$b5plot->SetLegend("Busy");
//$b5plot->SetLineWeight(0);

$b6plot = new LinePlot($data["N"]);
$b6plot->SetFillColor($cpcolors["N"]);
$b6plot->SetLegend("No Answer");
//$b6plot->SetLineWeight(0);

$b7plot = new LinePlot($data["C"]);
$b7plot->SetFillColor($cpcolors["C"]);
$b7plot->SetLegend("Congestion");
//$b7plot->SetLineWeight(0);

$b8plot = new LinePlot($data["U"]);
$b8plot->SetFillColor($cpcolors["U"]);
$b8plot->SetLegend("Unknown");
//$b8plot->SetLineWeight(0);

// Create the accumulated graph
$accplot = new AccLinePlot(array($b5plot, $b6plot, $b4plot, $b3plot, $b7plot, $b8plot,$b2plot,$b1plot));

// Add the plot to the graph
$graph->Add($accplot);


$graph->title->Set("By Time $startdate to $enddate, $starthour:00-$endhour:00 $dm");

//$graph->xaxis->SetTickLabels($titles);
$graph->xaxis->SetLabelFormatCallback('TimeCallback');

$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetTextLabelInterval(30);
$graph->xaxis->SetPos("min");
$graph->yaxis->title->Set("");
$graph->yaxis->HideFirstTickLabel();
//$graph->yaxis->SetTextLabelInterval(3);
$graph->legend->Pos(0.00,0.25,"right","center");
$graph->img->SetAntiAliasing();

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

// Display the graph
$graph->Stroke();


?>
