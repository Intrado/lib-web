<?
include_once("common.inc.php");
include_once("../inc/table.inc.php");

$customer = "";
$user="";
$authcustomer = "";
$shardcustomer = "";
$userid = "";
if(isset($_GET['customer'])){
	$customerid = $_GET['customer']+0;
	$authcustomer = "and c.id = '$customerid'";
	$shardcustomer = "and qj.customerid = '$customerid'";
} 
if(isset($_GET['user'])){
	$userid = $_GET['user']+0;
	$user = "and qj.userid = '$userid'";
}

function fmt_date($row, $index){
	$date = date("M j, g:i a", strtotime($row[$index] . " " . $row[$index+1]));
	return $date;
}

function calc_done($row, $index){

	return $row[$index-2] - $row[$index-1];
}

$titles = array("0" => "Shard Name",
				"1" => "Customer ID",
				"2" => "User ID",
				"3" => "Job ID",
				"4" => "Status",
				"5" => "System Priority",
				"6" => "Time Slices",
				"7" => "TimeZone",
				"8" => "Start Date/Time",
				"10" => "End Date/Time",
				"12" => "Total Tasks",
				"13" => "Inprog",
				"14" => "Done");




$result = Query("select s.name, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where 1 $authcustomer ");
$conninfo = array();
while($row = DBGetRow($result)){
	$conninfo[] = $row;
}

foreach($conninfo as $conn){
	if($custdb = DBConnect($conn[1], $conn[2], $conn[3], "aspshard")){
		$query = "select qj.customerid,
					qj.userid, 
					qj.id,
					qj.status,
					qj.systempriority,
					qj.timeslices,
					qj.timezone,
					qj.startdate,
					qj.starttime,
					qj.enddate,
					qj.endtime,
					qj.phonetaskcount,
					sum(qjt.jobid = qj.id and qjt.customerid = qj.customerid)
					from qjob qj
					left join qjobtask qjt on (qjt.jobid = qj.id)
					where qj.status = 'active'
					$shardcustomer
					$user
					group by qj.id
					order by qj.id";
			
		$result = Query($query, $custdb);
		$data = array();
		while($row = DBGetRow($result)){
			$data[] = array_merge(array($conn[0]), $row);
		}
		
	}
}
include("nav.inc.php");	
?>
<table border=.2>
<?
	showtable($data, $titles, array("8"=>"fmt_date", "10" => "fmt_date", "14" => "calc_done"));
?>
</table>
<div > All time stamps are in customer time. </div>
<?
include("navbottom.inc.php");
?>
			