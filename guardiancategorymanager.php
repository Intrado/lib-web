<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/formatters.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata')) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////

$deleteorgids = array();
if (isset($_GET["delete"]) && isset($_GET["id"])) {
	// get the requested orgid for deletion
	$deleteorgid = $_GET["id"] + 0;
	// TODO call delete api
	redirect();
}

if (isset($_GET['deleteunassociated'])) {
	// TODO call api
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($row, $index) {
	global $start;
	return action_links(
		action_link("Edit", "pencil", "guardiancategoryedit.php?orgid=". $row[$index]),
		action_link("Delete", "cross", "guardiancategorymanager.php?id=". $row[$index] ."&delete&pagestart=$start","return confirm('". addslashes(_L('Are you sure you want to delete this guardian category?')) ."');")
			);
}

$titles = array(
	"orgkey" => _L("Guardian Category"),
	"id" => 'Action');
$formatters = array(
	"id" => "fmt_actions");

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

$data = QuickQueryMultiRow("select SQL_CALC_FOUND_ROWS id, orgkey from organization where not deleted order by orgkey, id limit $start, $limit", true);

$total = QuickQuery("select FOUND_ROWS()");

// TAI has a single root organization we want to display as the special case
$rootOrgId = QuickQuery("select id from organization where (select 1 from setting where name like '_dbtaiversion') and parentorganizationid is null and not deleted");
if ($rootOrgId) {
	for ($i = 0; $i < count($data); $i++) {
		if ($data[$i]['id'] == $rootOrgId) {
			$data[$i]['orgkey'] = $data[$i]['orgkey'] . " (Root)";
			break;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "Guardian Category Manager";

include_once("nav.inc.php");

buttons(icon_button(_L("Done"), "fugue/tick", "document.location='settings.php';"));	
startWindow(_L("Guardian Category Manager"));
?>
	<div class="feed_btn_wrap cf"><?= icon_button(_L('Add New Guardian Category'), "add", null, "guardiancategoryedit.php?id=new") . icon_button(_L('Delete Un-associated'),"cross","if(confirm('". addslashes(_L('Are you sure you want to delete all un-associated organizations?')) ."')) document.location='organizationdatamanager.php?&deleteunassociated'") ?></div>
<?

// if there are any organizations
if (count($data)) {
	showPageMenu($total, $start, $limit);
	?><table width="100%" cellpadding="3" cellspacing="1" class="list"><?
	showTable($data, $titles, $formatters);
	?></table><?
	showPageMenu($total, $start, $limit);
} else {
	?><div><img src='img/largeicons/information.jpg' /><?=escapehtml(_L("No guardian categories defined"))?></div><?
}

endWindow();
buttons();

include_once("navbottom.inc.php");
?>