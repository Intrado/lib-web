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
	"fail" => "#aaaaaa",
	"inprogress" => "blue",
	"retry" => "cyan",
	"scheduled" => "darkblue",
	"blocked" => "#CC00CC"
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
	"fail" => "No Phone #",
	"inprogress" => "In Progress",
	"retry" => "Retry",
	"scheduled" => "Scheduled",
	"blocked" => "Blocked"
);


$query = "
select count(*) as cnt,
		coalesce(if(rp.status = 'nocontacts','fail', null),
			if(rc.result not in ('A', 'M', 'blocked', 'duplicate') and rc.numattempts > 0 and rc.numattempts < js.value, 'retry', if(rc.result='notattempted', null, rc.result)),
			if (rp.status not in ('fail','duplicate','scheduled', 'blocked'), 'inprogress', rp.status))
			as callprogress2
from job j
inner join reportperson rp on (rp.jobid=j.id)
left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
inner join jobsetting js on (js.jobid = j.id and js.name = 'maxcallattempts')
where j.userid=$USER->id and j.status='active'
and rp.type='phone'
group by callprogress2
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

//$graph->title->Set("Active jobs by Call Progress");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$p1 = new PiePlot3D($data);

if (($aindex = array_search("Answered",$legend)) !== false)
	$p1->ExplodeSlice($aindex);
	
$graph->legend->Pos(0.01,0.01,"right","top");
$p1->SetLabelType(PIE_VALUE_ABS);
//$p1->value->SetFormat('%d');
$p1->value->Show(false);
$size = 0.5;
$p1->SetSize($size);
$p1->SetCenter(0.35);
$p1->SetLegends(($legend));
$p1->SetSliceColors(array_reverse($colors));

$graph->Add($p1);
$graph->Stroke();

?>