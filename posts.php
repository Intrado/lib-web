<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("obj/Publish.obj.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/feed.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting("_hasfeed") || !$USER->authorize(array('feedpost'))) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
$isajax = isset($_GET['ajax']);

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
if($isajax === true) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

	$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
	$limit = 100;
	$orderby = "date desc";

	$filter = "";
	if (isset($_GET['filter'])) {
		$filter = $_GET['filter'];
	}
	switch ($filter) {
		case "name":
			$orderby = "digitsfirst, name";
			break;
	}
	
	// get all the job info for jobs which contain feed post data
	$jobids = QuickQueryList(
		"select SQL_CALC_FOUND_ROWS id
		from job j
		inner join jobpost jp on (j.id = jp.jobid)
		where jp.posted and jp.type in ('feed','page')
		and j.userid = ?
		group by jobid",
		false, false, array($USER->id));
	
	// total rows
	$total = QuickQuery("select FOUND_ROWS()");
	
	// get all the job data
	if ($total) {
		// jobpost page destination will be an empty string, but it atleast tells us we do have a page post
		$postdata = QuickQueryMultiRow(
			"select j.id as jobid, j.messagegroupid as messagegroupid, group_concat(jpf.destination SEPARATOR ',') as feeddestination,
				jpp.posted as pageposted, j.modifydate as date, j.name as name, j.description as description,
				(j.name +0) as digitsfirst
			from job j
			left join jobpost jpf on (jpf.jobid = j.id and jpf.type = 'feed' and jpf.posted)
			left join jobpost jpp on (jpp.jobid = j.id and jpp.type = 'page' and jpp.posted)
			where j.id in (".implode(",", $jobids).")
			group by j.id
			order by $orderby, j.id
			limit $start, $limit",
			true, false, array($USER->id));
	} else {
		$postdata = array();
	}
	
	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;

	if(empty($postdata)) {
		$data->list[] = array(
			"icon" => "img/largeicons/globe.jpg",
			"title" => _L("No Posts."),
			"details" => "",
			"defaultlink" => "",
			"content" => "",
			"tools" => "");
	} else {
		foreach ($postdata as $post) {
			$mgid = $post["messagegroupid"];
			$jobid = $post['jobid'];
			$pageposted = $post['pageposted'];
			$time = date("M j, Y g:i a",strtotime($post["date"]));
			$title = escapehtml($post["name"]);
			
			$icon = 'img/largeicons/globe.jpg';
			
			// if the user owns this message group, they can edit, delete
			$actions = array();
			$messagegroup = new MessageGroup($mgid);
			if (userOwns("messagegroup", $messagegroup->id)) {
				if ($messagegroup->hasMessage("post","page") && $pageposted)
					$actions[] = action_link("Page", "layout_sidebar", 'editmessagepage.php?postedit&id=' . $messagegroup->getMessage("post", "page", "en")->id);
				if ($messagegroup->hasMessage("post","voice") && $pageposted)
					$actions[] = action_link("Media", "../nifty_play", 'editmessagepostvoice.php?postedit&id=' . $messagegroup->getMessage("post", "voice", "en")->id);
				if ($messagegroup->hasMessage("post","feed"))
					$actions[] = action_link("Feed", "rss", 'editmessagefeed.php?postedit&id=' . $messagegroup->getMessage("post", "feed", "en")->id);
			} else {
				$actions[] = action_link("View", "fugue/magnifier", 'messagegroupview.php?id=' . $mgid);
			}
			if ($messagegroup->hasMessage("post","feed")) {
				$actions[] = action_link("Feed Categories", "pencil", 'editjobfeedcategory.php?postedit&id=' . $jobid);
			}
			$tools = action_links ($actions);
			
			// get the job post feed categories
			$categoryids = explode(",", $post["feeddestination"]);
			if (count($categoryids)) {
				$categorynames = QuickQueryList("select name from feedcategory where id in (".repeatWithSeparator("?",",",count($categoryids)).")", false, false, $categoryids);
			} else {
				$categorynames = array();
			}
			if (count($categorynames))
				$categories = "Categories: ".implode(", ", $categorynames);
			else
				$categories = "";
			
			$defaultlink = "job.php?id=".$post["jobid"];
			$content = '<a href="' . $defaultlink . '" >' . $time .  ($post["description"] != ""?" - " . escapehtml($post["description"]):"") . '</a>';
			
			$data->list[] = array(
				"icon" => $icon,
				"title" => $title,
				"details" => $categories,
				"defaultlink" => $defaultlink,
				"content" => $content,
				"tools" => $tools
			);

		}
	}

	$data->pageinfo = array($numpages,$limit,$curpage, "Showing $displaystart - $displayend of $total records on $numpages pages ");

	header('Content-Type: application/json');
	echo json_encode(!empty($data) ? $data : false);
	exit();
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:post";
$TITLE = "Posted Content";

include_once("nav.inc.php");

startWindow(_L('My Posts'));

$feedButtons = array(icon_button(_L('Generate Feed URL/Widget'),"add","location.href='feedurlwiz.php?new'"));

$feedFilters = array(
	"name" => array("icon" => "img/largeicons/tiny20x20/pencil.jpg", "name" => "Name"),
	"date" => array("icon" => "img/largeicons/tiny20x20/clock.jpg", "name" => "Date")
);

feed($feedButtons,$feedFilters);

?>
<script type="text/javascript" src="script/feed.js.php"></script>
<script type="text/javascript">
var filtes = <?= json_encode(array_keys($feedFilters))?>;
var activepage = 0;
var currentfilter = 'date';
document.observe('dom:loaded', function() {
	feed_applyfilter('<?=$_SERVER["REQUEST_URI"]?>','name');
});
</script>
<?
endWindow();
include_once("navbottom.inc.php");
?>