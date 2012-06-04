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
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint')  && !$USER->authorize('sendsms') && !$USER->authorize('managesystemjobs')) {
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
	$limit = 5;
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
	
	// get all the info for job templates
	//$jobids = QuickQueryList("select SQL_CALC_FOUND_ROWS id from job j where j.userid = ? and j.status='template'", false, false, array($USER->id));
	
	$templatedata = QuickQueryMultiRow(
				"select SQL_CALC_FOUND_ROWS j.id as jobid, 
					j.messagegroupid as messagegroupid,
					j.modifydate as date,
					j.name as name,
					j.description as description,
					(j.name +0) as digitsfirst
				from job j where j.userid = ? and j.status='template'
				order by $orderby, j.id
				limit $start, $limit",true, false, array($USER->id));
	
	// total rows
	$total = QuickQuery("select FOUND_ROWS()");
	
	// get all the job data
	if (!$total) {
		$templatedata = array();
	}
	
	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;

	if(empty($templatedata)) {
		$data->list[] = array(
			"icon" => "img/largeicons/globe.jpg",
			"title" => _L("No Templates."),
			"details" => "",
			"defaultlink" => "",
			"content" => "",
			"tools" => "");
	} else {
		foreach ($templatedata as $template) {
			$jobid = $template['jobid'];
			$time = date("M j, Y g:i a",strtotime($template["date"]));
			$title = escapehtml($template["name"]);
			$icon = 'img/largeicons/globe.jpg';
			
			$tools = action_links (
				action_link("Edit", "pencil", 'jobtemplate.php?id=' . $jobid),
				action_link("Delete", "cross", 'jobtemplates.php?delete=' . $jobid, "return confirmDelete();")
			);
			
			$defaultlink = "jobtemplate.php?id=".$jobid;
			$content = '<a href="' . $defaultlink . '" >' . $time .  ($template["description"] != ""?" - " . escapehtml($template["description"]):"") . '</a>';
			
			$data->list[] = array(
				"icon" => $icon,
				"title" => $title,
				"details" => "",
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

$PAGE = "notifications:templates";
$TITLE = _L("%s Templates", getJobTitle());

include_once("nav.inc.php");

startWindow(_L('Templates'));

$feedButtons = array(icon_button(_L('Add Template'),"add","location.href='jobtemplate.php?id=new'"));
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