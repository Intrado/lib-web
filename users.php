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

function is_sm_user($id) {
	return QuickQuery("select count(*) from user where login='schoolmessenger' and id=?",false,array($id));
}
/*CSDELETEMARKER_END*/


if (isset($_GET['resetpass'])) {
	$maxreached = false;
	$id = 0 + $_GET['enable'];
	
	/*CSDELETEMARKER_START*/
	if (is_sm_user($id))
		redirect();
	
	if(($maxusers != "unlimited") && $maxusers <= $usercount){
		print '<script language="javascript">window.alert(\'You already have the maximum amount of users.\');window.location="users.php";</script>';
		$maxreached = true;
	}
	/*CSDELETEMARKER_END*/

	if(!$maxreached){
		QuickUpdate("update user set enabled = 1 where id = ?", false, array($id));

		$usr = new User($id);
		global $CUSTOMERURL;
		forgotPassword($usr->login, $CUSTOMERURL);
		redirect(); // TODO this takes a few seconds...
	}
}

if (isset($_GET['delete'])) {
	$deleteid = 0 + $_GET['delete'];
	/*CSDELETEMARKER_START*/
	if (is_sm_user($deleteid))
		redirect();
	/*CSDELETEMARKER_END*/

	if (isset($_SESSION['userid']) && $_SESSION['userid'] == $deleteid)
		$_SESSION['userid'] = NULL;

	QuickUpdate("update user set enabled=0, deleted=1 where id=?", false, array($deleteid));
	QuickUpdate("delete from schedule where id in (select scheduleid from job where status='repeating' and userid=?)", false, array($deleteid));
	QuickUpdate("delete from job where status='repeating' and userid=?", false, array($deleteid));

	redirect();
}

if (isset($_GET['disable'])) {
	$id = 0 + $_GET['disable'];
	/*CSDELETEMARKER_START*/
	if (is_sm_user($id))
		redirect();
	/*CSDELETEMARKER_END*/
	QuickUpdate("update user set enabled = 0 where id = ?", false, array($id));
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
		$id = 0 + $_GET['enable'];
		QuickUpdate("update user set enabled = 1 where id = ?", false, array($id));
		redirect();
	}
}


//preload names for all of the access profiles
$accessprofiles = QuickQueryList("select id,name from access",true);


//preload new disabled users with an email
$newusers = QuickQueryList("select id,1 from user where not enabled and deleted=0 and password='new' and email != ''", true);

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions_en ($obj,$name) {
	global $USER;

	$activeuseranchor = (isset($_SESSION['userid']) && $_SESSION['userid'] == $obj->id) ? '<a name="viewrecent">' : '';

	$links = array();
	$links[] = action_link($obj->importid > 0 ? _L("View") : _L("Edit"),"pencil","user.php?id=$obj->id");
	$links[] = action_link(_L("Login as this user"),"key_go","./?login=$obj->login");
	$links[] = action_link(_L("Reset Password"),"fugue/lock__pencil","?resetpass=1&enable=$obj->id");
	if ($obj->id != $USER->id)
		$links[] = action_link(_L("Disable"),"user_delete","?disable=$obj->id");
	
	return $activeuseranchor . action_links($links);
}

function fmt_actions_dis ($obj,$name) {
	global $newusers;
	$editviewaction = "Edit";
	if ($obj->importid > 0) $editviewaction = "View";


	$links = array();
	$links[] = action_link($obj->importid > 0 ? _L("View") : _L("Edit"),"pencil","user.php?id=$obj->id");
	$links[] = action_link(_L("Enable"),"user_add","?enable=$obj->id");
	if(isset($newusers[$obj->id]))
		$links[] = action_link(_L("Enable & Reset Password"),"fugue/lock__pencil","?enable=$obj->id&resetpass=1");
	
	$links[] = action_link(_L("Delete"),"cross","?delete=$obj->id","return confirmDelete()");
	
	return action_links($links);
}

/*
	Callback to format the access profile name for a user
*/
function fmt_acc_profile ($obj,$name) {
	global $accessprofiles;
	return escapehtml($accessprofiles[$obj->accessid]);
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
					"AccessProfile" => "#Profile",
					"lastlogin" => "Last Login",
					"Actions" => "Actions"
					);



startWindow('Active Users ' . help('Users_ActiveUsersList'),null, true);

button_bar(button('Add New User', NULL,"user.php?id=new") . help('Users_UserAdd'));


if($IS_COMMSUITE)
	$data = DBFindMany("User","from user where enabled and deleted=0 order by lastname, firstname");
/*CSDELETEMARKER_START*/
else
	$data = DBFindMany("User","from user where enabled and deleted=0 and login != 'schoolmessenger' order by lastname, firstname");
/*CSDELETEMARKER_END*/

showObjects($data, $titles, array("Actions" => "fmt_actions_en", 'AccessProfile' => 'fmt_acc_profile', "lastlogin" => "fmt_obj_date"), false, true);
endWindow();

print '<br>';

startWindow('Inactive Users ' . help('Users_InactiveUsersList'),null, true);
$data = DBFindMany("User","from user where not enabled and deleted=0 order by lastname, firstname");
showObjects($data, $titles, array('AccessProfile' => 'fmt_acc_profile', "Actions" => "fmt_actions_dis", "lastlogin" => "fmt_obj_date"), count($data) > 10, true);
endWindow();


include_once("navbottom.inc.php");
?>
