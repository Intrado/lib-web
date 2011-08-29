<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("../inc/table.inc.php");
require_once("AspAdminQuery.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("runqueries") || !$MANAGERUSER->authorized("editqueries"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////'
if (isset($_GET['delete'])) {
	// get the lists of queries (single and multiple customer)
	if ($MANAGERUSER->authorized("editqueries") && $MANAGERUSER->authQuery($_GET['delete'])) {
		QuickUpdate("delete from aspadminquery where id=?",false,array($_GET['delete']));
		notice(_L("Query deleted"));
	} else {
		notice(_L("You do not have permission delete this query"));
	}
}


$cid = false;
if (isset($_GET['cid']))
	$cid = $_GET['cid'] + 0;

////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////
function fmt_customerquery_actions ($obj, $name) {
	global $MANAGERUSER,$cid;
	$actionlinks = array();
	if ($MANAGERUSER->authorized("editqueries") && !$cid) {
		$actionlinks[] = action_link("Edit", "application_edit","queryedit.php?id=$obj->id");
		$actionlinks[] = action_link("Delete", "application_delete","querylist.php?delete=$obj->id","return confirmDelete();");
	}
	if ($MANAGERUSER->authorized("runqueries") && $cid) {
		$actionlinks[] = action_link("Run", "application_go","queryrun.php?id={$obj->id}&cid=$cid");
	}
	return action_links($actionlinks);
}


function fmt_query_actions ($obj, $name) {
	global $MANAGERUSER,$cid;
	$actionlinks = array();
	if ($MANAGERUSER->authorized("editqueries") && !$cid) {
		$actionlinks[] = action_link("Edit", "application_edit","queryedit.php?id=$obj->id");
		$actionlinks[] = action_link("Delete", "application_delete","querylist.php?delete=$obj->id","return confirmDelete();");
	}
	if ($MANAGERUSER->authorized("runqueries")) {
		$actionlinks[] = action_link("Run", "application_go","queryrun.php?id={$obj->id}");
	}
	return action_links($actionlinks);
}

////////////////////////////////////////////////////////////////////////////////
// Data
////////////////////////////////////////////////////////////////////////////////

$allCustomerManagerQueries = false;
$singleCustomerManagerQueries = false;

// get the lists of queries (single and multiple customer)
if ($MANAGERUSER->queries == "unrestricted") {
	$allCustomerManagerQueries = DBFindMany("AspAdminQuery", "from aspadminquery where options not like '%singlecustomer%' order by name");
	$singleCustomerManagerQueries = DBFindMany("AspAdminQuery", "from aspadminquery where options like '%singlecustomer%' order by name");
} else if ($MANAGERUSER->queries) {
	$allCustomerManagerQueries = DBFindMany("AspAdminQuery", "from aspadminquery where id in ($MANAGERUSER->queries) and options not like '%singlecustomer%' order by name");
	$singleCustomerManagerQueries = DBFindMany("AspAdminQuery", "from aspadminquery where id in ($MANAGERUSER->queries) and options like '%singlecustomer%' order by name");
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include_once("nav.inc.php");

if ($cid) {
	$custurl = QuickQuery("select c.urlcomponent from customer c where c.id = ?", false, array($cid));
	startWindow("Customer Queries: $custurl");
	
} else {
	startWindow("Customer Queries");
	
	if ($MANAGERUSER->authorized("editqueries")) 
		echo "<div style='padding:10px'>" . icon_button("Add Query", "add",false,"queryedit.php?id=new") . "</div><br />";
}
?>
<h2>Single Customer Queries</h2>
<?
if ($singleCustomerManagerQueries) {
	$titles = array(
			"name" => "#Name",
			"notes" => "#Notes",
			"Actions" => "Actions"
	);
	$formatters = array("Actions" => "fmt_customerquery_actions");
	
	showObjects($singleCustomerManagerQueries, $titles,$formatters);
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No Queries Found") . "</div>";
}
?>

<h2>All Customer Queries</h2>
<?
if ($allCustomerManagerQueries) {
	$titles = array(
			"name" => "#Name",
			"notes" => "#Notes",
			"Actions" => "Actions"
	);
	$formatters = array("Actions" => "fmt_query_actions");
	showObjects($allCustomerManagerQueries, $titles,$formatters);
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No Queries Found") . "</div>";
}
endWindow();

include_once("navbottom.inc.php");


