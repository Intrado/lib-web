<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");
include_once("inc/reportutils.inc.php");

include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_bar.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if(isset($_GET['jobid'])){
	$jobid = $_GET['jobid'] + 0;
	if(!(userOwns("job", $jobid) || $USER->authorize('viewsystemreports'))){
		redirect("unauthorized.php");
	}
} else if(isset($_GET['startdate']) && isset($_GET['enddate']) && isset($_GET['jobtypes'])){
	$jobid = implode("", getJobList($_GET['startdate'], $_GET['enddate'], $_GET['jobtypes'], $_GET['surveyonly']));
}

$jobstats = $_SESSION['jobstats'][$jobid];
if ($_GET['valid'] != $jobstats['validstamp']){
	redirect('unauthorized.php');	
}

$phonestats = $jobstats['phone'];

$cpcolors = array(
	"A" => "lightgreen",
	"M" => "#1DC10",
	"B" => "orange",
	"N" => "tan",
	"X" => "black",
	"F" => "red",
	"notattempted" => "blue",
	"blocked" => "#CC00CC",
	"duplicate" => "lightgray",
	"nocontacts" => "#aaaaaa",
	"declined" => "yellow"
);

$cpcodes = array(
	"A" => "Answered",
	"M" => "Machine",
	"B" => "Busy",
	"N" => "No Answer",
	"X" => "Disconnect",
	"F" => "Failed",
	"notattempted" => "Not Attempted",
	"blocked" => "Blocked",
	"duplicate" => "Duplicate",
	"nocontacts" => "No Phone #",
	"declined" => "Declined"
);

$data = array();
$legend = array();
$colors = array();
$labels = array();
$count=0;
foreach($cpcodes as $index => $code){
	$data = array_fill(0, 10, 0);
	$count++;
	$color = $cpcolors[$index];
	$data[$count-1] = $phonestats[$index];
	$legend = $code;
	$labels[] = $code;
	
	$barname = "bar" . $count;
	$$barname = new BarPlot($data);
	$$barname->SetFillColor($color);
	$$barname->SetAlign('center');
	$$barname->value->Show();
	$$barname->value->SetFormat('%d');
}

// New graph with a drop shadow
$graph = new Graph(500,250,'auto');
//$graph->SetShadow();
$graph->img->SetMargin(100,40,20,100);

for($i=1;$i<=$count;$i++){
	$barname = "bar" . $i;
	$graph->Add($$barname);
}

// Use a "text" X-scale
$graph->SetScale("textlin");
$graph->xaxis->SetTickLabels($labels);
$graph->xaxis->SetPos("min");
$graph->xaxis->SetLabelAngle(90);
$graph->yaxis->SetTextLabelInterval(2);
$graph->yaxis->HideFirstTickLabel();
$graph->SetFrame(false);

// Use built in font
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

// Finally output the  image
$graph->Stroke();
?>