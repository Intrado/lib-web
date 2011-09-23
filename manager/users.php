<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("../inc/table.inc.php");
require_once("dbmo/auth/AspAdminUser.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$MANAGERUSER->authorized("edituser"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////'
if (isset($_GET['delete'])) {
	QuickUpdate("update aspadminuser set deleted=1 where id=?",false,array($_GET['delete']));
	notice(_L("Query deleted"));
}



////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////
function fmt_user_actions ($obj, $name) {
	$actionlinks = array();
	$actionlinks[] = action_link("Edit", "pencil","user.php?id=$obj->id");
	$actionlinks[] = action_link("Delete", "cross","users.php?delete=$obj->id","return confirmDelete();");
	return action_links($actionlinks);
}

////////////////////////////////////////////////////////////////////////////////
// Data
////////////////////////////////////////////////////////////////////////////////

$users = DBFindMany("AspAdminUser", "from aspadminuser where deleted=0 order by login");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include_once("nav.inc.php");

startWindow(_L("Users"));

echo "<div style='padding:10px'>" . icon_button("Add User", "add",null,"user.php?id=new") . "</div><br /><hr />";

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


