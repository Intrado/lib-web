<?
/**
 * Handle login requests via portalauth through AuthServer
 *
 * User: nrheckman
 * Date: 8/13/12
 * Time: 9:32 AM
 */
$isindexpage = true;
require_once("inc/common.inc.php");
require_once("inc/DBMappedObject.php");
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
	$http = ($_SERVER["HTTPS"]?"https://":"http://");
	$redirectLoc = getPortalAuthAuthRequestTokenUrl($http. $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI']. "?is_return");
}

$TITLE = "Portal Authentication Login";
include_once("logintop.inc.php");
?>

<div><h2>Please wait while an attempt is made to log you in...</h2></div>

<script type="text/javascript">
	!function ($) {
		window.location = "<?=addslashes($redirectLoc)?>";
	}(window.jQuery);
</script>
<script type="text/javascript" src="script/jquery.1.7.2.min.js"></script>

<?
include_once("loginbottom.inc.php");
?>
