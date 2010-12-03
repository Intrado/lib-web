<?

require_once("inc/common.inc.php");
require_once("inc/twitteroauth/OAuth.php");
require_once("inc/twitteroauth/twitteroauth.php");
require_once("obj/Twitter.obj.php");

global $SETTINGS;
global $USER;

// if oauth_token is set, this is a redirect back from twitter authorization
if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier']) && isset($_SESSION['twitterRequestToken'])) {
	
	// create a twitter connection with the temporary auth tokens stored in session data
	$twitter = new Twitter($_SESSION['twitterRequestToken']['oauth_token'], $_SESSION['twitterRequestToken']['oauth_token_secret']);
	
	// get the access token and store it in the DB for this user
	$twAccessToken = $twitter->getAccessToken($_GET['oauth_verifier']);
	$USER->setSetting("tw_access_token", json_encode($twAccessToken));
	
	// remove temporary session data
	unset($_SESSION['twitterRequestToken']);
	
	// get redirect url
	$caller = substr($_SERVER['PATH_INFO'], 1);
	redirect("../". $caller);
	
} else {
	// can't get an authorize url with a valid access token so create a temp connection w/o one
	$unauthconnection = new Twitter();
		
	// get a temporary request token
	$thispage = (($_SERVER["HTTPS"])?"https://":"http://"). $_SERVER["SERVER_NAME"]. $_SERVER["REQUEST_URI"];
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