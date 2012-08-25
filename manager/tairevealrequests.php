<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
include_once("../inc/html.inc.php");

////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////

if ($_GET["delete"]) {
	if ($_GET["customerid"] && $_GET["threadid"]) {
		loadManagerConnectionData();
		$custdb = getPooledCustomerConnection($_GET["customerid"],true);
		
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
function fmt_actions($row, $index) {
	global $MANAGERUSER;

	$links = array();
	$links[] = action_link(_L("View Requested Thread"),"magnifier","taithread.php?customerid={$row["customerid"]}&threadid={$row["parentthreadid"]}");
	$links[] = action_link(_L("View Thread"),"magnifier","taithread.php?customerid={$row["customerid"]}&threadid={$row["threadid"]}");
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
	$custdb = getPooledCustomerConnection($cid,true);
	
	$query = "SELECT ? as customerid,m.threadid,t.originatinguserid, t.parentthreadid, m.body FROM `tai_message` m inner join `tai_thread` t on (t.id = m.threadid) WHERE exists (select * from tai_userthread ut where t.id=ut.threadid and ut.userid=1 and ut.isdeleted=0) and m.recipientuserid=1 and t.threadtype='identityreveal' group by m.threadid";
	$customerthreads = QuickQueryMultiRow($query,true,$custdb,array($cid));
	$thread = array_merge($thread,$customerthreads);
	
	echo ".";
	if (++$count % 20 == 0)
	echo "<wbr></wbr>";
	ob_flush();
	flush();
}

$titles = array(
	"customerid" => "#Customer ID",
	"url" => "#Custoemr URL",
	"threadid" => "ThreadId",
	"originatinguserid" => "Userid",
	"parentthreadid" => "Requested Thread",
	"body" => "Request Information",
	"actions" => "Actions");
$formatters = array(
	"url" => "fmt_custurl",
	"actions" => "fmt_actions"
);

echo '<table id="taiinbox" class="list sortable">';

showTable($thread,$titles,$formatters);

echo '</table>';

endWindow();

?>
<script>
function deleterequest(linkid,customerid,threadid) {
	new Ajax.Request("tairevealrequests.php?delete=true&customerid=" + customerid + "&threadid=" + threadid, {
		method:'get',
		onSuccess: function (result) {
			//$(linkid).up('tr').setStyle({backgroundColor: '#900'});
			Effect.Fade($(linkid).up('tr'), { duration: 2.0 });
		}
	});
}
</script>
<?
include_once("navbottom.inc.php");
?>
