<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Organization.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata')) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
if (isset($_GET['orgid']) && isset($_GET['delete'])) {
	// check that this orgid is valid
	$org = DBFind("Organization", "from organization where id = ? and not deleted", false, array($_GET['orgid']));
	if ($org) {
		// make sure this org doesn't have any associated rules
		$orgAssociated = QuickQuery(
			"select count(*)
			from organization o
				inner join listentry le on
					(o.id = le.organizationid)
				inner join userassociation ua on
					(o.id = ua.organizationid)
			where o.id = ?
				and not o.deleted", false, array($_GET['orgid']));
		if ($orgAssociated) {
			// cannot delete associated orgs
			error_log("org associated");
		} else {
			// delete the org
			$org->deleted = 1;
			$org->update();
		}
	}
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

function fmt_actions ($row, $index) {
	global $start;
	return action_links(
		action_link("Delete", "cross", "organizationdatamanager.php?orgid=". $row[$index] ."&delete&pagestart=$start","return confirm('". addslashes(_L('Are you sure you want to delete this organization?')) ."');"),
		action_link("Merge", "fugue/arrow_join", "organizationmerge.php?orgid=". $row[$index]));
}

$titles = array(
	"orgkey" => 'Organization',
	"id" => 'Action');
$formatters = array(
	"id" => "fmt_actions");

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

$data = QuickQueryMultiRow("select SQL_CALC_FOUND_ROWS id, orgkey from organization where not deleted order by orgkey, id limit $start, $limit", true);

$total = QuickQuery("select FOUND_ROWS()");


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "Organization Manager";

include_once("nav.inc.php");

buttons(
	icon_button("Done", "fugue/tick", "document.location='settings.php';"),
	icon_button("New", "fugue/plus", "document.location='organizationnew.php';"));
startWindow(_L("Organizations"));

// if there are any messages to subscribe to
if (count($data)) {
	showPageMenu($total, $start, $limit);
	?><table width="100%" cellpadding="3" cellspacing="1" class="list"><?
	showTable($data, $titles, $formatters);
	?></table><?
	showPageMenu($total, $start, $limit);
} else {
	?><div><img src='img/largeicons/information.jpg' /><?=escapehtml(_L("No published messages at this time"))?></div><?
}
endWindow();
buttons();
include_once("navbottom.inc.php");
?>