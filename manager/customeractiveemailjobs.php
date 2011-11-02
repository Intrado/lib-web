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


$jobfilter = new JobFilter("email");
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
	$query = "select j.systempriority, j.customerid, j.id,
			jt.sequence, jt.status, count(*) as tasks
			from emailjobtask jt
			straight_join qjob j on (j.id = jt.jobid and j.customerid = jt.customerid)
			where 1 $extrasql
			group by jt.status, jt.customerid, jt.jobid, jt.sequence
			order by j.systempriority, j.customerid, j.id, jt.sequence
			";
	$res = Query($query,$sharddb,$extraargs);
	while ($row = DBGetRow($res,true)) {
		$calldata[$row["systempriority"]][$row["customerid"]][$row["id"]][$row["sequence"]][$row["status"]] = $row["tasks"];
		@$activejobs[$row["customerid"]][$row["id"]]["taskcount"] += $row["tasks"];
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

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Customer Email Jobs');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array()); ?>
</script>
<ul><li><a href="customersubmittedjobs.php?clear">View Scheduled and Processing Jobs</a></li></ul>
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
				
				@$pritotals["taskcount"] += $activejobs[$customerid][$jobid]["taskcount"];
				
				
				foreach ($jobcalldata as $sequence => $sequencecalldata) {
					$row = @array(
							$showcust ? $customerid : "",
							$showcust ? $customers[$customerid] : "",
							$showjob ? $jobid : "",
							$showjob ? $activejobs[$customerid][$jobid]["taskcount"] : "",
							$sequence,
							$sequencecalldata['active'],
							$sequencecalldata['assigned'],
							$sequencecalldata['progress'],
							$customerid
						);
					$pridata[] = $row;
					@$pritotals["active"] += $sequencecalldata['active'];
					@$pritotals["assigned"] += $sequencecalldata['assigned'];
					@$pritotals["progress"] += $sequencecalldata['progress'];
					
					$showcust = $showjob = false;
				}
			}
		}
		$totalsrow = array (
			"<b>Total</b>",
			"",
			"",
			$pritotals["taskcount"],
			"",
			$pritotals["active"],
			$pritotals["assigned"],
			$pritotals["progress"]
		);
		$pridata[] = $totalsrow;
		$data[$pri] = $pridata;
		$summarydata[] = array (
			"<b>$prinames[$pri] Total</b>",
			$pritotals["taskcount"],
			$pritotals["active"],
			$pritotals["assigned"],
			$pritotals["progress"]
		);
	}
}



if (count($data)) {
	$titles = array(
		"Priority",
		"Total",
		"Active",
		"Assigned",
		"Progress"
	);
	
	$formatters = array (
		0 => "fmt_html",
		1 => "fmt_number",
		2 => "fmt_number",
		3 => "fmt_number",
		4 => "fmt_number"
	);
	echo "<div style=\"margin-top:10px;border: 3px solid black;\">";
	echo "<h2>Active Email Jobs Summary: </h2><table>";
	showTable($summarydata, $titles, $formatters);
	echo "</table>";
	echo "</div>";
	
	$titles = array(
		"Customer id",
		"Customer url",
		"Job id",
		"Remaining",
		"Sequence",
		"Active",
		"Assigned",
		"Progress"
	);
	$formatters = array (
		0 => "fmt_html",
		1 => "fmt_custurl",
		2 => "fmt_number",
		3 => "fmt_number",
		4 => "fmt_html",
		5 => "fmt_number",
		6 => "fmt_number",
		7 => "fmt_number"
	);
	
	foreach($data as $pri => $pridata) {
		echo "<div style=\"margin-top:10px;border: 3px solid $pricolors[$pri];\"><h2>$prinames[$pri]</h2><hr />";
		echo "Active Email Jobs: <table>";
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