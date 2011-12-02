<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("obj/JobFilter.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("activejobs"))
	exit("Not Authorized");



$jobfilter = new JobFilter("phone");
$jobfilter-> handleChages();

$customers = QuickQueryList("select id, urlcomponent from customer",true);

$res = Query("select id, dbhost, dbusername, dbpassword from shard order by id");
$shards = array();
while($row = DBGetRow($res)){
	$dsn = 'mysql:dbname=aspshard;host='.$row[1];
	$db = new PDO($dsn, $row[2], $row[3]);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	$shards[$row[0]] = $db;
}

$calldata = array();
$activejobs = array();
$extrasql = "";
$extraargs = array();

if(isset($_GET['cid'])){
	$customerid = $_GET['cid'] + 0;
	$extrasql .= " and j.customerid = $customerid ";
	if(isset($_GET['user'])){
		$userid = $_GET['user'] + 0;
		$extrasql .= " and j.userid = $userid ";
	}
}
if ($jobfilter->settings['dispatchtype'] == 'customer'){
	$extrasql .= " and j.dispatchtype = 'customer' ";
} else {
	$extrasql .= " and j.dispatchtype = 'system' ";
}

foreach ($shards as $shardid => $sharddb) {
	Query("use aspshard", $sharddb);
	// TODO remove 'phone' leftover from qjobtask.type field
	$query = "select j.systempriority, j.customerid, j.id, 'phone', jt.attempts, jt.sequence,
					jt.status, j.phonetaskcount, j.timeslices, count(*)
			from qjobtask jt
			straight_join qjob j on (j.id = jt.jobid and j.customerid = jt.customerid)
			where 1 $extrasql
			group by jt.status, jt.customerid, jt.jobid, jt.attempts, jt.sequence
			order by j.systempriority, j.customerid, j.id, jt.attempts, jt.sequence
			";
	$res = Query($query,$sharddb,$extraargs);
	while ($row = DBGetRow($res)) {

		$calldata[$row[0]][$row[1]][$row[2]][$row[3]][$row[4]][$row[5]][$row[6]] = $row[9];

		$activejobs[$row[1]][$row[2]]["phonetaskcount"] = $row[7];
		@$activejobs[$row[1]][$row[2]]["phonetaskremaining"] += $row[9];


		$activejobs[$row[1]][$row[2]]["timeslices"] = $row[8];
	}
}



////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


function fmt_html ($row,$index) {
	return $row[$index];
}

function fmt_number ($row,$index) {
	if ($row[$index])
		return number_format($row[$index]);
	else
		return "";
}

function fmt_custurl($row, $index){
	$url = "<a href=\"customerlink.php?id=" . $row[0] ."\" target=\"_blank\">" . $row[1] . "</a>";
	return $url;
}

//index 2 is jobid
function fmt_play_activejobs($row, $index){
	$url = "";
	if($row[2])
		$url = "<a onclick='popup(\"customerplaymessage.php?customerid=" . $row[14] . "&jobid=" . $row[2] . "\", 400, 500); return false;' href=\"#\" alt='' title='Play Message'><img src='mimg/s-play.png' border=0></a>";
	return $url;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Customer Jobs');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array()); ?>
</script>
<?

$jobfilter->render();

$foundjobs = false;
startWindow(_L('Jobs'));
$prinames = array (1 => "Emergency", 2 => "High", 3 => "General");
$pricolors = array (1 => "#ff0000", 2 => "#ffff00", 3 => "#0000ff");

$data = array();
$summarydata = array();

foreach($jobfilter->settings['priorities'] as $pri) {
	if (isset($calldata[$pri])) {
		$foundjobs = true;
		$pricalldata = $calldata[$pri];
		$pridata = array();
		$pritotals = array();
		foreach ($pricalldata as $customerid => $custcalldata) {
			$showcust = true;
			foreach ($custcalldata as $jobid => $jobcalldata) {
				$showjob = true;
	
				@$pritotals["phonetaskremaining"] += $activejobs[$customerid][$jobid]["phonetaskremaining"];
				@$pritotals["phonetaskcount"] += $activejobs[$customerid][$jobid]["phonetaskcount"];
	
				$slicesize = $activejobs[$customerid][$jobid]["timeslices"];
				if ($slicesize) {
					$slicesize = (int) max(2,($activejobs[$customerid][$jobid]["phonetaskcount"] / $slicesize));
					@$pritotals["slicesize"] += $slicesize;
				} else {
					$slicesize = "&#8734;";
				}
	
				foreach ($jobcalldata as $type => $typecalldata) {
					$showtype = true;
					foreach ($typecalldata as $attempt => $attemptcalldata) {
						$showattempt = true;
						foreach ($attemptcalldata as $sequence => $sequencecalldata) {
							$row = @array(
									$showcust ? $customerid : "",
									$showcust ? $customers[$customerid] : "",
									$showjob ? $jobid : "",
									$showjob ? $activejobs[$customerid][$jobid]["phonetaskremaining"] : "",
									$showjob ? $activejobs[$customerid][$jobid]["phonetaskcount"] : "",
									$showjob ? $slicesize : "",
									$showtype ? $type : "",
									$showattempt ? $attempt : "",
									$sequence,
									$sequencecalldata['active'],
									$sequencecalldata['assigned'],
									$sequencecalldata['progress'],
									$sequencecalldata['pending'],
									$sequencecalldata['waiting'],
									$customerid
								);
							$pridata[] = $row;
	
	
							@$pritotals["active"] += $sequencecalldata['active'];
							@$pritotals["assigned"] += $sequencecalldata['assigned'];
							@$pritotals["progress"] += $sequencecalldata['progress'];
							@$pritotals["pending"] += $sequencecalldata['pending'];
							@$pritotals["waiting"] += $sequencecalldata['waiting'];
	
							$showcust = $showjob = $showtype = $showattempt = false;
						}
					}
				}
			}
		}
		$totalsrow = array (
			"<b>Total</b>",
			"",
			"",
			$pritotals["phonetaskremaining"],
			$pritotals["phonetaskcount"],
			$pritotals["slicesize"] ? $pritotals["slicesize"] : "&#8734;",
			"",
			"",
			"",
			$pritotals["active"],
			$pritotals["assigned"],
			$pritotals["progress"],
			$pritotals["pending"],
			$pritotals["waiting"]
		);
		$pridata[] = $totalsrow;
		$data[$pri] = $pridata;
		$summarydata[] = array (
			"<b>$prinames[$pri] Total</b>",
			$pritotals["phonetaskremaining"],
			$pritotals["phonetaskcount"],
			$pritotals["slicesize"] ? $pritotals["slicesize"] : "&#8734;",
			$pritotals["active"],
			$pritotals["assigned"],
			$pritotals["progress"],
			$pritotals["pending"],
			$pritotals["waiting"]
		);
	}
}
if (count($data)) {
	$titles = array(
		"Priority",
		"job ph remain",
		"job ph total",
		"job throttle",
		"Active",
		"Assigned",
		"Progress",
		"Pending",
		"Waiting",
	);
	$formatters = array (
		0 => "fmt_html",
		1 => "fmt_number",
		2 => "fmt_number",
		3 => "fmt_number",
		4 => "fmt_number",
		5 => "fmt_number",
		6 => "fmt_number",
		7 => "fmt_number",
		8 => "fmt_number"
	);
	echo "<div style=\"margin-top:10px;border: 3px solid black;\">";
	echo "<h2>Active Jobs Summary: </h2><table>";
	showTable($summarydata, $titles, $formatters);
	echo "</table>";
	echo "</div>";
	
	$titles = array(
		"Customer id",
		"Customer url",
		"Job id",
		"job ph remain",
		"job ph total",
		"job throttle",
		"Type",
		"attempt",
		"sequence",
		"Active",
		"Assigned",
		"Progress",
		"Pending",
		"Waiting",
		"Play Message"
	);
	$formatters = array (
		0 => "fmt_html",
		1 => "fmt_custurl",
		3 => "fmt_number",
		4 => "fmt_number",
		5 => "fmt_html",
		9 => "fmt_number",
		10 => "fmt_number",
		11 => "fmt_number",
		12 => "fmt_number",
		13 => "fmt_number",
		14 => "fmt_play_activejobs"
	);
	foreach($data as $pri => $pridata) {
		echo "<div style=\"margin-top:10px;border: 3px solid $pricolors[$pri];\"><h2>$prinames[$pri]</h2><hr />";
		echo "Active Jobs: <table>";
		showTable($pridata, $titles, $formatters);
		echo "</table>";
		echo "</div>";
	}
}

if (!$foundjobs) {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No jobs found for the current filter settings") . "<div>";
}
endWindow();

include_once("navbottom.inc.php");
?>