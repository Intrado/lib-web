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

// Show javascript alerts as necessary, redirected from user.php
if (isset($_GET['maxusers']))
	error("You already have the maximum number of allowed users");
if (isset($_GET['noprofiles']))
	error("You have no Access Profiles defined! Go to the Admin->Profiles tab and create one");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

/*CSDELETEMARKER_START*/
$usercount = QuickQuery("select count(*) from user where enabled = 1 and login != 'schoolmessenger'");
$maxusers = getSystemSetting("_maxusers","unlimited");

$maxreached = $maxusers != "unlimited" && $usercount >= $maxusers;

function is_sm_user($id) {
	return QuickQuery("select count(*) from user where login='schoolmessenger' and id=?",false,array($id));
}
/*CSDELETEMARKER_END*/


if (isset($_GET['resetpass'])) {
	$id = 0 + $_GET['resetpass'];
	$usr = new User($id);
	
	/*CSDELETEMARKER_START*/
	if (is_sm_user($id))
		redirect(); // NOTE: Deliberately not show a notice() about the hidden schoolmessenger user?
	
	if ($maxreached && !$usr->enabled) {
		redirect("?maxusers");
	}
	/*CSDELETEMARKER_END*/

	$usr->enabled = 1;
	$usr->update();

	forgotPassword($usr->login, $CUSTOMERURL);
	notice(_L("An email has been sent to %s for resetting the password", $usr->login));
	redirect();
}

if (isset($_GET['delete'])) {
	$deleteid = 0 + $_GET['delete'];
	/*CSDELETEMARKER_START*/
	if (is_sm_user($deleteid))
		redirect(); // NOTE: Deliberately not show a notice() about the hidden schoolmessenger user?
	/*CSDELETEMARKER_END*/

	if (isset($_SESSION['userid']) && $_SESSION['userid'] == $deleteid)
		$_SESSION['userid'] = NULL;

	$usr = new User($deleteid);
	
	QuickQuery('BEGIN');
		QuickUpdate("update user set enabled=0, deleted=1 where id=?", false, array($deleteid));
		QuickUpdate("delete from schedule where id in (select scheduleid from job where status='repeating' and userid=?)", false, array($deleteid));
		QuickUpdate("delete from job where status='repeating' and userid=?", false, array($deleteid));
	QuickQuery('COMMIT');

	notice(_L("%s is now deleted", $user->login));
	redirect();
}

if (isset($_GET['disable'])) {
	$id = 0 + $_GET['disable'];
	$usr = new User($id);
	
	/*CSDELETEMARKER_START*/
	if (is_sm_user($id))
		redirect(); // NOTE: Deliberately not show a notice() about the hidden schoolmessenger user?
	/*CSDELETEMARKER_END*/
		
	$usr->enabled = 0;
	$usr->update();
	
	notice(_L("%s is now disabled", $user->login));
	redirect();
}

if (isset($_GET['enable'])) {
	$id = 0 + $_GET['enable'];
	$usr = new User($id);
	
	/*CSDELETEMARKER_START*/
	if (is_sm_user($id))
		redirect(); // NOTE: Deliberately not show a notice() about the hidden schoolmessenger user?
	if($maxreached && !$usr->enabled) {
		redirect("?maxusers");
	}
	/*CSDELETEMARKER_END*/
	
	$usr->enabled = 1;
	$usr->update();
	
	notice(_L("%s is now enabled", $usr->login))
	redirect();
}

//preload names for all of the access profiles
$accessprofiles = QuickQueryList("select id,name from access",true);


//preload new disabled users with an email
$newusers = QuickQueryList("select id,1 from user where not enabled and deleted=0 and password='new' and email != ''", true);

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////
function fmt_actions_enabled_account ($account,$name) {
	global $USER;

	$id = $account['id'];
	$importid = $account['importid'];
	$login = $account['login'];
	
	$activeuseranchor = (isset($_SESSION['userid']) && $_SESSION['userid'] == $id) ? '<a name="viewrecent">' : '';

	$links = array();
	$links[] = action_link($importid > 0 ? _L("View") : _L("Edit"),"pencil","user.php?id=$id");
	$links[] = action_link(_L("Login as this user"),"key_go","./?login=$login");
	$links[] = action_link(_L("Reset Password"),"fugue/lock__pencil","", "if (window.confirm('"._L('Send an email reset reminder?')."')) window.location='?resetpass=$id'");
	if ($id != $USER->id)
		$links[] = action_link(_L("Disable"),"user_delete","?disable=$id");
	
	return $activeuseranchor . action_links($links);
}

function fmt_actions_disabled_account ($account,$name) {
	global $newusers;
	$editviewaction = "Edit";
	$importid = $account['importid'];
	if ($importid > 0) $editviewaction = "View";
	$id = $account['id'];

	$links = array();
	$links[] = action_link($importid > 0 ? _L("View") : _L("Edit"),"pencil","user.php?id=$id");
	$links[] = action_link(_L("Enable"),"user_add","?enable=$id");
	if(isset($newusers[$id]))
		$links[] = action_link(_L("Enable & Reset Password"),"fugue/lock__pencil","?resetpass=$id");
	
	$links[] = action_link(_L("Delete"),"cross","?delete=$id","return confirmDelete()");
	
	return action_links($links);
}

////////////////////////////////////////////////////////////////////////////////
// AJAX
////////////////////////////////////////////////////////////////////////////////
if (!isset($_SESSION['ajaxtablepagestart']) || !isset($_GET['ajax']))
	$_SESSION['ajaxtablepagestart'] = array();
