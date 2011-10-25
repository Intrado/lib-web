<?
include_once("common.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/formatters.inc.php");

if (!$MANAGERUSER->authorized("lockedusers"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET["cid"]) && isset($_GET["enable"])) {
	$userinfo = QuickQueryRow("select customerid, login, status from loginattempt where status != 'enabled' and customerid=? and login=?",true,false,array($_GET["cid"],$_GET["enable"]));
	$haserror = false;
	switch($userinfo["status"]) {
		case "lockout":
			QuickUpdate("update loginattempt set status = 'enabled', attempts = 0 where login=?",false,array($_GET["enable"]));
			notice("User " .  escapehtml($_GET["enable"]) . " is now unlocked");
			break;
		case "disabled":
			$customerinfo = QuickQueryRow("select c.id as customerid, s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where c.id=?",true,false,array($userinfo["customerid"]));
			if ($customerinfo) {
				$cust_db = DBConnect($customerinfo["dbhost"], $customerinfo["dbusername"], $customerinfo["dbpassword"],"c_" . $userinfo["customerid"]);
				QuickUpdate("update loginattempt set status = 'enabled', attempts=0 where login=?",false,array($_GET["enable"]));
				QuickUpdate("update user set enabled = 1 where login=?", $cust_db,array($_GET["enable"]));
				notice("User " .  escapehtml($_GET["enable"]) . " is now enabled");
			} else {
				$haserror = true;
			}
			break;
		default:
			notice("Unable to perform request");
	}
	
	if (!$haserror) {
		notice("Unable to perform request");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$lockedusers = QuickQueryMultiRow("select l.customerid,c.urlcomponent, l.login, l.ipaddress, l.attempts, l.lastattempt, l.status from loginattempt l left join customer c on (c.id = l.customerid) where l.status != 'enabled'");

$titles = array("0" => "Customer ID",
				"1" => "Customer URL",
				"2" => "Login",
				"3" => "IP Address",
				"4" => "Attempts",
				"5" => "Last Attempt",
				"6" => "Status",
				"Actions" => "Actions");

$formatters = array("1" => "fmt_customerUrl",
					"4" => "fmt_date",
					"5" => "fmt_locked_status",
					"Actions" => "lockeduser_actions");



////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

//index 5 is status
function lockeduser_actions($row, $index){
	$actionlinks = array();
	switch($row[6]) {
		case "disabled":
			$actionlinks[] = action_link("Enable", "key_go","lockedusers.php?cid=" . $row[0] . "&enable=" . urlencode($row[2]));
			break;
		case "lockout":
			$actionlinks[] = action_link("Unlock", "lock_open","lockedusers.php?cid=" . $row[0] . "&enable=" . urlencode($row[2]));
			break;
	}
	return action_links($actionlinks);
}

function fmt_locked_status($row,$index){
	if($row[$index] == 'lockout'){
		return "Temporarily Locked";
	} else if($row[$index] == 'disabled'){
		return "Disabled";
	} else {
		return ucfirst($row[$index]);
	}
}
function fmt_customerUrl($row, $index){
	$url = "";
	if($row[1])
		$url = "<a href=\"customerlink.php?id=" . $row[0] ."\" target=\"_blank\">" . $row[1] . "</a>";
	return $url;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include("nav.inc.php");

?>
<table class=list>
<?
showTable($lockedusers, $titles, $formatters);
?>
</table>
<?
include("navbottom.inc.php");
?>