<?

require_once("inc/common.inc.php");
require_once("inc/facebook.php");
require_once("inc/facebookEnhanced.inc.php");
require_once("inc/facebook.inc.php");

global $SETTINGS;
global $USER;

header('Content-Type: application/json');

if (!getSystemSetting('_hasfacebook', false) || !$USER->authorize('facebookpost'))
	echo false;

if (!isset($_GET['type']) && !isset($_POST['type']))
	echo false;

$type = isset($_GET['type'])?$_GET['type']:$_POST['type'];

switch ($type) {
	case "save":
		if (isset($_POST['access_token'])) {
			list($accessToken, $expiresIn) = fb_getExtendAccessToken($_POST['access_token']);
			$USER->setSetting("fb_access_token", $accessToken);
			$USER->setSetting("fb_expires_on", strtotime("now") + $expiresIn);
		}
		if (isset($_POST['fb_user_id']))
			$USER->setSetting("fb_user_id", (($_POST['fb_user_id'] == "false")?false:$_POST['fb_user_id']));
		break;
	
	case "delete":
		$USER->setSetting("fb_user_id", false);
		$USER->setSetting("fb_access_token", false);
		$USER->setSetting("fb_expires_on", false);
		break;
		
	default:
		echo false;
}


?>