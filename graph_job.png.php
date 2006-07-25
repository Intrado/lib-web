<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_pie.php");
include ("jpgraph/jpgraph_pie3d.php");
include ("jpgraph/jpgraph_canvas.php");


$jobid = DBSafe($_GET['jobid']);
if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports') && customerOwns("job",$jobid))) {
	header("Content-type: image/gif");
	readfile("img/icon_logout.gif");
	exit();
}


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

$cpcodes = array(
	"A" => "Answered",
	"M" => "Machine",
	"B" => "Busy",
	"N" => "No Answer",
	"X" => "Disconnect",
	"F" => "Failed",
	"C" => "Calling",
	"duplicate" => "Duplicate",
	"fail" => "Failed",
	"queued" => "In Progress",
	"inprogress" => "In Progress"
);


$query = "
select count(*) as cnt,
		coalesce(callprogress,
			if (wi.status not in ('fail','duplicate'), 'inprogress',wi.status))
			as callprogress
from jobworkitem wi
left join	jobtask jt on
					(jt.jobworkitemid=wi.id)
left join	calllog cl on
					(cl.jobtaskid=jt.id and (cl.callattempt=jt.numattempts-1))
where wi.jobid='$jobid' and wi.type='phone'
group by wi.jobid, callprogress
order by cnt asc
";


$data = array();
$legend = array();
$colors = array();

if ($result = Query($query)) {
	while ($row = DBGetRow($result)) {
		$data[] = $row[0];

		$legend[] = $cpcodes[$row[1]] . ": %d";
		$colors[] = $cpcolors[$row[1]];
	}
}

//var_dump($data);
//var_dump($legend);
//var_dump($colors);
//exit();

//check for no data and display a message
if (count($data) == 0 || array_sum($data) == 0) {
	$graph = new CanvasGraph(400,300,"auto");
	$t1 = new Text("Sorry, there is no data to display");
	$t1->SetPos(0.05,0.5);
	$t1->SetOrientation("h");
	$t1->SetFont(FF_FONT1,FS_NORMAL);
	$t1->SetBox("white","black",'gray');
	$t1->SetColor("black");
	$graph->AddText($t1);
	$graph->Stroke();
	exit();
}

$graph = new PieGraph(400,300,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);
$graph->SetAntiAliasing();

$graph->title->Set("Call results - " . date("g:i:s a"));
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$p1 = new PiePlot3D(($data));

$p1->SetLabelType(PIE_VALUE_ABS);
$p1->value->SetFormat('');
$p1->value->Show();
$size = 0.375;
$p1->SetSize($size);
$p1->SetCenter(0.3);
$p1->SetLegends(($legend));
$p1->SetSliceColors(array_reverse($colors));

$graph->Add($p1);
$graph->Stroke();

?>