<?

$SETTINGS = parse_ini_file("parentportalsettings.ini.php",true);
$INBOUND_ACTIVATION = false; // set only when customer allows phone activation (bypass activation codes for contact associations)
$INBOUND_MSGCALLBACK = false; // set only when customer has callback feature

if (extension_loaded ('newrelic') && isset($SETTINGS['instrumentation']['newrelic_parentportal_id'])) {
	newrelic_set_appname($SETTINGS['instrumentation']['newrelic_parentportal_id']);
}

require_once("XML/RPC.php");
require_once("authportal.inc.php");
require_once("portalsessionhandler.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBMappedObjectHelpers.php");
require_once("../inc/DBRelationMap.php");

apache_note("CS_APP","cm"); //for logging

// SMK notes 2015-10-15 - there is an awkward flag at the top of index.php (and two other pages) which prevents this chunk
// of code from executing; it has to run before locale.inc.php loads because it sets up the session that locale depends on
if (! isset($ppNotLoggedIn)) {
	// we are logged in

	$logout = "?logout=1";
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])){
		//index page will redirect to ssl
		redirect("index.php".$logout);
	}
	doStartSession();
	if (! isset($_SESSION["portaluserid"])){
		$_SESSION['lasturi'] = $_SERVER['REQUEST_URI'];
		redirect("./".$logout);
	}

	$result = portalGetPortalUser();
	if (strlen($result['result']) > 0) {
		// Something other than a successful result (which would be empty...)
		redirect("./".$logout);
	}
	$_SESSION['portaluser'] = $result['portaluser'];
	apache_note("CS_USER",urlencode($_SESSION['portaluser']['portaluser.username'])); //for logging
	instrumentation_add_custom_parameter("portalUsername", $_SESSION['portaluser']['portaluser.username']);
	instrumentation_add_custom_parameter("portalUserId", $_SESSION['portaluserid']);
	instrumentation_add_custom_parameter("userSession", hash("sha256", session_id() . $_SESSION['portaluserid']));

	if (isset($_SESSION['customerid'])) {		
		apache_note("CS_CUST",urlencode($_SESSION['customerid'])); //for logging
		instrumentation_add_custom_parameter("customerId", $_SESSION['customerid']);

		// store the customer's toll free inbound number
		$n = QuickQuery("select value from setting where name='inboundnumber'");

		// find if this customer allows phone activation
		if (QuickQuery("select value from setting where name='portalphoneactivation'") == "1") {
			if ($n != false && $n != "") $INBOUND_ACTIVATION = $n;
		}
		// find if this customer has message callback
		if (QuickQuery("select value from setting where name='_hascallback'") == "1") {
			if ($n != false && $n != "") $INBOUND_MSGCALLBACK = $n;
		}
		instrumentation_add_custom_parameter("inboundActivation", $INBOUND_ACTIVATION ? "yes":"no");
		instrumentation_add_custom_parameter("inboundMsgCallback", $INBOUND_MSGCALLBACK ? "yes":"no");
	}
}

// load customer/user locale 
require_once("../inc/locale.inc.php");
instrumentation_add_custom_parameter("locale", $LOCALE);

/*
 * return string to append on url redirects, may contain the customerurl option
 */
function getAppendCustomerUrl() {
	// find optional customerurl either by url param or cookie
	$customerurl = false;
	if (isset($_GET['u'])) {
		$customerurl = $_GET['u'];
	} else if (isset($_COOKIE['customerurl'])) {
		$customerurl = $_COOKIE['customerurl'];
	}

	// pass along the customerurl (used by phone activation feature to find a customer without any existing associations)
	$appendcustomerurl = "";
	if ($customerurl) {
		$appendcustomerurl = "?u=".urlencode($customerurl);
		// if cookie not set, or already set but different, then set it
		if (!isset($_COOKIE['customerurl']) ||
			(isset($_COOKIE['customerurl']) && $_COOKIE['customerurl'] != $customerurl)) {
				setcookie('customerurl', $customerurl, time()+60*60*24*365);
		}
	}
	return $appendcustomerurl;
}

?>
