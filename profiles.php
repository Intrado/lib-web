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
	if (customerOwns("access",$deleteid)) {
		$count = QuickQuery("select count(*) from user where accessid='$deleteid' and deleted=0");
		if ($count == 0) {
			QuickUpdate("delete from access where id='$deleteid'");
			QuickUpdate("delete from permission where accessid='$deleteid'");
		} else {
			error("This access profile is being used by $count user account(s). Please reassign users to a different profile and try agian");
		}
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($obj,$name) {
	return '<a href="profile.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
		. '<a href="?delete=' . $obj->id . '" onclick="return confirmDelete();">Delete</a>';
}

function fmt_creator ($obj, $name) {
	return QuickQuery("select login from user where id = $obj->moduserid");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:security";
$TITLE = "Access Profiles";

include_once("nav.inc.php");

$titles = array(	"name" => "Name",
					"description" => "Description",
					"created" => "Created On",
					"modified" => "Modified On",
					"moduserid" => "Last Modified By",
					"Actions" => "Actions"
					);

$data = DBFindMany("Access","from access where customerid=$USER->customerid order by name");
startWindow('Profile List ' . help('Security_ProfileList', NULL, 'blue'), 'padding: 3px;');

button_bar(button('createaccesspro', NULL,"profile.php?id=new") . help('Security_ProfileAdd'));

showObjects($data, $titles, array("Actions" => "fmt_actions", "moduserid" => "fmt_creator"), count($data) > 10);
endWindow();

include_once("navbottom.inc.php");
?>