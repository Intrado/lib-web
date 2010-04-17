<?
include_once("common.inc.php");
include_once("../inc/table.inc.php");


if (!$MANAGERUSER->authorized("activejobs"))
	exit("Not Authorized");

if(isset($_GET['cid'])){
	$customerid = $_GET['cid'] + 0;
	$extrasql = " and j.customerid = $customerid ";
	if(isset($_GET['user'])){
		$userid = $_GET['user'] + 0;
		$extrasql .= " and j.userid = $userid ";
	}
} else {
	$extrasql = "";
}

if (isset($_GET['customer'])){
	$dispatchtype = 'customer';
	$extrasql .= " and j.dispatchtype = 'customer' ";
} else if (isset($_GET['system'])) {
	$dispatchtype = 'system';
	$extrasql .= " and j.dispatchtype = 'system' ";	
} else {
	$dispatchtype = 'system';
	$extrasql .= " and j.dispatchtype = 'system' ";	
}


/*

//TODO filter for a customer


foreach shard:

select j.systempriority, j.customerid, j.id, jt.type, jt.attempts, jt.sequence,
		jt.status, j.phonetaskcount, j.timeslices, count(*)
from qjobtask jt
straight_join qjob j on (j.id = jt.jobid and j.customerid = jt.customerid)
group by jt.status, jt.customerid, jt.jobid, jt.type, jt.attempts, jt.sequence

order by j.systempriority, j.customerid, j.id, jt.type, jt.attempts, jt.sequence, jt.status



show by
syspri
	customer
		job (totalphone, timeslices)
			type
				attempt
					sequence

*/

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
function fmt_play_link($row, $index){
	$url = "";
	if($row[2])
		$url = "<a onclick='popup(\"customerplaymessage.php?customerid=" . $row[14] . "&jobid=" . $row[2] . "\", 400, 500); return false;' href=\"#\" title='Play Message'><img src='img/s-play.png' border=0></a>";
	return $url;
}


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
$jobs = array();
$schedjobs = array();
foreach ($shards as $shardid => $sharddb) {
	Query("use aspshard", $sharddb);
	$query = "select j.systempriority, j.customerid, j.id, jt.type, jt.attempts, jt.sequence,
					jt.status, j.phonetaskcount, j.timeslices, count(*)
			from qjobtask jt
			straight_join qjob j on (j.id = jt.jobid and j.customerid = jt.customerid)
			where 1 $extrasql
			group by jt.status, jt.customerid, jt.jobid, jt.type, jt.attempts, jt.sequence
			order by j.systempriority, j.customerid, j.id, jt.type, jt.attempts, jt.sequence
			";
	$res = Query($query,$sharddb);
	while ($row = DBGetRow($res)) {

		$calldata[$row[0]][$row[1]][$row[2]][$row[3]][$row[4]][$row[5]][$row[6]] = $row[9];

		$jobs[$row[1]][$row[2]]["phonetaskcount"] = $row[7];
		@$jobs[$row[1]][$row[2]]["phonetaskremaining"] += $row[9];


		$jobs[$row[1]][$row[2]]["timeslices"] = $row[8];
	}


	QuickUpdate("set time_zone='GMT'",$sharddb);

	$query = "select j.systempriority, j.customerid, j.id, j.startdate, j.starttime, j.timezone,
			timediff(addtime(j.startdate,j.starttime), convert_tz(now(),'GMT',j.timezone)) as timetostart
			from qjob j where j.status='scheduled'
			order by hour(timetostart), minute(timetostart), second(timetostart), j.systempriority, j.customerid, j.id
			";
	$res = Query($query,$sharddb);
	while ($row = DBGetRow($res)) {
		list($hours,$minutes,$seconds) = explode(":",$row[6]);
		$days = floor(abs($hours)/24);
		if($hours < 0)
			$days = 0 - $days;
		$hours = $hours%24;
			
		$timetorun = implode(":",array($hours,$minutes,$seconds)) . ($days ? " + $days Days" : "");
		$schedjobs[$row[0]][($days*24*60*60)+($hours*60*60)+($minutes*60)+$seconds][] = array ($row[1], $customers[$row[1]], $row[2], $row[3], $row[4], $row[5], $timetorun);

	}
}

///////////////////////////////////////////////////
// Display
///////////////////////////////////////////////////

include("nav.inc.php");
?>

Task dispatch type:&nbsp;
<select id='dispatchtypeselect' onchange="window.location='customeractivejobs.php?'+this.options[this.selectedIndex].value;">
<option value='system' <?=($dispatchtype=='system')?"selected":""?>>Asp</option>
<option value='customer' <?=($dispatchtype=='customer')?"selected":""?>>SmartCall</option>
</select>

<?

$prinames = array (1 => "Emergency", 2 => "High", 3 => "General");
$pricolors = array (1 => "#ff0000", 2 => "#ffff00", 3 => "#0000ff");


for ($pri = 1; $pri <=3 ; $pri++) {
	if (!isset($calldata[$pri]) && !isset($schedjobs[$pri]))
		continue;
?>
	<h2 style="border: 3px solid <?= $pricolors[$pri] ?>;"><?=$prinames[$pri]?><hr>
<?
	if (isset($calldata[$pri])) {

	$pricalldata = $calldata[$pri];

	$data = array();
	$pritotals = array();
	foreach ($pricalldata as $customerid => $custcalldata) {
		$showcust = true;
		foreach ($custcalldata as $jobid => $jobcalldata) {
			$showjob = true;

			@$pritotals["phonetaskremaining"] += $jobs[$customerid][$jobid]["phonetaskremaining"];
			@$pritotals["phonetaskcount"] += $jobs[$customerid][$jobid]["phonetaskcount"];

			$slicesize = $jobs[$customerid][$jobid]["timeslices"];
			if ($slicesize) {
				$slicesize = (int) max(2,($jobs[$customerid][$jobid]["phonetaskcount"] / $slicesize));
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
								$showjob ? $jobs[$customerid][$jobid]["phonetaskremaining"] : "",
								$showjob ? $jobs[$customerid][$jobid]["phonetaskcount"] : "",
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
						$data[] = $row;


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


	$totalsrow = array ("<b>Total</b>",
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
	$data[] = $totalsrow;



?>
	Active Jobs:
	<table>
<?

	$titles = array("Customer id",
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
	$formatters = array (0 => "fmt_html",
						1 => "fmt_custurl",
						3 => "fmt_number",
						4 => "fmt_number",
						5 => "fmt_html",
						9 => "fmt_number",
						10 => "fmt_number",
						11 => "fmt_number",
						12 => "fmt_number",
						13 => "fmt_number",
						14 => "fmt_play_link"

		);

	showTable($data, $titles, $formatters);

?>
	</table>

<?
	}

	if (isset($schedjobs[$pri])) {
?>
		<hr>Scheduled jobs:
		<table border=1>
<?
			$titles = array ("Customer id",
						"Customer url",
						"Job id",
						"Start Date",
						"Start Time",
						"Timezone",
						"Time until run"
					);
		ksort($schedjobs[$pri]);
		$scheddata = array();
		foreach ($schedjobs[$pri] as $schedstart => $schedjob) 
			foreach ($schedjob as $job)
				$scheddata[] = $job;
		
		showTable($scheddata, $titles, array(1 => "fmt_custurl"));
	
?>
		</table>
<?
	}
?>
	</h2>
<?

}

?>


<div > All time stamps are in customer time. </div>
<?
include("navbottom.inc.php");
?>
