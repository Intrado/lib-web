<?

$SETTINGS = parse_ini_file("parentportalsettings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];
$INBOUND_ACTIVATION = false; // set only when customer allows phone activation (bypass activation codes for contact associations)
$INBOUND_MSGCALLBACK = false; // set only when customer has callback feature

require_once("XML/RPC.php");
require_once("authportal.inc.php");
require_once("portalsessionhandler.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");

if(!isset($ppNotLoggedIn)){
	// we are logged in

	$logout = "?logout=1";
	if (isset($_SESSION['customerurl'])) {
		$logout += "&u=".urlencode($_SESSION['customerurl']);
	}
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])){
		//index page will redirect to ssl
		redirect("index.php".$logout);
	}
	doStartSession();
	if(!isset($_SESSION["portaluserid"])){
		$_SESSION['lasturi'] = $_SERVER['REQUEST_URI'];
		redirect("./".$logout);
    } else {
    	$result = portalGetPortalUser();
    	if($result['result'] == ""){
	    	$_SESSION['portaluser'] = $result['portaluser'];
			apache_note("CS_USER",urlencode($_SESSION['portaluser']['portaluser.username'])); //for logging
    	} else {
    		redirect("./".$logout);
    	}
    }

	if (isset($_SESSION['customerid'])) {		
		apache_note("CS_CUST",urlencode($_SESSION['customerid'])); //for logging

		// store the customer's toll free inbound number
		$n = QuickQuery("select value from setting where name='inboundnumber'");

	    // find if this customer allows phone activation
	    if (QuickQuery("select value from setting where name='portalphoneactivation'") == "1") {
 	    	if ($n != false && $n != "")
    			$INBOUND_ACTIVATION = $n;
	    }
	    // find if this customer has message callback
	    if (QuickQuery("select value from setting where name='_hascallback'") == "1") {
 	    	if ($n != false && $n != "")
    			$INBOUND_MSGCALLBACK = $n;
	    }
    }
} else {
	// we are not logged in
}

// load customer/user locale 
require_once("../inc/locale.inc.php");

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
	$appendcustomerurl = "?"; // empty url params is ok, safe to append &locale or other options even if no u=customerurl
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
