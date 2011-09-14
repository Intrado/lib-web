<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

if (!$MANAGERUSER->authorized("imports"))
	exit("Not Authorized");

if(isset($_GET["delete"])) {
	if (isset($_GET["customerid"]) && isset($_GET["ruleid"])) {
//		error_log($_GET["customerid"]);
		list($shardid,$dbhost,$dbusername,$dbpassword) = QuickQueryRow("select s.id, s.dbhost, s.dbusername, s.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id=?",false,false,array($_GET["customerid"]));
		$dsn = 'mysql:dbname=aspshard;host='.$dbhost;
		$sharddb = new PDO($dsn, $dbusername, $dbpassword);
		$sharddb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		
		Query("use aspshard", $sharddb);

		QuickUpdate("delete from importalert where customerid=? and importalertruleid=?",$sharddb,array($_GET["customerid"],$_GET["ruleid"]));

		notice("Deleted Alert");
		redirect();
	}
}




////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$shardresult = Query("select id, dbhost, dbusername, dbpassword from shard order by id");

// Get import alerts from all shards
$data = array();
while ($shardinfo = DBGetRow($shardresult)) {
	list($shardid,$dbhost,$dbusername,$dbpassword) = $shardinfo;
	$dsn = 'mysql:dbname=aspshard;host='.$dbhost;
	$sharddb = new PDO($dsn, $dbusername, $dbpassword);
	$sharddb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	
	Query("use aspshard", $sharddb);
	$query = "select customerid,importalertruleid,name,operation,testvalue,actualvalue,alerttime,notified from importalert";
	$result = Query($query,$sharddb);
	
	
	while ($row = DBGetRow($result,true)) {
		$row["urlcomponent"] = QuickQuery("select urlcomponent from customer where id=?",false,array($row["customerid"]));
		$data[] = $row;
	}		
}

$titles = array(
		"urlcomponent" => "#Cust Name",
		"alert" => "Alert",
		"alerttime" => "#Alert Time",
		"notified" => "#Notified Time",
		"actions" => "Actions"
);

$formatters = array(
	"urlcomponent" => "fmt_custurl",
	"alert" => "fmt_alert",
	"actions" => "fmt_actions");

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////
function fmt_custurl($row, $index){
	global $MANAGERUSER;
	//index 1 is url
	//index 2 is display name
	if ($MANAGERUSER->authorized("logincustomer"))
		return "<a href='customerlink.php?id=" . $row["customerid"] ."' target=\"_blank\">" . escapehtml($row["urlcomponent"]) . "</a>";
	else
		return $row["urlcomponent"];
}

function fmt_alert($row, $index){
	switch($row["name"]) {
		case "daysold":
			$str = "Import is delayed. Expected import within {$row["testvalue"]} days. Triggered at {$row["actualvalue"]} days";
			break;
		case "size":
			$str = "File size is too " . ($row["operation"]=="gt"?"big":"small") .
					 ". Expected " . ($row["operation"]=="gt"?"less":"more") . " than {$row["testvalue"]} bytes. Actual size: {$row["actualvalue"]} bytes";
			break;
		case "importtime":
			$midnight_today = mktime(0,0,0);
			$testvalue = date("g:i a",$midnight_today + $row["testvalue"]);
			$actualvalue = date("g:i a",$midnight_today + $row["actualvalue"]);			
			$str = "Imported too " . ($row["operation"]=="gt"?"late":"early") . 
			". Expected import " . ($row["operation"]=="gt"?"before":"after") . " $testvalue. Imported at: $actualvalue";
			break;
	}
	return $str;
}
function fmt_actions($row, $index){
	$str = "";
	$actions = array();
	$actions[] = action_link("View", "magnifier","customerimports.php?customer={$row["customerid"]}");
	$actions[] = action_link("Delete", "cross","importalerts.php?delete&customerid={$row["customerid"]}&ruleid={$row["importalertruleid"]}","return confirmDelete();");
	return action_links($actions);
}

/////////////////////////////
// Display
/////////////////////////////

include("nav.inc.php");
startWindow(_L('Import Alerts'));
?>
<table class="list sortable" id="customer_imports_table">
<?
showTable($data, $titles, $formatters);
?>
</table>
<?
endWindow();
date_default_timezone_set("US/Pacific");
include("navbottom.inc.php");
?>
