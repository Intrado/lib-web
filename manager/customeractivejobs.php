<?
include_once("common.inc.php");
include_once("../inc/table.inc.php");


if(isset($_GET['customer'])){
	$customerid = $_GET['customer'] + 0;
} else if(isset($_GET['user'])){
	$userid = $_GET['user'];
	$extra = "and user.id = '$userid'";
} else {
	$extra = "";
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


$customers = QuickQueryList("select id, urlcomponent from customer",true);


$res = Query("select id, dbhost, dbusername, dbpassword from shard order by id");
$shards = array();
while($row = DBGetRow($res)){
	$shards[$row[0]] = $db = mysql_connect($row[1], $row[2], $row[3]);
	mysql_select_db("aspshard",$db);
}




$calldata = array();
$jobs = array();
foreach ($shards as $shardid => $sharddb) {
	$query = "select j.systempriority, j.customerid, j.id, jt.type, jt.attempts, jt.sequence,
					jt.status, j.phonetaskcount, j.timeslices, count(*)
			from qjobtask jt
			straight_join qjob j on (j.id = jt.jobid and j.customerid = jt.customerid)
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

}


include("nav.inc.php");


$prinames = array (1 => "Emergency", 2 => "Attendance", 3 => "General");
$pricolors = array (1 => "#ff0000", 2 => "#ffff00", 3 => "#0000ff");




foreach ($calldata as $pri => $pricalldata) {


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
				$slicesize = (int) ($jobs[$customerid][$jobid]["phonetaskcount"] / $slicesize);
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
								$sequencecalldata['waiting']
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
	<h2 style="border: 3px solid <?= $pricolors[$pri] ?>;">Priority: <?=$prinames[$pri]?>
	<table border=1>
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
					"Waiting"
				);
	$formatters = array (0 => "fmt_html",
						3 => "fmt_number",
						4 => "fmt_number",
						5 => "fmt_html",
						9 => "fmt_number",
						10 => "fmt_number",
						11 => "fmt_number",
						12 => "fmt_number",
						13 => "fmt_number"

		);

	showTable($data, $titles, $formatters);

?>
	</table>
	</h2>
<?

}

?>


<div > All time stamps are in customer time. </div>
<?
include("navbottom.inc.php");
?>
