<?
/**
 * Handle login requests via portalauth through AuthServer
 *
 * Date: 1/24/2013
 */

$ppNotLoggedIn = true;
require_once("common.inc.php");

if (isset($_REQUEST["is_return"])) {
//error_log("portallogin isreturn");
	doStartSession(); // start session to send sessionid to login
	// useing the access token, request that authserver create a session for whoever is logged into portal
	$loginDetails = loginViaPortalAuth();
	if ($loginDetails && isset($loginDetails["userID"]) && $loginDetails["userID"] > 0) {
//error_log("cscm logged in via portalauth, redirect to index");

		// set a cookie to be used on session timeout to decide where to send the user on logout. NOTE: only good for the session
//		$loginSrc = array("src" => "portal", "user" => $loginDetails["username"], "type" => $loginDetails["type"]);
//		setcookie($CUSTOMERURL. "_login_src", json_encode($loginSrc));

		// load the user's credentials and prepare the session
//		loadCredentials($loginDetails["userID"]);
//		loadDisplaySettings();

		// set the sessiondata values that were already set during login but would be overwritten by php get/put session
		//$_SESSION['customerid'] = 0; // TODO support customerurl input
		$_SESSION['userid'] = $loginDetails["userID"];
		$_SESSION['portaluserid'] = $loginDetails["userID"];

		// send to the index.php page to get them properly loaded.
		$redirectLoc = "index.php";
	} else {
//error_log("portallogin return, no details, redir to ... um");
		$portalAuthLocation = getPortalAuthLocation();
		if ($portalAuthLocation != false) {
			// if we get a valid location back from authserver for the commsuite app's login form, send the user there with an error code
			$redirectLoc = $portalAuthLocation["url"]. $portalAuthLocation["login"]. "#nocommsuiteuser";
		} else {
			// Nothing much we can do, authserver doesn't know where to send them! Just go to index.php
			$redirectLoc = "index.php";
		}
	}
} else {
//error_log("portallogin create new anonymous session");
	// create a brand new session
	newSession();
	doStartSession();
	$http = ($_SERVER["HTTPS"]?"https://":"http://");
	$redirectLoc = getPortalAuthAuthRequestTokenUrl($http. $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI']. "?is_return");
}
redirect($redirectLoc);
?>