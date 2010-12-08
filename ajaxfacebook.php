<?

require_once("inc/common.inc.php");
require_once("inc/facebook.php");
require_once("inc/facebook.inc.php");

global $SETTINGS;
global $USER;

header('Content-Type: application/json');

if (!$USER->authorize('facebookpost'))
	echo false;

if (!isset($_GET['type']) && !isset($_POST['type']))
	echo false;

$type = isset($_GET['type'])?$_GET['type']:$_POST['type'];

switch ($type) {
	case "store_access_token":
		if (isset($_POST['access_token']))
			$USER->setSetting("fb_access_token", (($_POST['access_token'] == "false")?false:$_POST['access_token']));
		break;
	
	default:
		echo false;
}


?>