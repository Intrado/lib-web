<?php
include_once("common.inc.php");
include_once("../inc/securityhelper.inc.php");
include_once("../obj/Job.obj.php");

include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_pie.php");
include ("../jpgraph/jpgraph_pie3d.php");
include ("../jpgraph/jpgraph_canvas.php");


$jobid = $_GET['jobid']+0;

if (!userOwns("job",$jobid) && !$USER->authorize('viewsystemreports')) {
	header("Content-type: image/gif");
	readfile("img/icon_logout.gif");
	exit();
}
$job = new Job($jobid);

$query = "
select count(*) as cnt,
		coalesce(
			if(rc.result not in ('A', 'M') and rc.numattempts > '0' and rc.numattempts < js.value and j.status not in ('complete','cancelled'), 'retry', null),
			if(rc.result='notattempted' and j.status in ('complete','cancelled'), 'fail', null),
			if(rc.result not in ('A', 'M', 'duplicate', 'blocked') and rc.numattempts = '0' and j.status not in ('complete','cancelled'), 'inprogress', null),
			rc.result)
			as callprogress2

from job j
inner join reportperson rp on (rp.jobid=j.id)
left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
inner join jobsetting js on (js.jobid = j.id and js.name = 'maxcallattempts')
where rp.type='phone'
and rp.jobid='$jobid'
group by callprogress2
";


$cpcolors = array(
	"A" => "lightgreen",
	"M" => "#1DC10",
	"B" => "orange",
	"N" => "tan",
	"X" => "black",
	"F" => "#8AA6B6",
	"C" => "yellow",
	"inprogress" => "blue",
	"retry" => "cyan",
	"scheduled" => "darkblue"
);


$cpcodes = array(
	"A" => "Answered",
	"M" => "Machine",
	"B" => "Busy",
	"N" => "No Answer",
	"X" => "Disconnect",
	"F" => "Unknown",
	"C" => "Calling",
	"inprogress" => "Queued",
	"retry" => "Retrying",
	"scheduled" => "Scheduled"
);

//preset array positions
$data = array(
	"scheduled" => false,
	"inprogress" => false,
	"retry" => false,
	"C" => false,
	"A" => false,
	"M" => false,
	"B" => false,
	"N" => false,
	"X" => false,
	"F" => false
);
$legend = $data;
$colors = $data;

if ($result = Query($query)) {
	while ($row = DBGetRow($result)) {
		if(!isset($data[$row[1]])){
			continue;
		} else if($row[1] == "fail"){
			$row[1] = "F";
			$data[$row[1]] += $row[0];
		}else
			$data[$row[1]] = $row[0];

		$legend[$row[1]] = $cpcodes[$row[1]] . ": %d";
		$colors[$row[1]] = $cpcolors[$row[1]];
	}
}

foreach ($data as $k => $v) {
	if ($v === false) {
		unset($data[$k]);
		unset($legend[$k]);
		unset($colors[$k]);
	}
}


$data = array_values($data);
$legend = array_values($legend);
$colors = array_values($colors);


//var_dump($data);
//var_dump($legend);
//var_dump($colors);
//exit();

//check for no data and display a message
if (count($data) == 0 || array_sum($data) == 0) {
	$graph = new CanvasGraph(298,168,"auto");
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

$graph = new PieGraph(298,168,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);
$graph->SetAntiAliasing();

$graph->title->Set($job->name);
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$p1 = new PiePlot3D($data);

if (($aindex = array_search("Answered",$legend)) !== false)
	$p1->ExplodeSlice($aindex);
	
$graph->legend->Pos(0.01,0.1,"right","top");
$p1->SetLabelType(PIE_VALUE_ABS);
$p1->value->Show(false);
$size = 0.5;
$p1->SetSize($size);
$p1->SetCenter(0.35);
$p1->SetLegends(($legend));
$p1->SetSliceColors(array_reverse($colors));

$graph->Add($p1);
$graph->Stroke();

?>