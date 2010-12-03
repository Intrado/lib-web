<?

require_once("inc/common.inc.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");

global $SETTINGS;
global $USER;

header('Content-Type: application/json');

if (!isset($_GET['type']) && !isset($_POST['type']))
	return false;

$type = isset($_GET['type'])?$_GET['type']:$_POST['type'];

// get twitter connection
$twitterdata = json_decode($USER->getSetting("tw_access_token", false));
if ($twitterdata) {
	$twitter = new Twitter($twitterdata->oauth_token, $twitterdata->oauth_token_secret);
} else {
	$twitter = new Twitter();
}
$twitter->decode_json = false;

switch ($type) {
	case "user":
		$userdata = $twitter->getUserData();
		echo $userdata;
		break;
	
	default:
		return false;
}


?>