<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
include_once("../inc/html.inc.php");

////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////


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
	$links[] = action_link(_L("View"),"pencil","taithread.php?customerid={$row["customerid"]}&threadid={$row["threadid"]}");
	$links[] = action_link(_L("Delete"),"cross","taiinbox.php?delete=true&customerid={$row["customerid"]}&threadid={$row["threadid"]}");
	
	return action_links($links);
}

$TITLE = "Talk About It Inbox";
$PAGE = "tai:inbox";

include_once("nav.inc.php");
startWindow(_L('Inbox'));


loadManagerConnectionData();

$thread = array();
$count = 0;

$query = "select customerid from customerproduct p where p.product = 'tai' and enabled";
$taicustomers = QuickQueryList($query);


foreach ($taicustomers as $cid) {
	$custdb = getPooledCustomerConnection($cid,true);
	
	$query = "SELECT ? as customerid,threadid,body FROM `tai_message` m WHERE exists (select * from tai_usermessage um where um.userid=1 and um.isdeleted=0) and `recipientuserid`=1 group by threadid";
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
	"threadid" => "#Thread Id",
	"body" => "Last Message",
	"actions" => "Actions");
$formatters = array(
	"url" => "fmt_custurl",
	"actions" => "fmt_actions"
);

echo '<table id="taiinbox" class="list sortable">';

showTable($thread,$titles,$formatters);

echo '</table>';

endWindow();

include_once("navbottom.inc.php");
?>
