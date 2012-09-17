<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
include_once("../inc/html.inc.php");

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

function fmt_actions($row, $index) {
	global $MANAGERUSER;
	
	$links = array();
	$links[] = action_link(_L("View"),"magnifier","taithread.php?customerid={$row["customerid"]}&threadid={$row["threadid"]}");
	$links[] = action_link(_L("Delete"),"cross",false,"if(confirmDelete()) { deleterequest(this.id,'{$row["customerid"]}','{$row["threadid"]}');}return false;");
	
	return action_links($links);
}

function threadcompare($a, $b) {
	if ($a["modifiedtimestamp"] == $b["modifiedtimestamp"]) {
		return 0;
	}
	return ($a["modifiedtimestamp"] > $b["modifiedtimestamp"]) ? -1 : 1;
}

$TITLE = "Talk About It Inbox";
$PAGE = "tai:inbox";

include_once("nav.inc.php");
startWindow(_L('Inbox'));


loadManagerConnectionData();

$threads = array();
$count = 0;

$query = "select c.id from customer c inner join customerproduct p on (p.customerid = c.id) where c.enabled and p.product = 'tai' and p.enabled";
$taicustomers = QuickQueryList($query);


foreach ($taicustomers as $cid) {
	$custdb = getPooledCustomerConnection($cid);
	
	$query = "SELECT ? as customerid,m.threadid,m.body,t.modifiedtimestamp FROM `tai_message` m inner join `tai_thread` t on (t.id = m.threadid) WHERE exists (select * from tai_userthread ut where t.id=ut.threadid and ut.userid=1 and ut.isdeleted=0) and m.recipientuserid=1 and t.threadtype='comment' group by threadid";
	$customerthreads = QuickQueryMultiRow($query,true,$custdb,array($cid));
	$threads = array_merge($threads,$customerthreads);
	
	echo ".";
	if (++$count % 20 == 0)
	echo "<wbr></wbr>";
	ob_flush();
	flush();
}
uasort($threads, 'threadcompare');


$titles = array(
	"customerid" => "#Customer ID",
	"url" => "#Customer URL",
	"threadid" => "@Thread",
	"body" => "Last Message",
	"modifiedtimestamp" => "#Modified",
	"actions" => "Actions");
$formatters = array(
	"url" => "fmt_custurl",
	"modifiedtimestamp" => "fmt_timestamp",
	"actions" => "fmt_actions"
);

show_column_selector('taiinbox', $titles);

echo '<table id="taiinbox" class="list sortable">';

showTable($threads,$titles,$formatters);

echo '</table>';

endWindow();
?>
<script>
function deleterequest(linkid,customerid,threadid) {
	new Ajax.Request("taiinbox.php?delete=true&customerid=" + customerid + "&threadid=" + threadid, {
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
