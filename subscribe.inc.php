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
	$subscription = Publish::getSubscriptions($SUBSCRIBETYPE, $_GET['id'], $USER->id);
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
	global $data;
	global $start;
	global $SUBSCRIBETYPE;
	$actionlinks = array();
	if ($SUBSCRIBETYPE == 'messagegroup')
		$actionlinks[] = action_link("View", "fugue/magnifier", "messagegroupview.php?id=" . $row[$index]);
	if (isset($data["subscribed"][$row['id']]))
		$actionlinks[] = action_link("Un-Subscribe", "fugue/star__minus", $SUBSCRIBETYPE."subscribe.php?id=". $data["subscribed"][$row[$index]] ."&remove&pagestart=$start");
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


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$data = null;

switch ($SUBSCRIBETYPE) {
	case "messagegroup":
		$TITLE = escapehtml(_L("Manage Message Subscriptions"));
		$subtab = 'messages';
		$windowtitle = escapehtml(_L('Published Messages'));
		$parent = 'messages.php';
		$data = Publish::getSubscribableItems($SUBSCRIBETYPE,"notification",$start,$limit);
		
		break;
	case "list":
		$TITLE = escapehtml(_L("Manage List Subscriptions"));
		$subtab = 'lists';
		$windowtitle = escapehtml(_L('Published Lists'));
		$parent = 'lists.php';
		$data = Publish::getSubscribableItems($SUBSCRIBETYPE,null,$start,$limit);
		break;
}

$PAGE = "notifications:$subtab";

include_once("nav.inc.php");


startWindow($windowtitle);
// if there are any messages to subscribe to
?><div style="padding-top: 5px; padding-bottom: 5px; float: left;"><?
	buttons(icon_button(_L("Done"), "fugue/tick", "document.location='$parent';"));
?></div><?
if (count($data["items"])) {
	?><div style="float: right"><?
		showPageMenu($data["total"], $start, $limit);
	?></div><div style="clear:both"></div><?
	?><table width="100%" cellpadding="3" cellspacing="1" class="list"><?
	showTable($data["items"], $titles, $formatters);
	?></table><?
	showPageMenu($data["total"], $start, $limit);
} else {
	?><div><img src='img/largeicons/information.jpg' /><?=escapehtml(_L("No items available at this time"))?></div><?
}
endWindow();
include_once("navbottom.inc.php");
?>