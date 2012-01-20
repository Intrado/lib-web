<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");
include_once("inc/reportutils.inc.php");

include ("jpgraph/jpgraph.php");
include ("jpgraph/jpgraph_bar.php");

require_once('inc/graph.inc.php');

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
	$colors = array(
		"A" => "lightgreen",
		"M" => "#1DC10",
		"B" => "orange",
		"N" => "tan",
		"X" => "black",
		"F" => "#8AA6B6"
	);
	$resultcodes = array(
		"A" => "Answered",
		"M" => "Machine",
		"B" => "Busy",
		"N" => "No Answer",
		"X" => "Disconnect",
		"F" => "Unknown"
	);
} else if ($type == 'email' || $type == 'sms') {
	$colors = array(
		"sent" => "lightgreen",
		"unsent" => "blue"
	);
	$resultcodes = array(
		"sent" => "Sent",
		"unsent" => "Unsent"
	);
}
// Common code colors
$colors = array_merge($colors, array(
	"notattempted" => "blue",
	"blocked" => "#CC00CC",
	"duplicate" => "lightgray",
	"nocontacts" => "#aaaaaa",
	"declined" => "yellow"
));
// Common code titles
$resultcodes = array_merge($resultcodes, array(
	"notattempted" => "Not Attempted",
	"blocked" => "Blocked",
	"duplicate" => "Duplicate",
	"nocontacts" => "No Phone #",
	"declined" => "No Phone Selected"
));
// Correct code titles depending on type, default phone.
if ($type == 'email') {
	$resultcodes['nocontacts'] = 'No Email';
	$resultcodes['declined'] = 'No Email Selected';
}
else if ($type == 'sms') {
	$resultcodes['nocontacts'] = 'No SMS';
	$resultcodes['declined'] = 'No SMS Selected';
}

$graphdata = array();
$graphcolors = array();
$labels = array();
foreach($resultcodes as $index => $label) {
	$graphdata[] = $jobstats[$type][$index];
	$graphcolors[] = $colors[$index];
	$labels[] = $label;
}

$bars = array();
$bar = new BarPlot($graphdata);
$bar->SetFillColor($graphcolors);
$bar->SetAlign('center');
$bar->value->Show();
$bar->value->SetFormat('%d');
$bars[] = $bar;

$scalex = isset($_GET['scalex']) ? $_GET['scalex'] + 0 : 1;
$scaley = isset($_GET['scaley']) ? $_GET['scaley'] + 0 : 1;
output_bar_graph($bars, $labels, 500*$scalex,400*$scaley);
?>