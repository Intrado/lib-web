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
//index 2 is display name
	if (!isset($_GET['showdisabled']))
		$url = $row[2] . " (<a href=\"customerlink.php?id=" . $row[0] ."\" >" . $row[1] . "</a>)";
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
	$actions = '<a href="customeredit.php?id=' . $row[0] .'" title="Edit"><img src="img/s-edit.png" border=0></a>&nbsp;';
	$actions .= '<a href="userlist.php?customer=' . $row[0] . '" title="Users"><img src="img/s-users.png" border=0></a>&nbsp;';
	$actions .= '<a href="customerimports.php?customer=' . $row[0] . '" title="Imports"><img src="img/s-imports.png" border=0></a>&nbsp;';
	$actions .= '<a href="customeractivejobs.php?customer=' . $row[0] . '" title="Jobs"><img src="img/s-jobs.png" border=0></a>&nbsp;';
	$actions .= '<a href="customerpriorities.php?id=' . $row[0] . '" title="Priorities"><img src="img/s-priorities.png" border=0></a>&nbsp;';
	$actions .= '<a href="customerdms.php?cid=' . $row[0] . '" title="DMs"><img src="img/s-rdms.png" border=0></a>';

	return $actions;
}

function fmt_jobcount($row, $index){
	if($row[$index] > 0){
		return "<div style='background-color: #ccffcc;'>" . $row[$index] . "<div>";
	} else {
		return $row[$index];
	}
}

function fmt_hasportal($row, $index){
	if($row[$index])
		return "Yes";
	else
		return "No";
}

////////////////////////////////////////////////////////////////////////////////
// data handling
////////////////////////////////////////////////////////////////////////////////

$res = Query("select id, dbhost, dbusername, dbpassword from shard order by id");
$shardinfo = array();
while($row = DBGetRow($res)){
	$shardinfo[$row[0]] = array($row[1], $row[2], $row[3]);
}

$customerquery = Query("select id, shardid, urlcomponent, oemid, nsid from customer $customersql order by shardid, id");
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
		$row[0] = $cust[0];
		$row[1] = $cust[2];
		$row[2] = getCustomerSystemSetting('displayname', false, true, $custdb);
		$row[3] = getCustomerSystemSetting('_productname', false, true, $custdb);
		$row[4] = getCustomerSystemSetting('timezone', false, true, $custdb);
		$row[5] = getCustomerSystemSetting('_managernote', false, true, $custdb);
		$row[6] = getCustomerSystemSetting('disablerepeat', false, true, $custdb);

		$row[7] = getCustomerSystemSetting('_maxusers', false, true, $custdb);

		$row[8] = QuickQuery("SELECT COUNT(*) FROM user where enabled = '1' and login != 'schoolmessenger'", $custdb);
		$row[9] = QuickQuery("SELECT COUNT(*) FROM job INNER JOIN user ON(job.userid = user.id)
								WHERE job.status = 'active'", $custdb);
		$customerfeatures = array();
		if(getCustomerSystemSetting('_hasportal', false, true, $custdb))
			$customerfeatures[] = "Portal";
		if(getCustomerSystemSetting('_hassms', false, true, $custdb))
			$customerfeatures[] = "SMS";
		$row[10] = implode(", ", $customerfeatures);
		$row[11] = ucfirst(getCustomerSystemSetting('_dmmethod', "", true, $custdb));
		$row[12] = $cust[3];
		$row[13] = $cust[4];
		$data[] = $row;
	}
}

$titles = array("0" => "ID",
				"url" => "Name",
				"3" => "Product Name",
				"4" => "Timezone",
				"6" => "Status",
				"11" => "DM Method",
				"10" => "Features",
				"7" => "Max Users",
				"8" => "Users",
				"9" => "Jobs",
				"Actions" => "Actions",
				"5" => "NOTES: ",
				"OEM ID",
				"NetSuite ID");

$formatters = array("url" => "fmt_custurl",
					"6" => "fmt_status",
					"8" => "fmt_users",
					"9" => "fmt_jobcount",
					"Actions" => "fmt_actions");

include_once("nav.inc.php");
?>

Show disabled: <input type="checkbox" onclick="window.location='customers.php?' + (this.checked ? 'showdisabled' : '');" <?= isset($_GET['showdisabled']) ? "checked" : ""?>>

<table class=list>
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