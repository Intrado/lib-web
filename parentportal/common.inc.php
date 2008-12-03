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


	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])){
		//index page will redirect to ssl
		redirect("index.php?logout=1");
	}
	doStartSession();
	if(!isset($_SESSION["portaluserid"])){
		$_SESSION['lasturi'] = $_SERVER['REQUEST_URI'];
		redirect("./?logout=1");
    } else {
    	$result = portalGetPortalUser();
    	if($result['result'] == ""){
	    	$_SESSION['portaluser'] = $result['portaluser'];
    	} else {
    		redirect("./?logout=1");
    	}
    }

	if (isset($_SESSION['customerid'])) {
		// store the customer's toll free inbound number
		$n = QuickQuery("select value from setting where name='inboundnumber'");

	    // find if this customer allows phone activation
	    if (QuickQuery("select value from setting where name='cmphoneactivation'") == "1") {
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

?>