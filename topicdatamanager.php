<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Topic.obj.php");
require_once("obj/TopicDataManager.obj.php");
require_once("obj/TopicDataFormatter.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata')) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$topicDataManager = new TopicDataManager();

if (isset($_GET["delete"]) && isset($_GET["topicid"])) {
	$topicDataManager->deleteTopic($_GET["topicid"]);
}

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

$topicDataFormatter = new TopicDataFormatter($start, $limit, $topicDataManager->topicsInfo($start,$limit));



///////////////////////////////////////////////////////////////////////////////
// Display
///////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = "Topic Manager";

include_once("nav.inc.php");

buttons(icon_button(_L("Done"), "fugue/tick", "document.location='settings.php';"));

startWindow(_L("Topics"));
?>
	<div class="feed_btn_wrap cf"><?= icon_button(_L('Add New Topic'),"add",null,"topicedit.php?topicid=new") ?></div>
<?

// if there are any topics
	if ($topicDataFormatter->anyTopics()) {

		$topicDataFormatter->showMenu();

		?><table width="100%" cellpadding="3" cellspacing="1" class="list"><?

		$topicDataFormatter->showTable();

		?></table><?

		$topicDataFormatter->showMenu();

	} else {
		?><div><img src='img/largeicons/information.jpg' /><?=escapehtml(_L("No topics defined"))?></div><?
	}

endWindow();
buttons();

include_once("navbottom.inc.php");
?>
