<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_bar.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

$options = $_SESSION['report']['options'];
$reldatequery = "";

if(isset($options['jobid'])){
	$jobid = $options['jobid'];
	$jobidquery = "and jobid = '$jobid'";
} else if(isset($options['reldate'])){
	$reldate = $options['reldate'];
	switch($reldate){
		case 'today':
			$targetdate = QuickQuery("select curdate()");
			$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$targetdate',interval 1 day) )
								or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
			
			break;
		
		case 'week':
			//1 = Sunday, 2 = Monday, ..., 7 = Saturday
			$dow = QuickQuery("select dayofweek(curdate())");

			//normally go back 1 day
			$daydiff = 1;
			//if it is sunday, go back 2 days
			if ($dow == 1)
				$daydiff = 2;
			//if it is monday, go back 3 days
			if ($dow == 2)
				$daydiff = 3;

			$targetdate = QuickQuery("select date_sub(curdate(),interval $daydiff day)");
			$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$targetdate',interval 1 day) )
								or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
			
			break;
		case 'yesterday':
			$targetdate = QuickQuery("select date_sub(curdate(),interval 1 day)");
			$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$targetdate',interval 1 day) )
								or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
			
			break;
		case 'weektodate':
			$today = QuickQuery("select curdate()");
			$targetdate = QuickQuery("select date_sub(curdate(), interval 1 week)");
			$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$today',interval 1 day) )
								or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$today',interval 1 day) ";
			break;
		case 'monthtodate':
			$today = QuickQuery("select curdate()");
			$targetdate = QuickQuery("select date_sub(curdate(), interval 1 month)");
			$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$today',interval 1 day) )
								or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$today',interval 1 day) ";
			break;
		case 'xdays':
			$lastxdays = $params['lastxdays'];
			if($lastxdays == "")
				$lastxdays = 1;
			$today = QuickQuery("select curdate()");
			$targetdate = QuickQuery("select date_sub(curdate(),interval $lastxdays day)");
			$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$today',interval 1 day) )
								or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$today',interval 1 day) ";
			
			break;
		case 'daterange':
			
			$datestart = strtotime($params['startdate']);
			$dateend = strtotime($params['enddate']);
			$reldatequery = "and ( (j.startdate >= from_unixtime('$datestart') and j.startdate < date_add(from_unixtime('$dateend'),interval 1 day) )
								or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= from_unixtime('$datestart') and j.startdate <= date_add(from_unixtime('$dateend'),interval 1 day) ";
			break;
	}
	$joblist = QuickQueryList("select j.id from job j where 1 $reldatequery");
	$jobidquery = " and jobid in ('" . implode("','", $joblist) . "')";
} else {
	exit(0);
}

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
select hour,
	answered as A,
	machine as M,
	busy as B,
	noanswer as N
from systemstats
where 1
$jobidquery
";


$result = Query($query);

$data = array("A" => array(), "M" => array(), "B" => array(), "N" => array());

$x_titles = array();
while ($row = DBGetRow($result)) {
	$data["A"][$row[0]] = $row[1];
	$data["M"][$row[0]] = $row[2];
	$data["B"][$row[0]] = $row[3];
	$data["N"][$row[0]] = $row[4];
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

$graph->title->Set("By Time of Day ");
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