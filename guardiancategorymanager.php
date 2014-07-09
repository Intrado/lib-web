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

if (isset($_GET["delete"]) && isset($_GET["id"])) {
	// get the requested orgid for deletion
	$deleteid = $_GET["id"] + 0;

	if ($csApi->deleteGuardianCategory($deleteid)) {
		notice(_L("The guardian category is now deleted."));
	} else {
		error(_L("This guardian category is being used. Please un-associate and try again."));
	}
	
	redirect();
}

if (isset($_GET['deleteunassociated'])) {
	// TODO call api
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($obj, $name) {
	return action_links(
		action_link("Edit", "pencil", "guardiancategoryedit.php?id=". $obj->id),
		action_link("Delete", "cross", "guardiancategorymanager.php?id=". $obj->id ."&delete","return confirm('". addslashes(_L('Are you sure you want to delete this guardian category?')) ."');")
			);
}

$titles = array(
	"name" => _L("Guardian Category"),
	"Actions" => _L('Actions'));

$formatters = array(
	"id" => "fmt_actions");

$data = $csApi->getGuardianCategoryList();
// TODO sort by name

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "Guardian Category Manager";

include_once("nav.inc.php");

buttons(icon_button(_L("Done"), "fugue/tick", "document.location='settings.php';"));	
startWindow(_L("Guardian Category Manager"));
?>
	<div class="feed_btn_wrap cf"><?= icon_button(_L('Add New Guardian Category'), "add", null, "guardiancategoryedit.php") . icon_button(_L('Delete Un-associated'),"cross","if(confirm('". addslashes(_L('Are you sure you want to delete all un-associated categories?')) ."')) document.location='guardiancategorymanager.php?&deleteunassociated'") ?></div>
<?

if (count($data)) {
	showObjects($data, $titles, array("Actions" => "fmt_actions"), count($data) > 10);
} else {
	?><div><img src='img/largeicons/information.jpg' /><?=escapehtml(_L("No guardian categories defined"))?></div><?
}

endWindow();
buttons();

include_once("navbottom.inc.php");
?>