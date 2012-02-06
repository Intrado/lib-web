<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/securityhelper.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata'))
	redirect('unauthorized.php');

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
if (isset($_GET['orgid'])) {
	$orgid = $_GET['orgid'];
	$orgkey = QuickQuery("select orgkey from organization where id = ?", false, array($orgid));
} else {
	redirect("organizationdatamanager.php");
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

function fmt_login ($row, $index) {
	if ($row[$index])
		return $row[$index];
	else
		return "N/A";
}

function fmt_actions ($row, $index) {
	global $USER;
	$login = $row['login'];
	$userid = $row['userid'];
	$userenabled = $row['userenabled'];
	if (!$login)
		return "";
	$actionlinks = array();
	if ($login && $login !== 'schoolmessenger' && $USER->authorize('manageaccount')) {
		if ($userid)
			$actionlinks[] = action_link($row['userimportid'] > 0 ? _L("View User") : _L("Edit User"),"pencil","user.php?id=$userid");
		if ($userenabled)
			$actionlinks[] = action_link("Login as this user", "key_go", "./?login=$login");
	}
	return action_links($actionlinks);
}

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

// TODO: show personassociations?
$associatedorgdata = QuickQueryMultiRow("(select SQL_CALC_FOUND_ROWS l.id as id, 'List Rule' as type, l.name as name, u.login as login,
											'' as userimportid, '' as userid, u.enabled as userenabled
										from list l
											inner join listentry le on
												(l.id = le.listid and not l.deleted)
											inner join user u on
												(l.userid = u.id and not u.deleted)
										where le.type = 'organization' and le.organizationid = ?)
										union
										(select u.id as id, 'User Association' as type, concat(u.lastname, ', ', u.firstname) as name, u.login as login,
											u.importid as userimportid, u.id as userid, '' as userenabled
										from user u
											inner join userassociation ua on
												(u.id = ua.userid)
										where not u.deleted and ua.type = 'organization' and ua.organizationid = ?)
										union
										(select '' as id, 'Subscriber Field' as type, o.orgkey as name, '' as login,
											'' as userimportid, '' as userid, '' as userenabled
										from persondatavalues pdv
											inner join organization o on
												(pdv.value = o.id)
										where pdv.fieldnum = 'oid' and pdv.value = ?)
										union
										(select '' as id, 'Self Signup Person' as type, concat(p.f02, ', ', p.f01) as name, '' as login,
											'' as userimportid, '' as userid, '' as userenabled
										from person p
											inner join personassociation pa on
												(p.id = pa.personid)
										where not p.deleted and p.type = 'subscriber' and pa.organizationid = ?)
										order by login, type
										limit $start, $limit",
										true, false, array($orgid, $orgid, $orgid, $orgid));

$total = QuickQuery("select FOUND_ROWS()");

$titles = array(
	"login" => 'Owner Login',
	"type" => 'Type',
	"name" => 'Name',
	"id" => 'Action');
$formatters = array(
	"login" => "fmt_login",
	"id" => "fmt_actions");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = escapehtml($orgkey);

include_once("nav.inc.php");

buttons(icon_button(_L("Back"), "fugue/tick", "document.location='organizationdatamanager.php';"));

startWindow("Associations");

if (count($associatedorgdata)) {
	?><div style="float: right"><?
		showPageMenu($total, $start, $limit);
	?></div><div style="clear:both"></div>
	<table width="100%" cellpadding="3" cellspacing="1" class="list"><?
	showTable($associatedorgdata, $titles, $formatters);
	?></table><?
	showPageMenu($total, $start, $limit);
} else {
	?><div><img src='img/largeicons/information.jpg' /><?=escapehtml(_L("This Organization has no associations"))?></div><?
}

endWindow();
buttons();

include_once("navbottom.inc.php");
?>