<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include_once ("jpgraph/jpgraph.php");
include_once ("jpgraph/jpgraph_pie.php");
include_once ("jpgraph/jpgraph_pie3d.php");
include_once ("jpgraph/jpgraph_canvas.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point


$jobid = DBSafe($_GET['jobid']);
if (!userOwns("job",$jobid) && !$USER->authorize('viewsystemreports')) {
	header("Content-type: image/gif");
	readfile("img/icon_logout.gif");
	exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
if (!in_array($type, array('phone', 'email', 'sms')))
	$type = 'phone'; // Default to phone

switch ($type) {
	case 'phone':
		$coalesceSQL = "
			if(rc.result not in ('A', 'M') and rc.numattempts > 0 and rc.numattempts < js.value and j.status not in ('complete','cancelled'), 'retry', null),
			if(rc.result='notattempted' and j.status in ('complete','cancelled'), 'fail', null),
			if(rc.result not in ('A', 'M', 'duplicate', 'blocked') and rc.numattempts = 0 and j.status not in ('complete','cancelled'), 'inprogress', null),
			rc.result
		";
		$cpcolors = array(
			"A" => "lightgreen",
			"M" => "#1DC10",
			"B" => "orange",
			"N" => "tan",
			"X" => "black",
			"F" => "#8AA6B6",
			"inprogress" => "blue",
			"retry" => "cyan"
		);

		$cpcodes = array(
			"A" => "Answered",
			"M" => "Machine",
			"B" => "Busy",
			"N" => "No Answer",
			"X" => "Disconnect",
			"F" => "Unknown",
			"inprogress" => "Queued",
			"retry" => "Retrying"
		);
		break;
	case 'email':
		$coalesceSQL = "
			if(rc.result not in ('sent', 'duplicate', 'declined') and rc.numattempts = 0 and j.status not in ('complete','cancelled'), 'inprogress', null),
			rc.result
		";
		$cpcolors = array(
			"sent" => "lightgreen",
			"unsent" => "#1DC10",
			"duplicate" => "lightgray",
			"declined" => "yellow",
			"inprogress" => "blue"
		);
		$cpcodes = array(
			"sent" => "Sent",
			"unsent" => "Unsent",
			"duplicate" => "Duplicate",
			"declined" => "No Email Selected",
			"inprogress" => "Queued"
		);
		break;
	case 'sms':
		$coalesceSQL = "
			if(rc.result not in ('sent', 'duplicate', 'declined') and rc.numattempts = 0 and j.status not in ('complete','cancelled'), 'inprogress', null),
			rc.result
		";
		$cpcolors = array(
			"sent" => "lightgreen",
			"unsent" => "#1DC10",
			"duplicate" => "lightgray",
			"declined" => "yellow",
			"inprogress" => "blue"
		);
		$cpcodes = array(
			"sent" => "Sent",
			"unsent" => "Unsent",
			"duplicate" => "Duplicate",
			"declined" => "No SMS Selected",
			"inprogress" => "Queued"
		);
		break;
}
$query = "
	select count(*) as cnt, coalesce($coalesceSQL) as callprogress2
	from job j
	inner join reportperson rp on (rp.jobid=j.id)
	left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
	inner join jobsetting js on (js.jobid = j.id and js.name = 'maxcallattempts')
	where rp.type=?
	and rp.jobid=?
	group by callprogress2
";

$data = $cpcodes;
// Initialize all values in $data to false.
foreach ($data as $k => $v) {
	$data[$k] = false;
}
$legend = $data;
$colors = $data;
if ($result = Query($query, false, array($type, $jobid))) {
	while ($row = DBGetRow($result)) {
		if($row[1] == "fail")
			$row[1] = "F";

		if(!isset($data[$row[1]])){
			continue;
		} else if($row[1] == "F"){
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

if ($type == 'phone')
	$graph->title->Set("Phone results - " . date("g:i:s a"));
else if ($type == 'email')
	$graph->title->Set("Email results - " . date("g:i:s a"));
else if ($type == 'sms')
	$graph->title->Set("SMS results - " . date("g:i:s a"));

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
