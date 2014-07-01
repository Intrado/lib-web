<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
require_once("inc/table.inc.php");
require_once("obj/Access.obj.php");
require_once("obj/Permission.obj.php");
require_once("inc/utils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('manageprofile')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	//TODO use csAPI
	$deleteid = DBSafe($_GET['delete']);
	if (isset($_SESSION['accessid']) && $_SESSION['accessid'] == $deleteid)
		$_SESSION['accessid'] = NULL;
	$count = QuickQuery("select count(*) from user where accessid='$deleteid' and deleted=0");
	if ($count == 0) {
		$access = new Access($deleteid);
		Query("BEGIN");
			QuickUpdate("delete from access where id='$deleteid'");
			QuickUpdate("delete from permission where accessid='$deleteid'");
		Query("COMMIT");
		notice(_L("The access profile, %s, is now deleted.", escapehtml($access->name)));
	} else {
		error("This access profile is being used by $count user account(s). Please reassign users to a different profile and try again");
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($obj,$name) {
	return action_links(
		action_link(_L("Edit"),"pencil","profile.php?id=$obj->id"),
		action_link(_L("Delete"),"cross","?delete=$obj->id","return confirmDelete()")
	);
}

function fmt_guardian_actions ($obj,$name) {
	return action_links(
			action_link(_L("Edit"),"pencil","guardianprofile.php?id=$obj->id"),
			action_link(_L("Delete"),"cross","?delete=$obj->id","return confirmDelete()")
	);
}

// get profiles, then sort and order them for display, based on type
$data = $csApi->getProfileList();
$accessCsList = array();
$accessGuardianList = array();
foreach ($data as $access) {
	switch ($access->type) {
		case "cs":
			$accessCsList[$access->name] = $access;
			break;
		case "guardian":
			$accessGuardianList[$access->name] = $access;
			break;
		default:
			//do nothing with 'identity' profiles
	}
}
// alphabetical by name
ksort($accessCsList);
ksort($accessGuardianList);
// PHP 5.4 introduces this nice way to sort case-insensitive, but we use 5.3
//ksort($accessCsList, SORT_NATURAL | SORT_FLAG_CASE);
//ksort($accessGuardianList, SORT_NATURAL | SORT_FLAG_CASE);


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:profiles";
$TITLE = "Access Profiles";

include_once("nav.inc.php");

///////////// Access Profiles
$titles = array(	"name" => _L("Name"),
		"description" => _L("Description"),
		"Actions" => _L("Actions")
);

startWindow(_L('Profile List'), 'padding: 3px;');

?>
	<div class="feed_btn_wrap cf"><?= icon_button(_L('Add New Access Profile'),"add",null,"profile.php?id=new") ?></div>
<?

showObjects($accessCsList, $titles, array("Actions" => "fmt_actions" /*, "moduserid" => "fmt_creator"*/), count($data) > 10);
endWindow();

///////////// Guardian Profiles
$titles = array(	"name" => _L("Name"),
		"description" => _L("Description"),
		"Actions" => _L("Actions")
);

startWindow(_L('Guardian Profile List'), 'padding: 3px;');

?>
	<div class="feed_btn_wrap cf"><?= icon_button(_L('Add New Guardian Profile'),"add",null,"guardianprofile.php?id=new") ?></div>
<?

showObjects($accessGuardianList, $titles, array("Actions" => "fmt_guardian_actions" /*, "moduserid" => "fmt_creator"*/), count($data) > 10);
endWindow();

include_once("navbottom.inc.php");
?>
