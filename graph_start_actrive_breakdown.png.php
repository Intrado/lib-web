<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_pie.php");
include ("jpgraph/jpgraph_pie3d.php");
include ("jpgraph/jpgraph_canvas.php");

$query = "
select count(*) as cnt,
		coalesce(if (wi.status='waiting', 'retry', cl.callprogress),
			if (wi.status not in ('fail','duplicate','scheduled'), 'inprogress',wi.status))
			as callprogress2


from jobworkitem wi
inner join job j on (wi.jobid=j.id)
left join	jobtask jt on
					(jt.jobworkitemid=wi.id)
left join	calllog cl on
					(cl.jobtaskid=jt.id and (cl.callattempt=jt.numattempts-1))
where j.userid=$USER->id and j.status='active'
and wi.type='phone'
group by callprogress2
";



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
	"scheduled" => "darkblue"

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
	"duplicate" => false,
	"fail" => false,
	"F" => false
);
$legend = $data;
$colors = $data;

if ($result = Query($query)) {
	while ($row = DBGetRow($result)) {
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

//echo mysql_error();
//var_dump($data);
//var_dump($legend);
//var_dump($colors);
//exit();

//check for no data
if (count($data) == 0 || array_sum($data) == 0) {
	header("Content-type: image/png");
	readfile("img/start_graph_placeholder.png");
	exit();
}

$graph = new PieGraph(300,200,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);
$graph->SetAntiAliasing();

//$graph->title->Set("Call Progress");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->legend->Pos(0.01,0.01,"right","top");


$p1 = new PiePlot3D($data);

$p1->SetLabelType(PIE_VALUE_ABS);
$p1->value->SetFormat('');
$p1->value->Show();
$size = 0.4;
$p1->SetSize($size);
$p1->SetCenter(0.27);
$p1->SetLegends(($legend));
$p1->SetSliceColors(array_reverse($colors));

$graph->Add($p1);
$graph->Stroke();

?>