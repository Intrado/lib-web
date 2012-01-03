<?
setlocale(LC_ALL, 'en_US.UTF-8');

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

//get the customer URL
$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

apache_note("CS_APP","cisco"); //for logging
apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("XML/RPC.php");
require_once("../inc/auth.inc.php");
require_once("../inc/sessionhandler.inc.php");

require_once("../inc/securityhelper.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Rule.obj.php"); //for search and sec profile rules


//reconstruct the full URL. IP phones don't handle relative links.

$addr = $_SERVER['SERVER_NAME'] == "" ? $_SERVER['SERVER_ADDR'] : $_SERVER['SERVER_NAME'];
$URL = "http://" . $addr . ":" . $_SERVER['SERVER_PORT'] . substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],"/"));

if (!isset($isindexpage) || !$isindexpage) {
	doStartSession();
	if (!isset($_SESSION['user'])) {
		header("Location: $URL/index.php?logout=1");
		exit();
	} else {
		$USER = &$_SESSION['user'];
		$USER->refresh();
		$USER->optionsarray = false; /* will be reconstructed if needed */
		
		apache_note("CS_USER",urlencode($USER->login)); //for logging

		$ACCESS = &$_SESSION['access'];
		$ACCESS->loadPermissions(true);

		if (!$USER->enabled || !$USER->authorize('loginphone')) {
			header("Location: $URL/index.php?logout=1");
			exit();
		}
	}
}

if (isset($_SERVER['HTTP_X_CISCOIPPHONEMODELNAME']))
	$_SESSION['HTTP_X_CISCOIPPHONEMODELNAME'] = $_SERVER['HTTP_X_CISCOIPPHONEMODELNAME'];
else
	$_SERVER['HTTP_X_CISCOIPPHONEMODELNAME'] = isset($_SESSION['HTTP_X_CISCOIPPHONEMODELNAME']) ? $_SESSION['HTTP_X_CISCOIPPHONEMODELNAME'] : null;

/*
$fp = fopen("foo.txt","w");
ob_start();
var_dump($GLOBALS);
fwrite($fp,ob_get_clean());
fclose($fp);
*/

$PHONE_FEATURES = array(
"CiscoIPPhoneText" 				=> array("7905" => true, 	"7912" => true, 	"7920" => true, 	"7940" => true,		"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneMenu" 				=> array("7905" => true, 	"7912" => true, 	"7920" => true, 	"7940" => true,		"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneIconMenu" 			=> array("7905" => true, 	"7912" => true, 	"7920" => true, 	"7940" => true, 	"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneDirectory"			=> array("7905" => true, 	"7912" => true, 	"7920" => true, 	"7940" => true, 	"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneInput" 			=> array("7905" => true, 	"7912" => true, 	"7920" => true, 	"7940" => true, 	"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneImage" 			=> array("7905" => false,	"7912" => false,	"7920" => true, 	"7940" => true, 	"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneImageFile" 		=> array("7905" => false,	"7912" => false,	"7920" => false, 	"7940" => false,	"7941" => false,	"7960" => false,	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneGraphicMenu"		=> array("7905" => false,	"7912" => false,	"7920" => true, 	"7940" => true, 	"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneGraphicFileMenu"	=> array("7905" => false,	"7912" => false,	"7920" => false, 	"7940" => false,	"7941" => false,	"7960" => false,	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneExecute"			=> array("7905" => true, 	"7912" => true, 	"7920" => true, 	"7940" => true, 	"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneError"				=> array("7905" => true, 	"7912" => true, 	"7920" => true, 	"7940" => true, 	"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true),
"CiscoIPPhoneResponse"			=> array("7905" => true, 	"7912" => true, 	"7920" => true, 	"7940" => true, 	"7941" => true, 	"7960" => true, 	"7970" => true, 	"IP Communicator" => true)
);

function doesSupport ($feature) {
	global $PHONE_FEATURES;

	if (strpos($_SERVER['HTTP_ACCEPT'], "x-CiscoIPPhone/*") !== false)
		return true;
	else if (strpos($_SERVER['HTTP_ACCEPT'], "x-CiscoIPPhone/" . $feature) !== false)
		return true;

	if (strpos($_SERVER['HTTP_X_CISCOIPPHONEMODELNAME'],"Communicator") !== false)
		$model = "IP Communicator";
	else
		$model = preg_replace("/[^0-9]/","",$_SERVER['HTTP_X_CISCOIPPHONEMODELNAME']);

	return $PHONE_FEATURES[$feature][$model];
}

function isModel ($model) {
	if (strpos($_SERVER['HTTP_X_CISCOIPPHONEMODELNAME'],$model) !== false)
		return true;
}

require_once('../inc/locale.inc.php');
?>
