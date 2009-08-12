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
		redirect();
	
	if ($maxreached && !$usr->enabled) {		
		redirect("?maxusers");
	}
	/*CSDELETEMARKER_END*/

	$usr->enabled = 1;
	$usr->update();

	forgotPassword($usr->login, $CUSTOMERURL);
	redirect();
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
	$usr = new User($id);
	
	/*CSDELETEMARKER_START*/
	if (is_sm_user($id))
		redirect();
	/*CSDELETEMARKER_END*/
		
	$usr->enabled = 0;
	$usr->update();
	redirect();
}

if (isset($_GET['enable'])) {
	$id = 0 + $_GET['enable'];
	$usr = new User($id);
	
	/*CSDELETEMARKER_START*/
	if (is_sm_user($id))
		redirect();
	if($maxreached && !$usr->enabled) {		
		redirect("?maxusers");
	}
	/*CSDELETEMARKER_END*/
	
	$usr->enabled = 1;
	$usr->update();
	redirect();
}

if (isset($_GET['maxusers'])) {
	error("You already have the maximum number of allowed users");
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

/*
	Callback to format the access profile name for a user
*/
function fmt_profile_name ($account, $name) {
	global $accessprofiles;
	return escapehtml($accessprofiles[$account['accessid']]);
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
		"AccessProfile" => "Profile",
		"lastlogin" => "Last Login",
		"Actions" => "Actions"
	);
	$formatters = array(
		'AccessProfile' => 'fmt_profile_name',
		"lastlogin" => "fmt_date"
	);
	$sorting = array(
		"firstname" => "firstname",
		"lastname" => "lastname",
		"login" => "login",
		"lastlogin" => "lastlogin"
	);
	
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
	
	$orderbySQL = ajax_table_get_orderby($containerID, $sorting);
	if (empty($orderbySQL))
		$orderbySQL = "lastname, firstname";
	
	$filterSQL = get_filter_sql($containerID);
	if (!empty($filterSQL)) {
		$filterValue = $_SESSION["{$containerID}_filter"];
		
		// Append the same string 3 times, for [lastname, firstname, login].
		for ($i = 0; $i < 3; $i++) {
			$args[] = '%' . $_SESSION["{$containerID}_filter"] . '%';
		}
	} else {
		$filterValue = '';
		$args = false;
	}
	
	$numUsers = QuickQuery("select count(*) from user where $criteriaSQL $filterSQL", false, $args);
	
	$limitstart = isset($_SESSION['ajaxtablepagestart'][$containerID]) ? $_SESSION['ajaxtablepagestart'][$containerID] : 0;
	if ($limitstart >= $numUsers)
		$limitstart = $numUsers-$perpage;
	if ($limitstart < 0)
		$limitstart = 0;
	$data = DBFindMany("User","from user where $criteriaSQL $filterSQL ORDER BY $orderbySQL LIMIT $limitstart,$perpage", false, $args);
	foreach ($data as $i => $account) {
		$data[$i] = (array)$account;
	}
	
	$tooltip = addslashes(_L("Search by First Name, Last Name, Username, or Access Profile. Press ENTER to apply the search word."));
	$html = "<div style='float:right; padding:5px; padding-bottom:0; margin-right:5px;'><input id='{$containerID}_search' size=20 value='$filterValue'></div>";
	$html .= ajax_table_show_menu($containerID, $numUsers, $limitstart, $perpage) . ajax_show_table($containerID, $data, $titles, $formatters, $sorting);
	$html .= "
		<script type='text/javascript'>
			Event.observe('{$containerID}_search', 'keypress', function(event) {
				if (Event.KEY_RETURN == event.keyCode)
					ajax_table_update('$containerID', '?ajax=filter&filter=' + event.element().value);
			});
			
			new Tip('{$containerID}_search', '$tooltip', {
					style: 'protogrey',
					stem: 'bottomRight',
					hook: { target: 'topLeft', tip: 'bottomRight' },
					offset: { x: 10, y: 0 },
					fixed: true,
					hideOthers: true
			});
		</script>
	";
	return $html;
}

function get_filter_sql($containerID) {
	global $accessprofiles;
	
	if (!isset($_SESSION["{$containerID}_filter"]))
		$_SESSION["{$containerID}_filter"] = '';
	if (isset($_GET['ajax']) && $_GET['ajax'] == 'filter' && isset($_GET['filter']))
		$_SESSION["{$containerID}_filter"] = $_GET['filter'];
	
	$filter = $_SESSION["{$containerID}_filter"];
	
	if (!empty($filter)) {
		// PROFILE
		$filteredProfiles = array();
		foreach ($accessprofiles as $id => $name) {
			if (strpos($name, $filter) !== false)
				$filteredProfiles[] = $id;
		}
		$profileSQL = empty($filteredProfiles) ? "" : ('or accessid in (' . implode(',', $filteredProfiles) . ')');
		
		return "and (firstname like ? or lastname like ? or login like ? $profileSQL)";
	} else {
		return '';
	}
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