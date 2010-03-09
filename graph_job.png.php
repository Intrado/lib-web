<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");

include_once ("jpgraph/jpgraph.php");
include_once ("jpgraph/jpgraph_pie.php");
include_once ("jpgraph/jpgraph_pie3d.php");
include_once ("jpgraph/jpgraph_canvas.php");

require_once('inc/graph.inc.php');

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

// Construct Query
if ($type == 'phone') {
	$coalesceSQL = "
		if(rc.result not in ('A', 'M') and rc.numattempts > 0 and rc.numattempts < js.value and j.status not in ('complete','cancelled'), 'retry', null),
		if(rc.result='notattempted' and j.status in ('complete','cancelled'), 'fail', null),
		if(rc.result not in ('A', 'M', 'duplicate', 'blocked') and rc.numattempts = 0 and j.status not in ('complete','cancelled'), 'inprogress', null),
		rc.result
	";

	$resultcodes = array(
		"A" => "Answered",
		"M" => "Machine",
		"B" => "Busy",
		"N" => "No Answer",
		"X" => "Disconnect",
		"F" => "Unknown",
		"inprogress" => "Queued",
		"retry" => "Retrying"
	);
} else {
	$coalesceSQL = "
		if(rc.result not in ('sent', 'duplicate', 'declined') and rc.numattempts = 0 and j.status not in ('complete','cancelled'), 'inprogress', null),
		rc.result
	";

	$resultcodes = array(
		"sent" => "Sent",
		"unsent" => "Unsent",
		"duplicate" => "Duplicate",
		"declined" => ($type == 'email') ? "No Email Selected" : "No SMS Selected",
		"inprogress" => "Queued"
	);
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

$templatecolors = array(
	"A" => "lightgreen",
	"M" => "#1DC10",
	"B" => "orange",
	"N" => "tan",
	"X" => "black",
	"F" => "#8AA6B6",
	"sent" => "lightgreen",
	"unsent" => "#1DC10",
	"duplicate" => "lightgray",
	"declined" => "yellow",
	"inprogress" => "blue",
	"retry" => "cyan"
);

$data = $resultcodes;
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

		$legend[$row[1]] = $resultcodes[$row[1]] . ": %d";
		$colors[$row[1]] = $templatecolors[$row[1]];
	}
}

foreach ($data as $k => $v) {
	if ($v === false) {
		unset($data[$k]);
		unset($legend[$k]);
		unset($colors[$k]);
	}
}

if ($type == 'phone')
	$title = ("Phone results - " . date("g:i:s a"));
else if ($type == 'email')
	$title = ("Email results - " . date("g:i:s a"));
else if ($type == 'sms')
	$title = ("SMS results - " . date("g:i:s a"));

output_pie_graph(array_values($data), array_values($legend), array_values($colors), $title);
?>