<?

require_once("inc/common.inc.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");

global $SETTINGS;
global $USER;

header('Content-Type: application/json');

if (! getSystemSetting('_hastwitter', false) || ! $USER->authorize('twitterpost'))
	echo false;

if (! isset($_GET['type']) && !isset($_POST['type']))
	echo false;

$type = isset($_GET['type']) ? $_GET['type'] : $_POST['type'];

// Get the recorded accounts
$existingTwAccessTokens = json_decode($USER->getSetting("tw_access_token", false));

switch ($type) {
	case 'user':
		if (isset($_REQUEST['user_id'])) {
			if (is_array($existingTwAccessTokens) && (count($existingTwAccessTokens) > 0)) {
				// so for each of the currently stored access tokens...
				for ($xx = 0; $xx < count($existingTwAccessTokens); $xx++) {
					// If this is the one we're looking for...
					if ($existingTwAccessTokens[$xx]->user_id === $_REQUEST['user_id']) {
						// get twitter connection
						$twitter = new Twitter($existingTwAccessTokens[$xx], false);
						$twitter->decode_json = false;
						$userdata = $twitter->getUserData();
						echo json_encode($userdata);
						break;
					}
				}
			}
		}
		else echo false;
		break;

	case 'delete_access_token':
		if (isset($_REQUEST['user_id'])) {
			$finalTwAccessTokens = array();
			if (is_array($existingTwAccessTokens) && (count($existingTwAccessTokens) > 0)) {

				// so for each of the currently stored access tokens...
				for ($xx = 0; $xx < count($existingTwAccessTokens); $xx++) {

					// If the account is a different one...
					if ($existingTwAccessTokens[$xx]->user_id !== $_REQUEST['user_id']) {

						// Migrate this one into the final collection
						$finalTwAccessTokens = $existingTwAccssTokens[$xx];
					}
				}
			}

			// And update the final collection; false if it's an empty set causes the setting to be deleted
			$USER->setSetting('tw_access_token', (count($finalTwAccessTokens) ? json_encode($finalTwAccessTokens) : false));
		}
		else echo false;
		break;

	case "store_access_token":
		if (isset($_POST['access_token'])) {

			if ($_POST['access_token'] == 'false') {
				$twitter->purgeCachedUserData();
				$USER->setSetting("tw_access_token", false);
			}
			else {
				$USER->setSetting("tw_access_token", $_POST['access_token']);
			}
		}
	
	default:
		echo false;
}


?>
