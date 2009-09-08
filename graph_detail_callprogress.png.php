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

$type = isset($_GET['type']) ? $_GET['type'] : '';
if (!in_array($type, array('phone', 'email', 'sms')))
	// TODO: Perhaps redirect to unauthorized.php?
	$type = 'phone'; // Default to phone

if ($type == 'phone') {
	$cpcolors = array(
		"A" => "lightgreen",
		"M" => "#1DC10",
		"B" => "orange",
		"N" => "tan",
		"X" => "black",
		"F" => "#8AA6B6"
	);
	$cpcodes = array(
		"A" => "Answered",
		"M" => "Machine",
		"B" => "Busy",
		"N" => "No Answer",
		"X" => "Disconnect",
		"F" => "Unknown"
	);
} else if ($type == 'email' || $type == 'sms') {
	$cpcolors = array(
		"sent" => "lightgreen",
		"unsent" => "blue"
	);
	$cpcodes = array(
		"sent" => "Sent",
		"unsent" => "Unsent"
	);
}
// Common code colors
$cpcolors = array_merge($cpcolors, array(
	"notattempted" => "blue",
	"blocked" => "#CC00CC",
	"duplicate" => "lightgray",
	"nocontacts" => "#aaaaaa",
	"declined" => "yellow"
));
// Common code titles
$cpcodes = array_merge($cpcodes, array(
	"notattempted" => "Not Attempted",
	"blocked" => "Blocked",
	"duplicate" => "Duplicate",
	"nocontacts" => "No Phone #",
	"declined" => "No Phone Selected"
));
// Correct code titles depending on type, default phone.
if ($type == 'email') {
	$cpcodes['nocontacts'] = 'No Email';
	$cpcodes['declined'] = 'No Email Selected';
}
else if ($type == 'sms') {
	$cpcodes['nocontacts'] = 'No SMS';
	$cpcodes['declined'] = 'No SMS Selected';
}

$data = array();
$legend = array();
$colors = array();
$labels = array();
$count=0;
foreach($cpcodes as $index => $code){
	$data = array_fill(0, count($cpcolors), 0);
	$count++;
	$color = $cpcolors[$index];
	$data[$count-1] = $jobstats[$type][$index];
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
$scalex = isset($_GET['scalex']) ? $_GET['scalex'] + 0 : 1;
$scaley = isset($_GET['scaley']) ? $_GET['scaley'] + 0 : 1;
$graph = new Graph(500*$scalex,400*$scaley,'auto');
//$graph->SetShadow();
$graph->img->SetMargin(100,60,20,130);

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