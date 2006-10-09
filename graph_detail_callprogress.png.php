<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");


include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_pie.php");
include ("jpgraph/jpgraph_pie3d.php");
include ("jpgraph/jpgraph_canvas.php");

$jobid = $_GET['jobid'] + 0;
//check userowns or customerowns and viewsystemreports
if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports') && customerOwns("job",$jobid))) {
	redirect('unauthorized.php');
}

$jobstats = $_SESSION['jobstats'][$jobid];
if ($_GET['valid'] != $jobstats['validstamp'])
	redirect('unauthorized.php');

$phonestats = $jobstats['phone'];

$data = array($phonestats['A'] - $phonestats['M'], $phonestats['remainingcalls']);
$legend = array("Completed: %d","Remaining: %d");
$colors = array("lightgreen", "blue");

$cpcolors = array(
	"A" => "lightgreen",
	"M" => "#1DC10",
	"B" => "orange",
	"N" => "tan",
	"X" => "black",
	"F" => "red",
	"C" => "yellow",
	"nullcp" => "blue"
);

$cpcodes = array(
	"A" => "Answered",
	"M" => "Machine",
	"B" => "Busy",
	"N" => "No Answer",
	"X" => "Disconnect",
	"F" => "Failed",
	"C" => "Calling",
	"nullcp" => "Not Attmp."
);

$data = array();
$legend = array();
$colors = array();
foreach ($cpcodes as $code => $title) {
	$color = $cpcolors[$code];

	if ($phonestats[$code] == 0)
		continue;

	$data[] = $phonestats[$code];
	$legend[] = $title . ": %d";
	$colors[] = $color;
}


//var_dump($data);
//var_dump($legend);
//var_dump($colors);
//exit();

$graph = new PieGraph(250,160,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);
$graph->SetAntiAliasing();

$graph->title->Set("Phone Numbers to Call: " . number_format($phonestats['totalcalls']));
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->legend->Pos(0.00,0.55,"right","center");


$p1 = new PiePlot3D($data);

$p1->SetLabelType(PIE_VALUE_ABS);
$p1->value->SetFormat('');
$p1->value->Show();
$size = 0.26;
$p1->SetSize($size);
$p1->SetCenter(0.27);
$p1->SetLegends(($legend));
$p1->SetSliceColors(array_reverse($colors));

$graph->Add($p1);
$graph->Stroke();

?>