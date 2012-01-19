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
require_once("inc/appserver.inc.php");
require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once($GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php');

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

$jobpostdestination = QuickQuery("select destination from jobpost where type = 'feed' and jobid = ?", false, array($jobid));

// get the current feed categories for the job
if ($jobpostdestination)
	$currentcategories = explode(",",$jobpostdestination);
else
	$currentcategories = array();

// get all the feed categories for the current user and those already associated with the job
$args = array();
// if the user has feed restrictions...
if (QuickQuery("select 1 from userfeedcategory where userid = ? limit 1", false, array($USER->id))) {
	$args[] = $USER->id;
	$usercategorywhere = "id in (select feedcategoryid from userfeedcategory where userid=?) ";
	
	// the job may already have categories selected, make sure they are displayed as well.
	$jobcategorywhere = "";
	if (count($currentcategories)) {
		foreach ($currentcategories as $id)
		$args[] = $id;
		$jobcategorywhere = " id in (".repeatWithSeparator("?",",",count($currentcategories)).") ";
	}
	
	// construct the where clause based off which restrictions (if any) exist
	if ($usercategorywhere && $jobcategorywhere)
		$categorywhere = " ($usercategorywhere or $jobcategorywhere) ";
	else if ($usercategorywhere)
		$categorywhere = $usercategorywhere;
	else
		$categorywhere = $jobcategorywhere;
} else {
	// user is unrestricted, just show them all categories
	$categorywhere = "1";
}
$possiblefeedcategories = QuickQueryMultiRow(
	"select id, name, description
	from feedcategory 
	where
	$categorywhere
	and not deleted
	order by name",
	true, false, $args);

$feedcategories = array();
$feeddescriptions = array();
foreach ($possiblefeedcategories as $category) {
	$feedcategories[$category['id']] = $category['name'];
	$feeddescriptions[$category['id']] = $category['description'];
}
$formdata = array(
	$jobdetails["name"],
	"feedcategories" => array(
		"label" => _L("Feed categories"),
		"fieldhelp" => _L('Select which categories you wish to include in this feed.'),
		"value" => (count($currentcategories)?$currentcategories:""),
		"validators" => array(
			array("ValInArray", "values" => array_keys($feedcategories))),
		"control" => array("MultiCheckBox", "values"=>$feedcategories, "hover" => $feeddescriptions),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L('TODO: help me!')
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
		$newjobpostdestination = implode(",", $postdata['feedcategories']);
		if ($jobpostdestination != $newjobpostdestination) {
			Query("BEGIN");
			
			// delete existing
			QuickUpdate("delete from jobpost where jobid = ? and type = 'feed'", false, array($jobid));
			
			// create new one
			if ($newjobpostdestination)
				QuickUpdate("insert into jobpost values (?,'feed',?,1)", false, array($jobid, $newjobpostdestination));
			
			Query("COMMIT");
			
			
			// expire feed categories that changed
			$categoryids = array_diff(array_merge($currentcategories,$postdata['feedcategories']), array_intersect($currentcategories, $postdata['feedcategories']));
			if (count($categoryids))
				expireFeedCategories($CUSTOMERURL, $categoryids);
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