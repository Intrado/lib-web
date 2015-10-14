<?
/**
 * Handle login requests via portalauth through AuthServer
 *
 * User: nrheckman
 * Date: 8/13/12
 * Time: 9:32 AM
 */

// set a header for the privacy policy so IE will accept the session cookie
header('P3P: policyref="/w3c/p3p.xml", CP="NOI DSP COR CUR ADM DEV OUR BUS"');

$isindexpage = true;
require_once("inc/common.inc.php");
require_once("obj/User.obj.php");
require_once("obj/Access.obj.php");

if (isset($_REQUEST["is_return"])) {
	doStartSession();
	// useing the access token, request that authserver create a session for whoever is logged into portal
	$loginDetails = loginViaPortalAuth($CUSTOMERURL, $_SERVER["REMOTE_ADDR"]);
	if ($loginDetails && isset($loginDetails["userID"]) && $loginDetails["userID"] > 0) {
		// set a cookie to be used on session timeout to decide where to send the user on logout. NOTE: only good for the session
		$loginSrc = array("src" => "portal", "user" => $loginDetails["username"], "type" => $loginDetails["type"]);
		setcookie($CUSTOMERURL. "_login_src", json_encode($loginSrc));

		// load the user's credentials and prepare the session
		loadCredentials($loginDetails["userID"]);
		loadDisplaySettings();

		// send to the index.php page to get them properly loaded.
		$redirectLoc = "index.php";
	} else {
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
	// create a brand new session
	newSession();
	doStartSession();
	$redirectLoc = getPortalAuthAuthRequestTokenUrl('https://' . $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI']. "?is_return");
}
redirect($redirectLoc);
?>
