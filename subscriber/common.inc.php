<?

$SETTINGS = parse_ini_file("subscribersettings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

//get the customer URL
if ($IS_COMMSUITE) {
	$CUSTOMERURL = "default";
	$BASEURL = "";
} /*CSDELETEMARKER_START*/ else {
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
	$BASEURL = "/$CUSTOMERURL";
} /*CSDELETEMARKER_END*/

$INBOUND_MSGCALLBACK = false; // set only when customer has callback feature


require_once("XML/RPC.php");
require_once("authsubscriber.inc.php");
require_once("subscribersessionhandler.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");

require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../obj/Validator.obj.php");


if (!isset($isNotLoggedIn)) {
	// we are logged in

	$logout = "?logout=1";
	if (isset($_SESSION['customerurl'])) {
		$logout += "&u=".urlencode($_SESSION['customerurl']);
	}
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		//index page will redirect to ssl
		redirect("index.php".$logout);
	}
	$sid = false;
	if (isset($_SESSION['subscriberid']))
		$sid = $_SESSION['subscriberid'];
		
	doStartSession($sid);
	
	if (!isset($_SESSION['subscriberid'])) {
		$_SESSION['lasturi'] = $_SERVER['REQUEST_URI'];
		redirect("./".$logout);
    }
    
	// store the customer's toll free inbound number
	$n = QuickQuery("select value from setting where name='inboundnumber'");
	$INBOUND_ACTIVATION = $n;
	
    // find if this customer has message callback
    if (QuickQuery("select value from setting where name='_hascallback'") == "1") {
	    	if ($n != false && $n != "")
   			$INBOUND_MSGCALLBACK = $n;
    }
} // else we are not logged in

// load subscriber locale 
require_once("../inc/locale.inc.php");

?>