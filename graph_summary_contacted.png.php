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

$data = array($phonestats['contacted'], $phonestats['notcontacted'], $phonestats['duplicates'], $phonestats['nocontacts']);
$legend = array("Contacted: %d", "Not Contacted: %d", "Duplicates: %d", "No Contact Info: %d");
$colors = array("lightgreen", "red", "lightgray", "yellow");


//var_dump($data);
//var_dump($legend);
//var_dump($colors);
//exit();

if (array_sum($data) == 0) {
	redirect("img/spacer.gif");
}


$graph = new PieGraph(300,100,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);
$graph->SetAntiAliasing();

$graph->title->Set("People to Contact: " . number_format($phonestats["totalpeople"]));
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->legend->Pos(0.00,0.3,"right","top");


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