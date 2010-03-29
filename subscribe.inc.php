<?
// SUBSCRIBETYPE must be set by the page including this one
if (!isset($SUBSCRIBETYPE))
	redirect('unauthorized.php');

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
// requests to subscribe to a publishid
if (isset($_GET['id']) && isset($_GET['subscribe'])) {
	// see that they don't subscribe more than once
	$subscription = getSubscriptions($SUBSCRIBETYPE, $_GET['id'], $USER->id);
	if (userCanSubscribe($SUBSCRIBETYPE, $_GET['id']) && !$subscription) {
		// create a new publish dbmo
		$subscribe = new Publish();
		$subscribe->userid = $USER->id;
		$subscribe->action = 'subscribe';
		$subscribe->type = $SUBSCRIBETYPE;
		$subscribe->setTypeId($_GET['id']);
		$subscribe->create();
	}
	redirect();
}

// requests to remove a publishid
if (isset($_GET['id']) && isset($_GET['remove'])) {
	// check that this is a vaid publish id
	$publish = DBFind("Publish", "from publish where id = ? and action = 'subscribe' and userid = ?", false, array($_GET['id'], $USER->id));
	if ($publish) {
		$publish->destroy();
	}
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

function fmt_name ($row, $index) {
	return "<div>". $row[$index] ."</div>";
}
function fmt_actions ($row, $index) {
	global $subscribed;
	global $start;
	global $SUBSCRIBETYPE;
	$actionlinks = array();
	if ($SUBSCRIBETYPE == 'messagegroup')
		$actionlinks[] = action_link("Preview", "fugue/control", false, "popup('messagegroupviewpopup.php?id=" . $row[$index] ."', 700, 500)");
	if (isset($subscribed[$row['id']]))
		$actionlinks[] = action_link("Un-Subscribe", "fugue/star__minus", $SUBSCRIBETYPE."subscribe.php?id=". $subscribed[$row[$index]] ."&remove&pagestart=$start");
	else
		$actionlinks[] = action_link("Subscribe", "fugue/star__plus", $SUBSCRIBETYPE."subscribe.php?id=". $row[$index] ."&subscribe&pagestart=$start");
	return action_links($actionlinks);
}

$titles = array(
	"name" => 'Name',
	"description" => 'Description',
	"modified" => 'Modified',
	"owner" => 'Owner',
	"id" => 'Action');
$formatters = array(
	"name" => "fmt_name",
	"modified" => "fmt_date",
	"id" => "fmt_actions");

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

$data = array();

// look up the user's organization associations
$userassociatedorgs = QuickQueryList("
	(select ua.organizationid as oid
	from userassociation ua
	where ua.userid = ? and ua.type = 'organization')
	UNION
	(select ua.organizationid as oid
	from userassociation ua
		left join section s on
			(ua.sectionid = s.id and ua.type = 'section')
	where ua.userid = ?)",
	false, false, array($USER->id, $USER->id));

// build the argument array 
$args = array($USER->id);

// create the sql that limits results by orgs, or doesn't depending on user associations
$orgrestrictionsql = "";
if (count($userassociatedorgs) == 0) {
	unset($userassociatedorgs);
	$orgrestrictionsql = "1";
} else if (count($userassociatedorgs) == 1 && $userassociatedorgs[0] == null) {
	// this user is restricted to sectionid 0 and has no additional associations that provide orgs
	unset($userassociatedorgs);
	$orgrestrictionsql = "p.organizationid is null";
} else {
	// user has org restrictions, add them to the args array but skip null
	$orgcount = 0;
	foreach ($userassociatedorgs as $index => $orgid) {
		if ($orgid !== null) {
			$orgcount++;
			$args[] = $orgid;
		}
	}
	$orgrestrictionsql = "(p.organizationid is null or p.organizationid in (" . DBParamListString($orgcount) ."))";
}

if ($SUBSCRIBETYPE == 'messagegroup') {

	$data = QuickQueryMultiRow(
		"select SQL_CALC_FOUND_ROWS 
			p.id as pubid, mg.id as id, mg.name as name, mg.description as description, mg.modified as modified, u.login as owner
		from publish p
		inner join messagegroup mg on
			(p.messagegroupid = mg.id and not mg.deleted)
		inner join user u on
			(p.userid = u.id)
		where p.userid != ?
			and action = 'publish'
			and " .$orgrestrictionsql. "
		group by id
		order by name, pubid
		limit $start, $limit", 
		true, false, $args);

	$total = QuickQuery("select FOUND_ROWS()");

	// get all this user's subscribed ids
	$subscribed = QuickQueryList("select messagegroupid, id from publish where action = 'subscribe' and type = 'messagegroup' and userid = ?", true, false, array($USER->id));
	
} else if ($SUBSCRIBETYPE == 'list') {

	$data = QuickQueryMultiRow(
		"select SQL_CALC_FOUND_ROWS 
			p.id as pubid, l.id as id, l.name as name, l.description as description, l.modifydate as modified, u.login as owner
		from publish p
		inner join list l on
			(p.listid = l.id and not l.deleted)
		inner join user u on
			(p.userid = u.id)
		where p.userid != ?
			and action = 'publish'
			and " .$orgrestrictionsql. "
		group by id
		order by name, pubid
		limit $start, $limit", 
		true, false, $args);

	$total = QuickQuery("select FOUND_ROWS()");

	// get all this user's subscribed ids
	$subscribed = QuickQueryList("select listid, id from publish where action = 'subscribe' and type = 'list' and userid = ?", true, false, array($USER->id));
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
switch ($SUBSCRIBETYPE) {
	case "messagegroup":
		$TITLE = escapehtml(_L("Manage Message Subscriptions"));
		$subtab = 'messages';
		$windowtitle = escapehtml(_L('Published Messages'));
		$parent = 'messages.php';
		break;
	case "list":
		$TITLE = escapehtml(_L("Manage List Subscriptions"));
		$subtab = 'lists';
		$windowtitle = escapehtml(_L('Published Lists'));
		$parent = 'lists.php';
		break;
}

$PAGE = "notifications:$subtab";

include_once("nav.inc.php");


startWindow($windowtitle);
// if there are any messages to subscribe to
?><div style="padding-top: 5px; padding-bottom: 5px; float: left;"><?
	buttons(icon_button(_L("Done"), "fugue/tick", "document.location='$parent';"));
?></div><?
if (count($data)) {
	?><div style="float: right"><?
		showPageMenu($total, $start, $limit);
	?></div><div style="clear:both"></div><?
	?><table width="100%" cellpadding="3" cellspacing="1" class="list"><?
	showTable($data, $titles, $formatters);
	?></table><?
	showPageMenu($total, $start, $limit);
} else {
	?><div><img src='img/largeicons/information.jpg' /><?=escapehtml(_L("No items available at this time"))?></div><?
}
endWindow();
include_once("navbottom.inc.php");
?>