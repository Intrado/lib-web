<?
// This page is a redirect used in twitter authorization requests.
// It will redirect to twitter if redirection is needed and then back to the calling page once authorization is accepted.

require_once("inc/common.inc.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");

global $SETTINGS;
global $USER;

if (!getSystemSetting('_hastwitter', false) || !$USER->authorize('twitterpost'))
	redirect('unauthorized.php');
		
// if oauth_token is set, this is a redirect back from twitter authorization
if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier']) && isset($_SESSION['twitterRequestToken'])) {
	
	// create a twitter connection with the temporary auth tokens stored in session data
	$twitter = new Twitter(
		json_encode(
			array(
				"oauth_token" => $_SESSION['twitterRequestToken']['oauth_token'],
				"oauth_token_secret" => $_SESSION['twitterRequestToken']['oauth_token_secret']
			)
		)
	);
	
	// get the access token and store it in the DB for this user
	$newTwAccessToken = $twitter->getAccessToken($_GET['oauth_verifier']);

	// Remove any an usersettings entry with the same account ID...
	$existingTwAccessTokens = json_decode($USER->getSetting("tw_access_token", false));
	$finalTwAccessTokens = array();
	if (is_array($existingTwAccessTokens) && (count($existingTwAccessTokens) > 0)) {
		// so for each of the currently stored access tokens...
		for ($xx = 0; $xx < count($existingTwAccessTokens); $xx++) {

			// If the account is a different one...
			if ($existingTwAccessTokens[$xx]->user_id !== $newTwAccesstoken->user_id) {
				// Migrate this one into the final collection
				$finalTwAccessTokens = $existingTwAccssTokens[$xx];
			}
		}
	}
	// Add this new one to the collection
	$finalTwAccessTokens[] = $newTwAccessToken;
	$finalEncoded = json_encode($finalTwAccessTokens);
//error_log('setting new twitter auth: ' . $finalEncoded);
	$USER->setSetting("tw_access_token", $finalEncoded);
	
	// remove temporary session data
	unset($_SESSION['twitterRequestToken']);
	
	// get redirect url
	$caller = substr($_SERVER['PATH_INFO'], 1);
	redirect("../". $caller);
	
}
else {
	// can't get an authorize url with a valid access token so create a temp connection w/o one
	$unauthconnection = new Twitter(false);
		
	// get a temporary request token
	$thispage = ((isset($_SERVER["HTTPS"]))?"https://":"http://"). $_SERVER["SERVER_NAME"]. $_SERVER["REQUEST_URI"];
	$requestToken = $unauthconnection->getRequestToken($thispage);
	
	// if the request token is valid
	if (isset($requestToken['oauth_token'])) {
		//save it to session data for later
		$_SESSION['twitterRequestToken'] = $requestToken;
		
		redirect($unauthconnection->getAuthorizeURL($requestToken['oauth_token']));
	} else {
		// error... redirect back to the caller
		$caller = substr($_SERVER['PATH_INFO'], 1);
		redirect("../". $caller);
	}
}

?>
