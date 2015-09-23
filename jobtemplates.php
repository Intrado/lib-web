<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/feed.inc.php");
require_once("obj/Job.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendsms')) {
	if (isset($_REQUEST['api'])) {
		header("HTTP/1.1 403 Forbidden");
		exit();
	}
	else{
		redirect('unauthorized.php');
	}
}

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if (userOwns("job",$deleteid)) {
		$job = new Job($deleteid);
		if ($job->status == "template" && $job->softDelete())
			notice(_L("The %s Template, %s, is now deleted.", getJobTitle(), escapehtml($job->name)));
		else
			notice(_L("The %s Template, %s, Could not be deleted. %s", getJobTitle(), escapehtml($job->name),$job->status));
	} else {
		notice(_L("The %s Template, Could not be deleted.", getJobTitle()));
	}
	redirectToReferrer();
}

if (isset($_GET['show']) && isset($_GET['templateid'])) {
	$id = DBSafe($_GET['templateid']);
	if (userOwns("job",$id)) {
		$job = new Job($id);
		$job->setSetting("displayondashboard",$_GET['show'] == "true"?true:false);
		$job->update();
	}
	redirectToReferrer();
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

	if (isset($_REQUEST['api'])) {
		// allow pass perpage querystrig for API up to 20
		if ($_GET['perpage']) {
			$limit = min(20, max(5, ((int)$_GET['perpage'])));
		}
	}

	$orderby = "date desc";

	$sortby = "";
	if (isset($_GET['feed_sortby'])) {
		$sortby = $_GET['feed_sortby'];
	}
	switch ($sortby) {
		case "name":
			$orderby = "digitsfirst, name";
			break;
	}
	
	// get all the info for job templates
	//$jobids = QuickQueryList("select SQL_CALC_FOUND_ROWS id from job j where j.userid = ? and j.status='template'", false, false, array($USER->id));
	
	$templatedata = QuickQueryMultiRow(
				"select SQL_CALC_FOUND_ROWS j.id as jobid, 
					j.messagegroupid as messagegroupid,
					j.jobtypeid as jobtypeid,
					j.modifydate as date,
					j.name as name,
					j.description as description,
					js.value as displayondashboard,
					(j.name +0) as digitsfirst
				from job j left join jobsetting js on (j.id = js.jobid and js.name='displayondashboard') where j.userid = ? and j.status='template' and not j.deleted
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
			"icon" => "img/newui/templates.png",
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
			$icon = 'img/newui/templates.png';
			
			$lists = json_encode(quickQueryList("select listid from joblist where jobid = ?", false, false, array($jobid)));
			$templateoptions = array(
				"subject" => $template["name"],
				"lists" => $lists,
				"jobtypeid" => $template["jobtypeid"],
				"messagegroupid" => $template["messagegroupid"]
			);
			
			$actionlinks = array(
				action_link(_L("New %s",getJobTitle()), "add", 'newbroadcast.php?template=true&' . http_build_query($templateoptions)),
				action_link(_L("Edit"), "pencil", 'jobtemplate.php?id=' . $jobid)
			);
			
			if ($template["displayondashboard"]) {
				$actionlinks[] = action_link(_L("Hide on Dashboard"), "application_form_delete", "jobtemplates.php?templateid=$jobid&show=false");
			} else {
				$actionlinks[] = action_link(_L("Show on Dashboard"), "application_form_add", "jobtemplates.php?templateid=$jobid&show=true");
			}
			$actionlinks[] = action_link(_L("Delete"), "cross", 'jobtemplates.php?delete=' . $jobid, "return confirmDelete();");
			$tools = action_links ($actionlinks);
			
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

	if (isset($_REQUEST['api'])) {
		// Return docs and metadata.
		echo json_encode(array(
			'docs' => $templatedata,
			'metadata' => array(
				'start' => $start,
				'pagesize' => $limit,
				'totalrows' => $total,
				'numpages' => $numpages,
				'curpage' => $curpage
			)
		));
	}
	else{
		echo json_encode(!empty($data) ? $data : false);
	}

	exit();
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:templates";
$TITLE = _L("%s Templates", getJobTitle());

include_once("nav.inc.php");

startWindow(_L("%s Templates", getJobTitle()));

$feedButtons = array(icon_button(_L('Add New Template'),"add","location.href='jobtemplate.php?id=new'"));
$sortoptions = array(
	"name" => array("icon" => "img/largeicons/tiny20x20/pencil.jpg", "name" => "Name"),
	"date" => array("icon" => "img/largeicons/tiny20x20/clock.jpg", "name" => "Date")
);

feed($feedButtons,$sortoptions);
?>


<script type="text/javascript" src="script/feed.js.php"></script>
<script type="text/javascript">



document.observe('dom:loaded', function() {
	feed_applyDefault('<?=$_SERVER["REQUEST_URI"]?>','name');
});
</script>
<?
endWindow();
include_once("navbottom.inc.php");
?>