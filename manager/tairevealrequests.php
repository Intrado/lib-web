<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
include_once("../inc/html.inc.php");
include_once("../obj/User.obj.php");


////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET["delete"])) {
	if ($_GET["customerid"] && $_GET["threadid"]) {
		loadManagerConnectionData();
		$custdb = getPooledCustomerConnection($_GET["customerid"]);
		
		QuickUpdate("UPDATE tai_userthread SET isdeleted=1 WHERE userid=1 and threadid=?",$custdb,array($_GET["threadid"]));
	}
	
	exit();
}

function fmt_custurl($row, $index){
	global $MANAGERUSER, $CUSTOMERINFO;
	if ($MANAGERUSER->authorized("logincustomer"))
		return "<a href='customerlink.php?id=" . $row["customerid"] ."' target=\"_blank\">" . escapehtml($CUSTOMERINFO[$row["customerid"]]['urlcomponent']) . "</a>";
	else
		return escapehtml(escapehtml($CUSTOMERINFO[$row["customerid"]]['urlcomponent']));
}

function fmt_timestamp($row, $index) {
	return date("Y-m-d G:i:s",$row[$index]);;
}

function fmt_userdisplayname($row, $index) {
	$user = $row["originatinguser"];
	return "{$user->firstname} {$user->lastname} ({$user->login})";
}
function fmt_userinfo($row, $index) {
	$user = $row["originatinguser"];
	return "Phone:&nbsp;{$user->phone}<br/>Email:&nbsp;" . $user->email;
}

function fmt_actions($row, $index) {
	global $MANAGERUSER;

	$links = array();
	$links[] = action_link(_L("View Requested Thread"),"magnifier","taithread.php?customerid={$row["customerid"]}&threadid={$row["parentthreadid"]}");
	$links[] = action_link(_L("View Request"),"magnifier","taithread.php?customerid={$row["customerid"]}&threadid={$row["threadid"]}");
	$links[] = action_link(_L("Delete"),"cross",false,"if(confirmDelete()) { deleterequest(this.id,'{$row["customerid"]}','{$row["threadid"]}');}return false;");
	
	return action_links($links);
}

$TITLE = _L("Identity Reveal Requests");
$PAGE = "tai:requests";

include_once("nav.inc.php");
startWindow($TITLE);


loadManagerConnectionData();

$thread = array();
$count = 0;

$query = "select customerid from customerproduct p where p.product = 'tai' and enabled";
$taicustomers = QuickQueryList($query);


foreach ($taicustomers as $cid) {
	$custdb = getPooledCustomerConnection($cid);
	
	$query = "SELECT ? as customerid,m.threadid,t.originatinguserid, t.parentthreadid, m.body,t.modifiedtimestamp FROM `tai_message` m inner join `tai_thread` t on (t.id = m.threadid) WHERE exists (select * from tai_userthread ut where t.id=ut.threadid and ut.userid=1 and ut.isdeleted=0) and m.recipientuserid=1 and t.threadtype='identityreveal' group by m.threadid";
	$customerthreads = QuickQueryMultiRow($query,true,$custdb,array($cid));
	
	// set global to customer db, restore after this section
	global $_dbcon;
	$savedbcon = $_dbcon;
	$_dbcon = $custdb;
	
	$users = array();
	for($i=0;$i<count($customerthreads);$i++) {
	//foreach($customerthreads as $customerthread) {
		if (!isset($users[$customerthreads[$i]["originatinguserid"]]))
			$users[$customerthreads[$i]["originatinguserid"]] = new User($customerthreads[$i]["originatinguserid"]);
		
		$customerthreads[$i]["originatinguser"] = $users[$customerthreads[$i]["originatinguserid"]];
		
	}
	// restore global db connection
	$_dbcon = $savedbcon;
	
	$thread = array_merge($thread,$customerthreads);
	
	echo ".";
	if (++$count % 20 == 0)
	echo "<wbr></wbr>";
	ob_flush();
	flush();
}

$titles = array(
	"customerid" => "#Customer ID",
	"url" => "#Customer URL",
	"threadid" => "@Thread",
	"parentthreadid" => "@Requested Thread",
	"originatinguserdisplay" => "From",
	"originatinguserinfo" => "@From Info",
	"body" => "Request Information",
	"modifiedtimestamp" => "Modified",
	"actions" => "Actions");
$formatters = array(
	"url" => "fmt_custurl",
	"originatinguserdisplay" => "fmt_userdisplayname",
	"originatinguserinfo" => "fmt_userinfo",
	"modifiedtimestamp" => "fmt_timestamp",
	"actions" => "fmt_actions"
);

show_column_selector('tairequests', $titles);

echo '<table id="tairequests" class="list sortable">';

showTable($thread,$titles,$formatters);

echo '</table>';

endWindow();

?>
<script>
function deleterequest(linkid,customerid,threadid) {
	new Ajax.Request("tairevealrequests.php?delete=true&customerid=" + customerid + "&threadid=" + threadid, {
		method:'get',
		onSuccess: function (result) {
			Effect.Fade($(linkid).up('tr'), { duration: 2.0 });
		}
	});
}
</script>
<?
include_once("navbottom.inc.php");
?>