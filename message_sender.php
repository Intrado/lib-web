<? 
include_once("inc/common.inc.php");

if (isset($_GET['template']) && $_GET['template'] && isset($_GET['subject']) && $_GET['subject']) {
	$_SESSION['message_sender'] = array();
	$_SESSION['message_sender']['template'] = array(
		"subject" => $_GET['subject'],
		"lists" => (isset($_GET['lists'])?$_GET['lists']:"[]"),
		"jobtypeid" => (isset($_GET['jobtypeid'])?$_GET['jobtypeid']:0),
		"messagegroupid" => (isset($_GET['messagegroupid'])?$_GET['messagegroupid']:0));
	redirect();
} else if (isset($_GET['new'])) {
	unset($_SESSION['message_sender']);
	redirect();
}

$PAGE = "notifications:jobs";
// if (isset($_SESSION['message_sender']['template']['subject']))
// 	$TITLE = _L("Broadcast Template: %s", $_SESSION['message_sender']['template']['subject']);
// else
	$TITLE = _L("New Broadcast");

// Moved this include into message_sender/index.php 
//include("nav.inc.php");

include("message_sender/index.php");

include("navbottom.inc.php"); ?>