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
if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports'))) {
	redirect('unauthorized.php');
}

$jobstats = $_SESSION['jobstats'][$jobid];
if ($_GET['valid'] != $jobstats['validstamp'])
	redirect('unauthorized.php');


//figure out how much space for the pie charts
$piesperrow	= 2;
$piesize 	= 40;
$xoffset 	= 120;
$xdelta 	= 240;

$yoffset	= 85;
$ydelta		= 150;

$startwidth	= 50;
$widthdelta	= 225;

$startheight= 25;
$heightdelta= 150;

$cols = min($piesperrow,count($jobstats['survey']['questions']));
$rows = ceil(count($jobstats['survey']['questions'])/(float)$piesperrow);


$width = $startwidth + ($cols * $widthdelta);
$height = $startheight + ($rows * $heightdelta);


//echo "c:$cols r:$rows w:$width h:$height<br>";
//exit();


$legend = range(1,9);
$colorset = array("blue","red","green","yellow","orange","purple","cyan","lightslateblue","forestgreen");


$graph = new PieGraph($width,$height,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);
$graph->SetAntiAliasing();

//$graph->title->Set("Participants of People Contacted");
//$graph->title->SetFont(FF_FONT1,FS_BOLD);

//$graph->legend->Pos(0.0,0.05,"right","top");

$i = 0;
foreach ($jobstats['survey']['questions'] as $question) {

	$questiondata = $question['answers'];
//	$questiondata = array (1=> 1,2,3,4,5,6,7,8,9);

	$labels = array();
	$data = array();
	$colors = array();
	foreach ($questiondata as $index => $value) {
		if ($value) {
			$data[] = $value;
			$labels[] = "#" . ($index) . ": %.1f%%";
			$colors[] = $colorset[$index-1];
		}
	}

//var_dump(($data));
//exit();

	$p1 = new PiePlot(($data));

//	$p1->SetLabelType(PIE_VALUE_ABS);

	$p1->SetLabelType(PIE_VALUE_PER);

	$p1->SetLabels($labels);
	$p1->SetLabelPos(1);



	$p1->title->Set($question['label']);
	$p1->title->SetBox(array(255,255,255,0.6),false,false,0,1);
	$p1->title->SetMargin(5);

	$p1->SetSize($piesize);
	$p1->SetCenter($xoffset + ($xdelta * ($i % $piesperrow)),
					$yoffset + ($ydelta * floor($i/$piesperrow)));
	$p1->SetGuideLines(true,true,false);
	$p1->SetGuideLinesAdjust(1.2);

//	$p1->value->SetFormat('');
	$p1->value->Show();
	$p1->value->SetFont(FF_FONT0);

//	if ($i+1 == $cols)//only set on top right pie chart
//		$p1->SetLegends(($legend));
	$p1->SetSliceColors(array_reverse($colors));


	$graph->Add($p1);
	$i++;
}

$graph->Stroke();

?>