if (isset($_GET['containerID']) && isset($_GET['ajax'])) {		
	if (isset($_GET['start']) && $_GET['ajax'] == 'page')
		$_SESSION['ajaxtablepagestart'][$_GET['containerID']] = $_GET['start'] + 0;
	if ($_GET['ajax'] == 'filter')
		$_SESSION['ajaxtablepagestart'][$_GET['containerID']] = 0;
		
	header('Content-Type: application/json');
		$ajaxdata = array('html' => show_user_table($_GET['containerID']));
	exit(json_encode($ajaxdata));
}

function show_user_table($containerID) {
	global $IS_COMMSUITE;
	
	$perpage = 20;
	
	$titles = array(
		"firstname" => "First Name",
		"lastname" => "Last Name",
		"login" => "Username",
		"description" => "Description",
		"profilename" => "Profile",
		"lastlogin" => "Last Login",
		"Actions" => "Actions"
	);
	$formatters = array(
		"lastlogin" => "fmt_date"
	);
	$sorting = array(
		"firstname" => "firstname",
		"lastname" => "lastname",
		"description" => "description",
		"login" => "login",
		"lastlogin" => "lastlogin",
		"profilename" => "profilename"
	);
	
	// ACCOUNT ENABLED/DISABLED, COMMSUITE/NOT COMMSUITE
	if ($containerID == 'inactiveUsersContainer') {
		$criteriaSQL = "not enabled and deleted=0";
		$formatters["Actions"] = "fmt_actions_disabled_account";
	} else {
		if($IS_COMMSUITE)
			$criteriaSQL = "enabled and deleted=0";
/*CSDELETEMARKER_START*/
		else
			$criteriaSQL = "enabled and deleted=0 and login != 'schoolmessenger'";
/*CSDELETEMARKER_END*/
		$formatters["Actions"] = "fmt_actions_enabled_account";
	}
	
	// ORDER BY
	$orderbySQL = ajax_table_get_orderby($containerID, $sorting);
	if (empty($orderbySQL))
		$orderbySQL = "lastname, firstname";

	// FILTER
	if (!isset($_SESSION["{$containerID}_filter"]))
		$_SESSION["{$containerID}_filter"] = '';
	if (isset($_GET['ajax']) && $_GET['ajax'] == 'filter' && isset($_GET['filter']))
		$_SESSION["{$containerID}_filter"] = $_GET['filter'];

	$filterSQL = !empty($_SESSION["{$containerID}_filter"]) ? "and (concat(upper(firstname),upper(lastname),upper(login),upper(a.name)) like ?)" : '';
	if (!empty($filterSQL)) {
		$filterValue = escapehtml($_SESSION["{$containerID}_filter"]);
		$args[] = '%' . strtoupper($_SESSION["{$containerID}_filter"]) . '%';
	} else {
		$filterValue = '';
		$args = false;
	}

	// PAGING
	$limitstart = isset($_SESSION['ajaxtablepagestart'][$containerID]) ? $_SESSION['ajaxtablepagestart'][$containerID] : 0;
	
	// RUN QUERY
	$data = QuickQueryMultiRow("select SQL_CALC_FOUND_ROWS u.*,a.name as profilename from user u left join access a on (u.accessid = a.id) where $criteriaSQL $filterSQL order by $orderbySQL limit $limitstart,$perpage", true, false, $args);
	$numUsers = QuickQuery("select FOUND_ROWS()");
	
	$tooltip = addslashes(_L("Search by First Name, Last Name, Username, or Access Profile. Press ENTER to apply the search word."));
	$html = "<div style='float:left; padding-top:5px'><input id='{$containerID}_search' size=20 value=''></div>";
	$html .= ajax_table_show_menu($containerID, $numUsers, $limitstart, $perpage) . ajax_show_table($containerID, $data, $titles, $formatters, $sorting);
	$html .= "
		<script type='text/javascript'>
			var searchLabel = '".addslashes(_L('Search'))."';
			var searchBox = $('{$containerID}_search');
			searchBox.value = '".addslashes($filterValue)."'.unescapeHTML();
			blankFieldValue('{$containerID}_search', searchLabel);
			searchBox.focus();
			searchBox.blur();
			
			Event.observe(searchBox, 'keypress', function(event) {
				if (Event.KEY_RETURN == event.keyCode)
					ajax_table_update('$containerID', '?ajax=filter&filter=' + encodeURIComponent(event.element().value));
			});
			
			new Tip(searchBox, '$tooltip', {
					style: 'protogrey',
					stem: 'bottomLeft',
					hook: { target: 'topLeft', tip: 'bottomLeft' },
					offset: { x: 0, y: 0 },
					fixed: true,
					hideOthers: true
			});
		</script>
	";
	return $html;
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

startWindow('Active Users ' . help('Users_ActiveUsersList'),null, true);
	button_bar(button('Add New User', NULL,"user.php?id=new") . help('Users_UserAdd'));
	
	echo '<div id="activeUsersContainer">';
		echo show_user_table('activeUsersContainer');
	echo '</div>';
endWindow();

print '<br>';

startWindow('Inactive Users ' . help('Users_InactiveUsersList'),null, true);
	echo '<div id="inactiveUsersContainer">';
		echo show_user_table('inactiveUsersContainer');
	echo '</div>';
endWindow();

include_once("navbottom.inc.php");
?>