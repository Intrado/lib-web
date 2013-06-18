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
	if (isset($_GET["customerid"]) && isset($_GET["smsnumber"])) {
		loadManagerConnectionData();
		
		QuickUpdate("use talkaboutit");
		
		$query = "delete from smscustomer where customerid=? and smsnumber=?";
		QuickUpdate($query,false,array($_GET["customerid"],$_GET["smsnumber"]));	
		QuickUpdate("use authserver");
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
	$links = array();
	$links[] = action_link(_L("Delete"),"cross",false,"if(confirmDelete()) { deleterequest(this.id,'{$row["customerid"]}','{$row["smsnumber"]}');}return false;");
	return action_links($links);
}

$TITLE = _L("SMS Numbers");
$PAGE = "tai:smsnumbers";

include_once("nav.inc.php");
startWindow($TITLE);


?>

Find the TAI CUSTOMER that has been associated with this SMS number:<br/><br/>

<form id="search" autocomplete="off" action="taismsnumbers.php" method="get">
	<input id="searchvalue" name="search" type="text" size="30" value="<?=isset($_GET["search"]) ? escapehtml($_GET["search"]) : ""?>"/><button type="submit">Search</button> Search Customer ID or SMS Number
	<div id="searchpreview">
	</div>
</form>

<?

loadManagerConnectionData();


if (isset($_GET["search"])) {
	QuickUpdate("use talkaboutit");

	$sqlsearch = "1";
	$safesearch =  DBSafe(trim($_GET["search"]));
	if ($safesearch == "") {
		$sqlsearch = "0"; // Expect no customers.
	} else
		$sqlsearch = "(customerid='$safesearch' or smsnumber like '%$safesearch%')";
	$query = "select customerid, smsnumber from smscustomer where $sqlsearch";
	$numbers = QuickQueryMultiRow($query,true);

	QuickUpdate("use authserver");

	if (count($numbers)) {
		$titles = array(
				"customerid" => "#Customer ID",
				"url" => "#Customer URL",
				"smsnumber" => "SMS Number",
				"actions" => "Actions");
		$formatters = array(
				"url" => "fmt_custurl",
				"actions" => "fmt_actions"
		);
		
		show_column_selector('tairequests', $titles);
		
		echo '<table id="tairequests" class="list sortable">';
		showTable($numbers,$titles,$formatters);
		echo '</table>';
	} else {
		echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No SMS numbers found with search '%s'",$safesearch) . "</div>";
	}
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("Use Search to find SMS Numbers") . "</div>";
}
endWindow();


?>
<script>
function deleterequest(linkid,customerid,smsnumber) {
	new Ajax.Request("taismsnumbers.php?delete=true&customerid=" + customerid + "&smsnumber=" + smsnumber, {
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
