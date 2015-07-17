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
require_once("obj/Publish.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/PeopleList.obj.php");


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
if (isset($_GET['download'])) {
	$userdetails = Query("
		select u.login, u.accesscode, u.firstname, u.lastname, u.description, u.phone, us.value callerid, u.email, u.aremail, u.enabled, u.lastlogin, u.ldap, u.staffpkey, a.name, a.description profiledescription,
			(select group_concat(jt.name separator ', ') from jobtype jt, userjobtypes ujt where ujt.jobtypeid = jt.id and ujt.userid = u.id and not jt.deleted) as jobtype,
			(select group_concat(s.skey separator ', ') from userassociation ua2 inner join section s on (ua2.sectionid = s.id) where ua2.type = 'section' and ua2.userid = u.id) as section,
			(select group_concat(o.orgkey separator ', ') from userassociation ua3 inner join organization o on (ua3.organizationid = o.id) where ua3.type = 'organization' and ua3.userid = u.id) as organization,
			r.fieldnum, r.op, r.val
		from user u
			left join userassociation ua on (u.id = ua.userid and ua.type = 'rule')
			left join rule r on (ua.ruleid = r.id)
			left join access a on (a.id = u.accessid)
			left join usersetting us on (us.userid = u.id and us.name = 'callerid')
		where not u.deleted and u.login != 'schoolmessenger' and u.accessid is not null");

	// set header
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=users.csv");
	header("Content-type: application/vnd.ms-excel");
	// echo out the data
	echo '"login","accesscode","firstname","lastname","description","phone","callerid","email","aremail","enabled","lastlogin","ldap","staffpkey","profile name","profile description","jobtypes","sections","organizations","fieldnum","op","val"' . "\n";
	while ($row = $userdetails->fetch(PDO::FETCH_ASSOC))
		echo array_to_csv($row) . "\n";
	exit;
}


$usercount = QuickQuery("select count(*) from user where enabled = 1 and login != 'schoolmessenger' and accessid is not null");
$inactiveusercount = QuickQuery("select count(*) from user where enabled = 0 and deleted = 0 and login != 'schoolmessenger' and accessid is not null");

$maxusers = getSystemSetting("_maxusers","unlimited");

$maxreached = $maxusers != "unlimited" && $usercount >= $maxusers;

function is_sm_user($id) {
	return QuickQuery("select count(*) from user where login='schoolmessenger' and id=?",false,array($id));
}

$hasldap = getSystemSetting('_hasldap', '0');

if (isset($_GET['resetpass'])) {
	$id = 0 + $_GET['resetpass'];
	$usr = new User($id);
	// check if ldap user (cannot reset them)
	if ($hasldap && $usr->ldap) {
		notice(_L("Unable to reset the password for %s. This user is authorized by the LDAP server.", escapehtml($usr->login)));
		redirect();
	}
	// else not ldap user, check if they have email for resetting
	if (empty($usr->email)) {
		notice(_L("Unable to reset the password for %s. This user does not have an email address.", escapehtml($usr->login)));
		redirect();
	}
	
	if (is_sm_user($id))
		redirect(); // NOTE: Deliberately not show a notice() about the hidden schoolmessenger user?

	if ($maxreached && !$usr->enabled) {
		redirect("?maxusers");
	}

	$usr->enabled = 1;
	$usr->update();

	forgotPassword($usr->login, $CUSTOMERURL);
	notice(_L("An email has been sent to %s for resetting the password.", escapehtml($usr->login)));
	redirect();
}

if (isset($_GET['delete'])) {
	$deleteid = 0 + $_GET['delete'];
	if (is_sm_user($deleteid))
		redirect(); // NOTE: Deliberately not show a notice() about the hidden schoolmessenger user?

	if (isset($_SESSION['userid']) && $_SESSION['userid'] == $deleteid)
		$_SESSION['userid'] = NULL;

	$usr = new User($deleteid);
	$hasPublishedItems = QuickQuery("
			select 1 from publish where userid = ? and action = 'publish' limit 1",
		false, array($deleteid));
	
	if ($hasPublishedItems) {
		$publishedMessagegroup = DBFindMany("Publish","
				from publish where userid = ? and action = 'publish' and type = 'messagegroup'",
				false, array($deleteid));
		$items = "";
		if (count($publishedMessagegroup)) {
			$names = array();
			foreach($publishedMessagegroup as $id => $publish) {
				$messagegroup = new MessageGroup($publish->messagegroupid);
				$names[] = $messagegroup->name;
			}
			$items .= "<br />" . _L("Message(s): ") . "<b>" . implode(", ", $names) . "</b>";
		}
		$publishedLists = DBFindMany("Publish","
				from publish where userid = ? and action = 'publish' and type = 'list'",
				false, array($deleteid));
		if (count($publishedLists)) {
			$names = array();
			foreach($publishedLists as $id => $publish) {
				$list = new PeopleList($publish->listid);
				$names[] = $list->name;
			}
			$items .= "<br />" . _L("List(s): ") . "<b>" . implode(", ", $names) . "</b>";
		}
		
		notice("<div class='alertmessage'>" . 
				_L("Unable to delete user: %s. User is currently publishing \n", "<b>" . escapehtml($usr->login) . "</b>") . $items .
				"</div>");
		redirect();
	}
	
	QuickQuery('BEGIN');
		QuickUpdate("update user set enabled=0, deleted=1 where id=?", false, array($deleteid));
		QuickUpdate("delete from schedule where id in (select scheduleid from job where status='repeating' and userid=?)", false, array($deleteid));
		QuickUpdate("delete from job where status='repeating' and userid=?", false, array($deleteid));
	QuickQuery('COMMIT');

	notice(_L("%s is now deleted.", escapehtml($usr->login)));
	redirect();
}

if (isset($_GET['disable'])) {
	$id = 0 + $_GET['disable'];
	$usr = new User($id);

	if (is_sm_user($id))
		redirect(); // NOTE: Deliberately not show a notice() about the hidden schoolmessenger user?

	$usr->enabled = 0;
	$usr->update();

	notice(_L("%s is now disabled.", escapehtml($usr->login)));
	redirect();
}

if (isset($_GET['enable'])) {
	$id = 0 + $_GET['enable'];
	$usr = new User($id);

	if (is_sm_user($id))
		redirect(); // NOTE: Deliberately not show a notice() about the hidden schoolmessenger user?
	if($maxreached && !$usr->enabled) {
		redirect("?maxusers");
	}

	$usr->enabled = 1;
	$usr->update();

	notice(_L("%s is now enabled.", escapehtml($usr->login)));
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
	global $USER, $hasldap;

	$id = $account['id'];
	$importid = $account['importid'];
	if (strcmp($account['importupdatemethod'], 'full'))
		$importid = 0; // only fullsync imports manage users, else allow edit full user data
	$login = $account['login'];
	$userldap = $account['ldap'];

	$activeuseranchor = (isset($_SESSION['userid']) && $_SESSION['userid'] == $id) ? '<a name="viewrecent">' : '';

	$links = array();
	$links[] = action_link($importid > 0 ? _L("View") : _L("Edit"),"pencil","user.php?id=$id");
	$links[] = action_link(_L("Login as this user"),"key_go","./?login=$login");
	if (!($hasldap && $userldap)) {
		$links[] = action_link(_L("Reset Password"),"fugue/lock__pencil","", "if (window.confirm('"._L('Send an email reset reminder?')."')) window.location='?resetpass=$id'");
	}
	if ($id != $USER->id)
		$links[] = action_link(_L("Disable"),"user_delete","?disable=$id");

	return $activeuseranchor . action_links($links);
}

function fmt_actions_disabled_account ($account,$name) {
	global $newusers, $hasldap;
	
	$importid = $account['importid'];
	if (strcmp($account['importupdatemethod'], 'full'))
	$importid = 0; // only fullsync imports manage users, else allow edit full user data
	$id = $account['id'];
	$userldap = $account['ldap'];

	$links = array();
	$links[] = action_link($importid > 0 ? _L("View") : _L("Edit"),"pencil","user.php?id=$id");
	$links[] = action_link(_L("Enable"),"user_add","?enable=$id");
	if(isset($newusers[$id]) && !($hasldap && $userldap)) {
		$links[] = action_link(_L("Enable & Reset Password"),"fugue/lock__pencil","?resetpass=$id");
	}

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

	$perpage = 500;

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
		$criteriaSQL = "not enabled and deleted=0 and accessid is not null";
		$formatters["Actions"] = "fmt_actions_disabled_account";
	} else {		
		$criteriaSQL = "enabled and deleted=0 and login != 'schoolmessenger' and accessid is not null";
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
	$data = QuickQueryMultiRow("select SQL_CALC_FOUND_ROWS u.*, a.name as profilename, i.updatemethod as importupdatemethod from user u left join access a on (u.accessid = a.id) left join import i on (i.id = u.importid) where $criteriaSQL $filterSQL order by $orderbySQL limit $limitstart,$perpage", true, false, $args);
	$numUsers = QuickQuery("select FOUND_ROWS()");

	$tooltip = addslashes(_L("Search by First Name, Last Name, Username, or Access Profile. Press ENTER to apply the search word."));
	$html = "<div class='usersearch'><input id='{$containerID}_search' size=20 value=''></div>";
	$html .= ajax_table_show_menu($containerID, $numUsers, $limitstart, $perpage) . ajax_show_table($containerID, $data, $titles, $formatters, $sorting, false, false, 0, false, false);
	$html .= "
		<script type='text/javascript'>
			var searchLabel = '".addslashes(_L('Search Users'))."';
			var searchBox = $('{$containerID}_search');
			searchBox.value = '".addslashes($filterValue)."'.unescapeHTML();
			blankFieldValue('{$containerID}_search', searchLabel);


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

// check if we should display active or inactive users.
if (isset($_GET['display'])) {
	$display = $_GET['display'] ;
} else {
	$display = 'active';
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:users";

if ($display === 'inactive') {
	$TITLE = "Inactive User List";
	$DESCRIPTION = '<a href="users.php">'._L("View Active Users").'</a>';
	$DESCRIPTION .= "&nbsp;&nbsp;Inactive Users: $inactiveusercount";
	
} else {
	$TITLE = "Active User List";
	$DESCRIPTION = '<a href="users.php?display=inactive">'._L("View Inactive Users").'</a>';
	$DESCRIPTION .= "&nbsp;&nbsp;Active Users: $usercount";
	
	if ($maxusers != "unlimited") {
		$DESCRIPTION .= ", Maximum Allowed: $maxusers";
	}
}

$DESCRIPTION .= '&nbsp;&nbsp;'.icon_button(_L('Download User Details CSV'),"report",null,"users.php?download");

include_once("nav.inc.php");

if($display !== 'inactive') {
startWindow('Active Users ' . help('Users_ActiveUsersList'),null, true);
	?>
	<div class="feed_btn_wrap cf">
            <?= icon_button(_L('Add New User'),"add",null,"user.php?id=new") ?>
        </div>
	<?
	echo '<div id="activeUsersContainer" class="cf">';
		echo show_user_table('activeUsersContainer');
	echo '</div>';
endWindow();
}

if($display === 'inactive') {
startWindow('Inactive Users ' . help('Users_InactiveUsersList'),null, true);
	echo '<div id="inactiveUsersContainer">';
		echo show_user_table('inactiveUsersContainer');
	echo '</div>';
endWindow();
}
include_once("navbottom.inc.php");
?>