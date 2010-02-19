<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Publish.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/User.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('subscribemessagegroup')) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
// requests to subscribe to a publishid
if (isset($_GET['publishid']) && isset($_GET['subscribe'])) {
	// check that this is a vaid publish id and the user can subscribe
	$publish = DBFind("Publish", "from publish where id = ? and action = 'publish' and type = 'messagegroup'", false, array($_GET['publishid']));
	// see that they don't subscribe more than once
	$publishSubscription = DBFind("Publish", "from publish where userid = ? and action = 'subscribe' and type = 'messagegroup' and messagegroupid = ?", false, array($_GET['publishid'], $publish->messagegroupid));
	if ($publish && $USER->authorize('subscribemessagegroup') && !$publishSubscription) {
		// create a new publish dbmo
		$subscribe = new Publish();
		$subscribe->userid = $USER->id;
		$subscribe->action = 'subscribe';
		$subscribe->type = 'messagegroup';
		$subscribe->messagegroupid = $publish->messagegroupid;
		$subscribe->create();
	}
}
// requests to remove a publishid
if (isset($_GET['publishid']) && isset($_GET['remove'])) {
	// check that this is a vaid publish id
	$publish = DBFind("Publish", "from publish where id = ? and action = 'subscribe' and userid = ? and type = 'messagegroup'", false, array($_GET['publishid'], $USER->id));
	if ($publish) {
		// if the user can subscribe, remove the subscription
		if ($USER->authorize('subscribemessagegroup'))
			$publish->destroy();
	}
}
////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

function fmt_msgname ($row, $index) {
	return "<div>". $row[$index] ."</div>";
}
function fmt_actions ($row, $index) {
	global $subscribed;
	global $start;
	$preview = action_link("Preview", "fugue/control", false, "popup('messagegroupviewpopup.php?id=" . $row['mgid'] ."', 700, 500)");
	if (isset($subscribed[$row['mgid']]))
		return action_links($preview, action_link("Un-Subscribe", "fugue/star__minus", "messagegroupsubscribe.php?publishid=". $subscribed[$row['mgid']] ."&remove&pagestart=$start"));
	else
		return action_links($preview, action_link("Subscribe", "fugue/star__plus", "messagegroupsubscribe.php?publishid=". $row[$index] ."&subscribe&pagestart=$start"));
}

$titles = array(
	"mgname" => 'Message Name',
	"mgdescription" => 'Description',
	"mgmodified" => 'Modified',
	"owner" => 'Owner',
	"id" => 'Action');
$formatters = array(
	"mgname" => "fmt_msgname",
	"mgmodified" => "fmt_date",
	"id" => "fmt_actions");

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

$data = array();
// get all the published messages
$data = QuickQueryMultiRow(
	"select SQL_CALC_FOUND_ROWS 
		p.id as id, mg.id as mgid, mg.name as mgname, mg.description as mgdescription, mg.modified as mgmodified, u.login as owner
	from publish p
	join messagegroup mg on
		(p.messagegroupid = mg.id and not mg.deleted)
	join user u on
		(p.userid = u.id)
	where p.userid != ?
		and action = 'publish'
	order by mg.name, id
	limit $start, $limit", 
	true, false, array($USER->id));

$total = QuickQuery("select FOUND_ROWS()");

// get all this user's subscribed messages
$subscribed = QuickQueryList("select messagegroupid, id from publish where action = 'subscribe' and userid = ?", true, false, array($USER->id));

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = "Subscribe";

include_once("nav.inc.php");

startWindow(_L("Subscribe to message"));
?><div style="padding-top: 5px; padding-bottom: 5px; float:left"><?
	buttons(icon_button("Done", "fugue/tick", "document.location='messages.php';"));
?></div><?
// if there are any messages to subscribe to
if (count($data)) {
	?><div style="float: right"><?
		showPageMenu($total, $start, $limit);
	?></div><div style="clear:both"></div><?
	?><table width="100%" cellpadding="3" cellspacing="1" class="list"><?
	showTable($data, $titles, $formatters);
	?></table><?
	showPageMenu($total, $start, $limit);
} else {
	?><div><img src='img/largeicons/information.jpg' /><?=escapehtml(_L("No published messages at this time"))?></div><?
}
endWindow();

include_once("navbottom.inc.php");
?>