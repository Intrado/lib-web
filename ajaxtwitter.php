<?

require_once("inc/common.inc.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");

global $SETTINGS;
global $USER;

header('Content-Type: application/json');

if (!$USER->authorize('twitterpost'))
	echo false;

if (!isset($_GET['type']) && !isset($_POST['type']))
	echo false;

$type = isset($_GET['type'])?$_GET['type']:$_POST['type'];

// get twitter connection
$twitter = new Twitter($USER->getSetting("tw_access_token", false));

$twitter->decode_json = false;

switch ($type) {
	case "user":
		$userdata = $twitter->getUserData();
		echo $userdata;
		break;
		
	case "store_access_token":
		if (isset($_POST['access_token']))
			$USER->setSetting("tw_access_token", (($_POST['access_token'] == "false")?false:$_POST['access_token']));
	
	default:
		echo false;
}


?>