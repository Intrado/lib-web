<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('manageaccount')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if ($_SESSION['userid'] == $deleteid)
		$_SESSION['userid'] = NULL;
	if (customerOwns("user",$deleteid)) {
		QuickUpdate("update user set enabled=0, deleted=1 where id='$deleteid' and customerid=$USER->customerid");
		QuickUpdate("delete from job where status='repeating' and userid='$deleteid' and customerid=$USER->customerid");
	}
	redirect();
}

if (isset($_GET['disable'])) {
	$id = DBSafe($_GET['disable']);
	if (customerOwns("user",$id))
		QuickUpdate("update user set enabled = 0 where id = '$id' and customerid=$USER->customerid");
	redirect();
}

if (isset($_GET['enable'])) {
	$id = DBSafe($_GET['enable']);
	if (customerOwns("user",$id))
		QuickUpdate("update user set enabled = 1 where id = '$id' and customerid=$USER->customerid");
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions_en ($obj,$name) {
	return '<a href="user.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
		. ($obj->enabled ? '<a href="./?login=' . $obj->login . '">Login&nbsp;as&nbsp;this&nbsp;user</a>&nbsp;|&nbsp;' : NULL)
		. '<a href="?disable=' . $obj->id . '">Disable</a>&nbsp;';
}

function fmt_actions_dis ($obj,$name) {
	return '<a href="user.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
		. '<a href="?enable=' . $obj->id . '">Enable</a>&nbsp;|&nbsp;'
		. '<a href="?delete=' . $obj->id . '" onclick="return confirmDelete();">Delete</a>';
}

/*
	Callback to format the access profile name for a user
*/
function fmt_acc_profile ($obj,$name) {
	$profile = QuickQuery("select name from access where access.id = $obj->accessid");
	return $profile;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:users";
$TITLE = "User List";

include_once("nav.inc.php");

$titles = array(	"firstname" => "#First Name",
					"lastname" => "#Last Name",
					"login" => "#Username",
					"AccessProfile" => "#Security Profile",
					"lastlogin" => "Last Login",
					"Actions" => "Actions"
					);

startWindow('Active Users ' . help('Users_ActiveUsersList', NULL, "blue"), 'padding: 3px;');

button_bar(button('adduser', NULL,"user.php?id=new") . help('Users_UserAdd'));

$data = DBFindMany("User","from user where customerid=$USER->customerid and enabled and deleted=0 order by lastname, firstname");
showObjects($data, $titles, array("Actions" => "fmt_actions_en", 'AccessProfile' => 'fmt_acc_profile', "lastlogin" => "fmt_obj_date"), count($data) > 10, true);
endWindow();

print '<br>';

startWindow('Inactive Users ' . help('Users_InactiveUsersList', NULL, "blue"), 'padding: 3px;');
$data = DBFindMany("User","from user where customerid=$USER->customerid and not enabled and deleted=0 order by lastname, firstname");
showObjects($data, $titles, array('AccessProfile' => 'fmt_acc_profile', "Actions" => "fmt_actions_dis", "lastlogin" => "fmt_obj_date"), count($data) > 10, true);
endWindow();


include_once("navbottom.inc.php");
?>