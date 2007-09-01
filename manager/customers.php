<?
include_once("common.inc.php");
include_once("../obj/Customer.obj.php");
include_once("../inc/table.inc.php");


if (isset($_GET['showdisabled']))
	$customersql = "where not enabled";
else
	$customersql = "where enabled";


////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_custurl($row, $index){

	if (!isset($_GET['showdisabled']))
		$url = "<a href=\"customerlink.php?id=" . $row[0] ."\" >" . $row[1] . "</a>";
	else
		$url = '<span style="color: gray;">' . $row[1] . '</span>';
	return $url;
}

function fmt_status($row, $index){
	if($row[$index])
		return "Repeating Jobs Disabled";
	else
		return "&nbsp;";
}

//Row[7] is the max users value
function fmt_users($row, $index){
	if($row[7] != "unlimited" && $row[$index] > $row[7]){
		return "<div style='background-color: #ff0000;'>" . $row[$index] . "</div>";
	} else if($row[$index] == 0){
		return "<div style='background-color: #ffcccc;'>" . $row[$index] . "</div>";
	} else {
		return $row[$index];
	}
}

function fmt_actions($row, $index){
	$actions = '<a href="customeredit.php?id=' . $row[0] .'">Edit</a>&nbsp;|&nbsp;<a href="userlist.php?customer=' . $row[0] . '">Users</a>&nbsp;|&nbsp;<a href="customerimports.php?customer=' . $row[0] . '">Imports</a>&nbsp;|&nbsp;<a href="customeractivejobs.php?customer=' . $row[0] . '">Jobs</a>&nbsp;|&nbsp;<a href="customerpriorities.php?id=' . $row[0] . '">Priorities</a>';
	return $actions;
}

function fmt_jobcount($row, $index){
	if($row[$index] > 0){
		return "<div style='background-color: #ccffcc;'>" . $row[$index] . "<div>";
	} else {
		return $row[$index];
	}
}

////////////////////////////////////////////////////////////////////////////////
// data handling
////////////////////////////////////////////////////////////////////////////////

$res = Query("select id, dbhost, dbusername, dbpassword from shard order by id");
$shardinfo = array();
while($row = DBGetRow($res)){
	$shardinfo[$row[0]] = array($row[1], $row[2], $row[3]);
}

$customerquery = Query("select id, shardid, urlcomponent from customer $customersql order by shardid, id");
$customers = array();
while($row = DBGetRow($customerquery)){
	$customers[] = $row;
}
$currhost = "";
$custdb;
$data = array();
foreach($customers as $cust) {

	if($currhost != $cust[1]){
		$custdb = mysql_connect($shardinfo[$cust[1]][0],$shardinfo[$cust[1]][1], $shardinfo[$cust[1]][2])
			or die("Could not connect to customer database: " . mysql_error());
		$currhost = $cust[1];
	}
	mysql_select_db("c_" . $cust[0]);
	if($custdb){
		$row = array();
		$row[] = $cust[0];
		$row[] = $cust[2];
		$row[] = QuickQuery("select value from setting where name = 'displayname'", $custdb);
		$row[] = QuickQuery("select value from setting where name = 'inboundnumber'", $custdb);
		$row[] = QuickQuery("select value from setting where name = 'timezone'", $custdb);
		$row[] = QuickQuery("select value from setting where name = '_managernote'", $custdb);
		$row[] = QuickQuery("select value from setting where name = 'disablerepeat'", $custdb);

		$row[] = QuickQuery("select value from setting where name = '_maxusers'", $custdb);

		$row[] = QuickQuery("SELECT COUNT(*) FROM user where enabled = '1' and login != 'schoolmessenger'", $custdb);
		$row[] = QuickQuery("SELECT COUNT(*) FROM job INNER JOIN user ON(job.userid = user.id)
								WHERE job.status = 'active'", $custdb);


		$data[] = $row;
	}
}

$titles = array("0" => "Customer ID",
				"2" => "Customer Name",
				"url" => "Customer URL (link)",
				"3" => "Toll Free Number",
				"4" => "Timezone",
				"6" => "Status",
				"7" => "Max Users",
				"8" => "Active Users",
				"9" => "Active Jobs",
				"Actions" => "Actions",
				"5" => "NOTES: ");

$formatters = array("url" => "fmt_custurl",
					"6" => "fmt_status",
					"8" => "fmt_users",
					"9" => "fmt_jobcount",
					"Actions" => "fmt_actions");

include_once("nav.inc.php");
?>

Show disabled: <input type="checkbox" onclick="window.location='customers.php?' + (this.checked ? 'showdisabled' : '');" <?= isset($_GET['showdisabled']) ? "checked" : ""?>>

<table border=1>
<?
showTable($data, $titles, $formatters);
?>
</table>

<div>Pink cells indicate that only the system user account has been created</div>
<div>Red cells indicate that the customer has more users than they should</div>
<div>Green cells indicate customers with active jobs</div>
<?
include_once("navbottom.inc.php");


?>