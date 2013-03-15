<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");

function getLoginUrl() {
	$portalAuthLocation = getPortalAuthLocation($params = "");
	if ($portalAuthLocation != false) {
		// if we get a valid location back from portalserver for the cm app's login form, send the user there to login again
		return $portalAuthLocation["url"]. $portalAuthLocation["login"]. $params;
	}
	// Nothing much we can do, portalserver doesn't know where to send them! Just go to unauthorized.php
	return "unauthorized.php";
}

// did they logout?
if (isset($_GET['logout'])) {
	doStartSession(); // start the session to get the id
	portalputSessionData(session_id(), ""); // write empty data to flush the user

	@session_destroy();

	// check for the "cm_login_src" cookie. If this exists, send the user to the appropriate login location
	if (isset($_COOKIE["cm_login_src"]) && $_COOKIE["cm_login_src"]) {
		$loginDetails = json_decode($_COOKIE["cm_login_src"], true);
		// clear the cookie
		setcookie("cm_login_src", "");

		$src = $loginDetails["src"];
		$user = $loginDetails["user"];
		$type = $loginDetails["type"];

		// if they came from powerschool, close the window
		if ($src == "portal" && $type == "powerschool") {
			echo '<script type="text/javascript">
				window.close();
			</script>';
			exit;
		} else {
			$redirectLoc = getLoginUrl();
		}
	}
} else {
	// forward any params to portalauth
	$params = http_build_query($_GET);

	if (isset($_REQUEST["is_return"])) {
		doStartSession(); // start session to send sessionid to login
		// useing the access token, request that authserver create a session for whoever is logged into portal
		$loginDetails = loginViaPortalAuth();
		if ($loginDetails && isset($loginDetails["userID"]) && $loginDetails["userID"] > 0) {

			// set a cookie to be used on session timeout to decide where to send the user on logout. NOTE: only good for the session
			$loginSrc = array("src" => "portal", "user" => $loginDetails["username"], "type" => $loginDetails["type"]);
			setcookie("cm_login_src", json_encode($loginSrc));

			// set the sessiondata values that were already set during login but would be overwritten by php get/put session
			$_SESSION['userid'] = $loginDetails["userID"];
			$_SESSION['portaluserid'] = $loginDetails["userID"];
			$_SESSION['userlogintype'] = $loginDetails["type"]; // "powerschool" or "local"

			$_SESSION['colorscheme']['_brandtheme'] = "3dblue";
			$_SESSION['colorscheme']['_brandprimary'] = "26477D";
			$_SESSION['colorscheme']['_brandtheme1'] = "89A3CE";
			$_SESSION['colorscheme']['_brandtheme2'] = "89A3CE";
			$_SESSION['colorscheme']['_brandratio'] = ".3";

			$redirpage = isset($_SESSION['lasturi']) ? $_SESSION['lasturi'] : 'choosecustomer.php' . getAppendCustomerUrl();
			unset($_SESSION['lasturi']);
			redirect($redirpage);
		} else {
			$redirectLoc = getLoginUrl($params);
		}
	} else {
		// create a brand new session
		newSession();
		doStartSession();
		$http = ($_SERVER["HTTPS"]?"https://":"http://");
		$port = $_SERVER["SERVER_PORT"];
		if ($port == "80" || $port == "443")
			$port = "";
		else
			$port = ":$port";
		$redirectLoc = getPortalAuthAuthRequestTokenUrl($http. $_SERVER['SERVER_NAME']. $port. $_SERVER['REQUEST_URI']. "?is_return");
	}
}
redirect($redirectLoc);
?>