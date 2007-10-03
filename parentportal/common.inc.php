<?

$SETTINGS = parse_ini_file("parentportalsettings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("XML/RPC.php");
require_once("authportal.inc.php");
require_once("portalsessionhandler.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");

if(!isset($ppNotLoggedIn)){
	// we are logged in

/*
	TODO:unsure if needed
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])){
		redirect("https://" . $_SERVER["SERVER_NAME"] . "/junk/parentportal/index.php?logout=1");
	}
*/
	doStartSession();
	if(!isset($_SESSION["portaluserid"])){
		$_SESSION['lasturi'] = $_SERVER['REQUEST_URI'];
		redirect("./?logout=1");
    }
} else {
	// we are not logged in
}
//TODO:Remove once api for portal user fields is complete
$_SESSION['portaluser']= array();
$_SESSION['portaluser']['firstname'] = "test";
$_SESSION['portaluser']['lastname'] = "test2";
$_SESSION['portaluser']['username'] = "test3";
$_SESSION['portaluser']['zipcode'] = "99999";

?>