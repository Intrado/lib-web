<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_pie.php");
include ("jpgraph/jpgraph_pie3d.php");
include ("jpgraph/jpgraph_canvas.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

$jobid = $_GET['jobid'] + 0;
//check userowns and viewsystemreports
if (!userOwns("job",$jobid) && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

$jobstats = $_SESSION['jobstats'][$jobid];
if ($_GET['valid'] != $jobstats['validstamp'])
	redirect('unauthorized.php');


$phonestats = $jobstats['phone'];

$data = array();
$legend = array();
$colors = array();

if (isset($jobstats['phone'])) {

	if ($numnoresponse = $jobstats['phone']['contacted'] - $jobstats['survey']['phoneparticipants']) {
		$data[] = $numnoresponse;
		$legend[] = "No response (phone): %d";
		$colors[] = "gray";
	}
	if ($jobstats['survey']['phoneparticipants']) {
		$data[] = $jobstats['survey']['phoneparticipants'];
		$legend[] = "Participated (phone): %d";
		$colors[] = "lightgreen";
	}
}


if (isset($jobstats['email'])) {

	if ($numnoresponse = $jobstats['email']['sentemails'] - $jobstats['survey']['emailparticipants']) {
		$data[] = $numnoresponse;
		$legend[] = "No response (web): %d";
		$colors[] = "tan";
	}
	if ($jobstats['survey']['emailparticipants']) {
		$data[] = $jobstats['survey']['emailparticipants'];
		$legend[] = "Participated (web): %d";
		$colors[] = "#1DC10";
	}
}

//var_dump($data);
//var_dump($legend);
//var_dump($colors);
//exit();

if (array_sum($data) == 0) {
	$graph = new CanvasGraph(350,100,"auto");
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


$graph = new PieGraph(350,100,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);
$graph->SetAntiAliasing();

//$graph->title->Set("Participants of People Contacted");
//$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->legend->Pos(0.00,0.2,"right","top");


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