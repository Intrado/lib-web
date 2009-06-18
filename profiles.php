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
	$deleteid = DBSafe($_GET['delete']);
	if ($_SESSION['accessid'] == $deleteid)
		$_SESSION['accessid'] = NULL;
	$count = QuickQuery("select count(*) from user where accessid='$deleteid' and deleted=0");
	if ($count == 0) {
		QuickUpdate("delete from access where id='$deleteid'");
		QuickUpdate("delete from permission where accessid='$deleteid'");
	} else {
		error("This access profile is being used by $count user account(s). Please reassign users to a different profile and try agian");
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($obj,$name) {
	return '<a href="profile.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
		. '<a href="?delete=' . $obj->id . '" onclick="return confirmDelete();">Delete</a>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:profiles";
$TITLE = "Access Profiles";

include_once("nav.inc.php");

$titles = array(	"name" => "Name",
					"description" => "Description",
					"Actions" => "Actions"
					);

if($IS_COMMSUITE)
	$data = DBFindMany("Access","from access order by name");
/*CSDELETEMARKER_START*/
else
	$data = DBFindMany("Access","from access where name != 'SchoolMessenger Admin' order by name");
/*CSDELETEMARKER_END*/

startWindow('Profile List ' . help('Security_ProfileList'), 'padding: 3px;');

button_bar(button('Create New Access Profile', NULL,"profile.php?id=new") . help('Security_ProfileAdd'));

showObjects($data, $titles, array("Actions" => "fmt_actions" /*, "moduserid" => "fmt_creator"*/), count($data) > 10);
endWindow();

include_once("navbottom.inc.php");
?>