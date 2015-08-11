<?

require_once("inc/common.inc.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");
require_once("obj/TwitterTokens.obj.php");

global $SETTINGS;
global $USER;

header('Content-Type: application/json');

if (! getSystemSetting('_hastwitter', false) || ! $USER->authorize('twitterpost'))
	echo false;

if (! isset($_REQUEST['type']))
	echo false;

$type = $_REQUEST['type'];

$twitterTokens = new TwitterTokens();

switch ($type) {
	case 'user':
		if (isset($_REQUEST['user_id'])) {
			$userdata = $twitterTokens->getUserDataForUserId($_REQUEST['user_id']);
			echo json_encode($userdata);
		}
		else echo false;
		break;

	case 'delete_access_token':
		if (isset($_REQUEST['user_id'])) {
			$twitterTokens->deleteAccessTokenForUserId($_REQUEST['user_id']);
		}
		else echo false;
		break;

	case "add_access_token":
		if (isset($_POST['access_token'])) {
			$twitterTokens->storeAccessTokens($_POST['access_token']);
		}
		else echo false;
		break;
	
	default:
		echo false;
}

