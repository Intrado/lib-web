<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/FeedCategory.obj.php");
require_once("obj/Job.obj.php");
require_once("inc/appserver.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting("_hasfeed") || !$USER->authorize('feedpost')) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id']) && $_GET['id']) {
	$_SESSION['editjobfeedcategoryjobid'] = $_GET['id'] + 0;
	redirect("editjobfeedcategory.php");
}
////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
if (!isset($_SESSION['editjobfeedcategoryjobid']))
	redirect('unauthorized.php');

$jobid = $_SESSION['editjobfeedcategoryjobid'];
// get the job name and userid
$jobdetails = QuickQueryRow("select name, userid from job where id = ?", true, false, array($jobid));
if (!count($jobdetails) || $jobdetails['userid'] != $USER->id)
	redirect('unauthorized.php');

$currentcategories = QuickQueryList("select destination from jobpost where type = 'feed' and jobid = ? and posted", false, false, array($jobid));

$feedcategories = FeedCategory::getAllowedFeedCategories($jobid);

$categories = array();
foreach ($feedcategories as $category)
	$categories[$category->id] = $category->name;

$formdata = array(
	$jobdetails["name"],
	"feedcategories" => array(
		"label" => _L("Feed categories"),
		"fieldhelp" => _L('Select the most appropriate category for the RSS feed component of your message.'),
		"value" => (count($currentcategories)?$currentcategories:""),
		"validators" => array(
			array("ValInArray", "values" => array_keys($categories))),
		"control" => array("MultiCheckBox", "values"=>$categories, "hover" => FeedCategory::getFeedDescriptions()),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L('Select the most appropriate category for your RSS feed message.')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"posts.php"));
$form = new Form("templateform",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		// new job post feed category destinations
		$newcategories = $postdata['feedcategories'];
		$diffcategoryids = array_diff(array_merge($currentcategories, $newcategories), array_intersect($currentcategories, $newcategories));
		
		if (count($diffcategoryids)) {
			Query("BEGIN");
			
			$job = new Job($jobid);
			$job->modifydate = date("Y-m-d H:i:s", time());
			$job->updateJobPost("feed", $newcategories, 1);
			$job->update();
			
			Query("COMMIT");
			
			// expire feed categories that changed
			if (count($diffcategoryids))
				expireFeedCategories($CUSTOMERURL, $diffcategoryids);
		}
		if ($ajax)
			$form->sendTo("posts.php");
		else
			redirect("posts.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:post";
$TITLE = _L('Feed Categories');

include_once("nav.inc.php");

startWindow(_L("Active Feed Categories"));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>