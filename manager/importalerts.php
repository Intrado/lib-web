<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("inc/importalert.inc.php");
require_once("../inc/memcache.inc.php");

if (!$MANAGERUSER->authorized("imports"))
	exit("Not Authorized");


$displaysetting = "unacknowledged";

if (isset($_REQUEST["view"]) && 
	in_array($_REQUEST["view"],array("acknowledged","unacknowledged","unconfigured","dismissed"))) {
	$displaysetting = $_REQUEST["view"];
}

if(isset($_REQUEST["acknowledge"])) {
	header('Content-Type: application/json');
	
	if (isset($_REQUEST["customerid"]) && isset($_REQUEST["importalertruleid"])) {
		list($shardid,$dbhost,$dbusername,$dbpassword) = QuickQueryRow("select s.id, s.dbhost, s.dbusername, s.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id=?",false,false,array($_REQUEST["customerid"]));
		$dsn = 'mysql:dbname=aspshard;host='.$dbhost;
		$sharddb = new PDO($dsn, $dbusername, $dbpassword);
		$sharddb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

		Query("use aspshard", $sharddb);
		
		QuickUpdate("update importalert set acknowledged=? where customerid=? and importalertruleid=?",$sharddb,array(($_REQUEST["acknowledge"]=="true"?1:0),$_REQUEST["customerid"],$_REQUEST["importalertruleid"]));

		echo "true";
	} else {
		echo "false";
	}
	exit();
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

function customerTimezone($customerid){
	$query = "select c.id, s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id=?";
	$custinfo = QuickQueryRow($query,true,false,array($customerid));
	$custdb = DBConnect($custinfo["dbhost"], $custinfo["dbusername"], $custinfo["dbpassword"], "c_{$custinfo["id"]}");
	if (!$custdb) {
		exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_{$custinfo["id"]}");
	}
	return getCustomerSystemSetting("timezone","",true,$custdb);
}

$shardresult = Query("select id, dbhost, dbusername, dbpassword from shard order by id");

// Get import alerts from all shards
$data = array();
while ($shardinfo = DBGetRow($shardresult)) {
	list($shardid,$dbhost,$dbusername,$dbpassword) = $shardinfo;
	$dsn = 'mysql:dbname=aspshard;host='.$dbhost;
	$sharddb = new PDO($dsn, $dbusername, $dbpassword);
	$sharddb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	
	Query("use aspshard", $sharddb);
	$query = "select customerid,importalertruleid,importname,name,operation,testvalue,actualvalue,alerttime,notified,notes,acknowledged from importalert where type='manager' and acknowledged=?";
	
	if ($displaysetting == "unconfigured" || $displaysetting == "dismissed")
		$query .= " and name='unconfigured'";
	else
		$query .= " and name!='unconfigured'";
	
	$result = Query($query,$sharddb,array($displaysetting == "acknowledged" || $displaysetting == "dismissed"?1:0));
	
	
	while ($row = DBGetRow($result,true)) {
		$row["urlcomponent"] = QuickQuery("select urlcomponent from customer where id=?",false,array($row["customerid"]));
		$data[] = $row;
	}		
}

$titles = array(
		"checkmark" => "Acknowledged",
		"urlcomponent" => "#Cust Name",
		"importname" => "Import",
		"alert" => "Alert",
		"alerttime" => "#Alert Time",
		"timezone" => "Timezone",
		"notified" => "#Notified Time",
		"notes" => "Alert Notes",
		"actions" => "Actions"
);

if ($displaysetting == "unconfigured") {
	unset($titles["importname"]);
	unset($titles["notified"]);
	unset($titles["timezone"]);
}

$formatters = array(
	"checkmark" => "fmt_check",
	"urlcomponent" => "fmt_custurl",
	"alert" => "fmt_alert",
	"timezone" => "fmt_timezone",
	"actions" => "fmt_actions");

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_check($row, $index){
	return '<input id="' . $row["customerid"] . ':' . $row["importalertruleid"] . '" class="importmulticheck" name="hide" type="checkbox" value="true" ' . ($row["acknowledged"]?'checked':'') .  ' onclick="acknowledgeAlert(this.id,this.checked)"/>';
}

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
	return formatAlert($row["name"],$row["operation"],$row["testvalue"],$row["actualvalue"]);
}

function fmt_timezone($row, $index){
	// 14400 seconds = 4 hours
	return gen2cache(14400,null,null,'customerTimezone',$row["customerid"]);
}

function fmt_actions($row, $index){
	$str = "";
	$actions = array();
	$actions[] = action_link("Edit Notes", "pencil","editalert.php?customerid={$row["customerid"]}&importalertruleid={$row["importalertruleid"]}");
	$actions[] = action_link("View", "magnifier","customerimports.php?customer={$row["customerid"]}");
	return action_links($actions);
}

/////////////////////////////
// Display
/////////////////////////////
$TITLE = _L("Import Alerts");
$PAGE = "commsuite:importalerts";

include("nav.inc.php");


?>
<form id="viewoptions" method="GET" action="importalerts.php">

<table>
<tr>
	<td>
	Displaying: 
	</td>
	<td>
	<select name="view" id='view' onchange="this.form.submit();">
		<option value='acknowledged' <?=($displaysetting=='acknowledged')?"selected":""?>>Acknowledged</option>
		<option value='unacknowledged' <?=($displaysetting=='unacknowledged')?"selected":""?>>Non Acknowledged</option>
		<option value='unconfigured' <?=($displaysetting=='unconfigured')?"selected":""?>>Unconfigured</option>
		<option value='dismissed' <?=($displaysetting=='dismissed')?"selected":""?>>Unconfigured (Acknowledged)</option>
	</select>
	</td>
</tr>
</table>
</form>
<?


startWindow("Import Alerts");

if (count($data)) {
?>
<table class="list sortable" id="customer_imports_table">
<?
showTable($data, $titles, $formatters);
?>
</table>
<script type="text/javascript">



function acknowledgeAlert(id, acknowledged) {
	var ids = id.split(':');
	$(id).checked = !acknowledged;
	new Ajax.Request('importalerts.php',{method:'post',parameters:{"acknowledge":acknowledged,"customerid":ids[0],"importalertruleid": ids[1]},
		onSuccess: function(response){
			var result = response.responseJSON;
			if (result == true) {
				$(id).checked = acknowledged;
			}
		}
	});
}
</script>
<?

} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No Records Found") . "<div>";
}
endWindow();
date_default_timezone_set("US/Pacific");
include("navbottom.inc.php");
?>
