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

/*CSDELETEMARKER_START*/
$usercount = QuickQuery("select count(*) from user where enabled = 1 and login != 'schoolmessenger'");
$maxusers = getSystemSetting("_maxusers","unlimited");
/*CSDELETEMARKER_END*/

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if (isset($_SESSION['userid']) && $_SESSION['userid'] == $deleteid)
		$_SESSION['userid'] = NULL;

	QuickUpdate("update user set enabled=0, deleted=1 where id='$deleteid'");
	QuickUpdate("delete from job where status='repeating' and userid='$deleteid'");

	redirect();
}

if (isset($_GET['disable'])) {
	$id = DBSafe($_GET['disable']);
	QuickUpdate("update user set enabled = 0 where id = '$id'");
	redirect();
}

if (isset($_GET['enable'])) {
	$maxreached = false;

	/*CSDELETEMARKER_START*/
	if(($maxusers != "unlimited") && $maxusers <= $usercount){
		print '<script language="javascript">window.alert(\'You already have the maximum amount of users.\');window.location="users.php";</script>';
		$maxreached = true;
	}
	/*CSDELETEMARKER_END*/

	if(!$maxreached){
		$id = DBSafe($_GET['enable']);
		QuickUpdate("update user set enabled = 1 where id = '$id'");
		redirect();
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions_en ($obj,$name) {
	global $USER;
	return '<a href="user.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
		. ($obj->enabled ? '<a href="./?login=' . $obj->login . '">Login&nbsp;as&nbsp;this&nbsp;user</a>' : NULL)
		. ($obj->id == $USER->id ? "" : '&nbsp;|&nbsp;<a href="?disable=' . $obj->id . '">Disable</a>&nbsp;');
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

/*CSDELETEMARKER_START*/
$DESCRIPTION = "Active Users: $usercount";
if($maxusers != "unlimited")
	$DESCRIPTION .= ", Maximum Allowed: $maxusers";
/*CSDELETEMARKER_END*/

include_once("nav.inc.php");

$titles = array(	"firstname" => "#First Name",
					"lastname" => "#Last Name",
					"login" => "#Username",
					"description" => "#Description",
					"AccessProfile" => "#Security Profile",
					"lastlogin" => "Last Login",
					"Actions" => "Actions"
					);



startWindow('Active Users ' . help('Users_ActiveUsersList'));

button_bar(button('Add New User', NULL,"user.php?id=new") . help('Users_UserAdd'));


if($IS_COMMSUITE)
	$data = DBFindMany("User","from user where enabled and deleted=0 order by lastname, firstname");
/*CSDELETEMARKER_START*/
else
	$data = DBFindMany("User","from user where enabled and deleted=0 and login != 'schoolmessenger' order by lastname, firstname");
/*CSDELETEMARKER_END*/

showObjects($data, $titles, array("Actions" => "fmt_actions_en", 'AccessProfile' => 'fmt_acc_profile', "lastlogin" => "fmt_obj_date"), count($data) > 10, true);
endWindow();

print '<br>';

startWindow('Inactive Users ' . help('Users_InactiveUsersList'));
$data = DBFindMany("User","from user where not enabled and deleted=0 order by lastname, firstname");
showObjects($data, $titles, array('AccessProfile' => 'fmt_acc_profile', "Actions" => "fmt_actions_dis", "lastlogin" => "fmt_obj_date"), count($data) > 10, true);
endWindow();


include_once("navbottom.inc.php");
?>