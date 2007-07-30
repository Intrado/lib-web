<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_pie.php");
include ("jpgraph/jpgraph_pie3d.php");
include ("jpgraph/jpgraph_canvas.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

$query = "
select count(*) as cnt,
		coalesce(if (rp.status='waiting', 'retry', if(rc.result='notattempted', null, rc.result)),
			if (rp.status not in ('fail','duplicate','scheduled'), 'inprogress', rp.status))
			as callprogress2

from job j
inner join reportperson rp on (rp.jobid=j.id)
left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
where j.userid=$USER->id and j.status='active'
and rp.type='phone'
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
	"A" => false,
	"M" => false,
	"B" => false,
	"N" => false,
	"X" => false,
	"F" => false,
	"C" => false,
	"duplicate" => false,
	"fail" => false,
	"inprogress" => false,
	"retry" => false,
	"scheduled" => false
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
	readfile("img/spacer.gif");
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