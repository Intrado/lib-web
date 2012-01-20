<?
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

$data = QuickQueryRow("select sum(status!='new'), sum(status='new') from reportperson where jobid='$jobid'");

$legend = array("Queued: %d", "Processing: %d");
$colors = array("blue", "yellow");


//var_dump($data);
//var_dump($legend);
//var_dump($colors);
//exit();

$graph = new PieGraph(250,100,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);
$graph->SetAntiAliasing();

$graph->title->Set("Items to process: " . number_format(array_sum($data)));
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->legend->Pos(0.00,0.3,"right","top");


$p1 = new PiePlot3D($data);

$p1->SetLabelType(PIE_VALUE_ABS);
$p1->value->SetFormat('');
$p1->value->Show();
$size = 0.4;
$p1->SetSize($size);
$p1->SetCenter(0.27);
$p1->SetLegends($legend);
$p1->SetSliceColors($colors);

$graph->Add($p1);
$graph->Stroke();

?>