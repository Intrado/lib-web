<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("../inc/table.inc.php");
require_once("dbmo/authserver/AspAdminUser.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("superuser"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////'
if (isset($_GET['delete'])) {
	QuickUpdate("update aspadminuser set deleted=1 where id=?",false,array($_GET['delete']));
	notice(_L("User Deleted"));
}
if (isset($_GET['undelete'])) {
	QuickUpdate("update aspadminuser set deleted=0 where id=?",false,array($_GET['undelete']));
	notice(_L("User Undeleted"));
}
$showdeleted = isset($_GET["deleted"]);

////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////
function fmt_user_actions ($obj, $name) {
	global $showdeleted;
	$actionlinks = array();
	$actionlinks[] = action_link("Edit", "pencil","user.php?id=$obj->id");
	
	if (!$showdeleted)
		$actionlinks[] = action_link("Delete", "cross","users.php?delete=$obj->id","return confirmDelete();");
	else
		$actionlinks[] = action_link("Undelete", "accept","users.php?undelete=$obj->id");
	
	return action_links($actionlinks);
}

////////////////////////////////////////////////////////////////////////////////
// Data
////////////////////////////////////////////////////////////////////////////////

$users = DBFindMany("AspAdminUser", "from aspadminuser where deleted=? order by login",false,array($showdeleted?1:0));

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include_once("nav.inc.php");

startWindow(_L("Users"));

echo "<div style='padding:10px'>" . icon_button("Add User", "add",null,"user.php?id=new") . "</div><br /><hr />";
echo $showdeleted?"<a href='users.php'>Show Active Users</a>":"<a href='users.php?deleted'>Show Deleted Users</a>";
if ($users) {
	$titles = array(
			"firstname" => "#First Name",
			"lastname" => "#Last Name",
			"email" => "#Email",
			"login" => "#Login",
			"Actions" => "Actions"
	);
	$formatters = array("Actions" => "fmt_user_actions");
	showObjects($users, $titles,$formatters);
} else {
	echo "<div class='destlabel'><img src='img/largeicons/information.jpg' align='middle'> " . _L("No Users Found") . "</div>";
}
endWindow();

include_once("navbottom.inc.php");


