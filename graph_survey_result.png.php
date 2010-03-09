<?

include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");

include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_bar.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

$jobid = $_GET['jobid'] + 0;
//check userowns and viewsystemreports
if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports'))) {
	redirect('unauthorized.php');
}

$jobstats = $_SESSION['jobstats'][$jobid];
if ($_GET['valid'] != $jobstats['validstamp'])
	redirect('unauthorized.php');

$question = $_GET['question']+0;



$colorset = array("blue","red","green","yellow","orange","purple","cyan","lightslateblue","forestgreen");


$graph = new Graph(500,250,"auto");
//$graph->SetShadow();
$graph->SetFrame(false);

$i = 0;
$question=$jobstats['survey']['questions'][$question];

	$questiondata = $question['answers'];

$count=0;
foreach ($questiondata as $index => $value) {
	$data = array_fill(0, 9, 0);
	$count++;
	$data[$index-1] = $value;
	
	$barname = "bar" . $count;
	$$barname = new BarPlot($data);
	$$barname->SetFillColor($colorset[$index-1]);
	$$barname->SetAlign('center');
	$$barname->value->Show();
}
	
for($i=1;$i<=$count;$i++){
	$barname = "bar" . $i;
	$graph->Add($$barname);
}
$labels = array("#1", "#2", "#3", "#4", "#5", "#6", "#7", "#8", "#9");
// Use a "text" X-scale
$graph->SetScale("textlin");
$graph->xaxis->SetTickLabels($labels);
$graph->xaxis->SetPos("min");
$graph->yaxis->SetTextLabelInterval(2);
$graph->yaxis->HideFirstTickLabel();
$graph->SetFrame(false);
$graph->Stroke();

?>