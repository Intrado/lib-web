<?php
include_once("common.inc.php");

include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_pie.php");
include ("../jpgraph/jpgraph_pie3d.php");
include ("../jpgraph/jpgraph_canvas.php");

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
			if (wi.status not in ('fail','queued','inprogress','duplicate'), 'inprogress',wi.status))
			as callprogress
from jobworkitem wi, job j
left join	jobtask jt on
					(jt.jobworkitemid=wi.id)
left join	calllog cl on
					(cl.jobtaskid=jt.id and (cl.callattempt=jt.numattempts-1))
where wi.jobid=j.id and j.userid=$USER->id and j.status='active'
and wi.type='phone'
group by callprogress
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

//check for no data
if (count($data) == 0 || array_sum($data) == 0) {
	header("Content-type: image/png");
	readfile("graph_placeholder.png");
	exit();
}

$graph = new PieGraph(298,168,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);
$graph->SetAntiAliasing();

$graph->title->Set("Active jobs by Call Progress");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$p1 = new PiePlot3D($data);

if (($aindex = array_search("Answered",$legend)) !== false)
	$p1->ExplodeSlice($aindex);

$p1->SetLabelType(PIE_VALUE_ABS);
//$p1->value->SetFormat('%d');
$p1->value->Show(false);
$size = 0.5;
$p1->SetSize($size);
$p1->SetCenter(0.35);
$p1->SetLegends(($legend));
$p1->SetSliceColors($colors);

$graph->Add($p1);
$graph->Stroke();

?>