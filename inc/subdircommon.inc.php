<?

setlocale(LC_ALL, 'en_US.UTF-8');

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
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

apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("XML/RPC.php");
require_once("../inc/auth.inc.php");
require_once("../inc/sessionhandler.inc.php");

require_once("../inc/utils.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../obj/Rule.obj.php"); //for search and sec profile rules

if (!isset($isindexpage) || !$isindexpage) {
	doStartSession();
	//force ssl?
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		exit();
	}

	if (!isset($_SESSION['user']) || !isset($_SESSION['access'])) {
		$_SESSION['lasturi'] = $_SERVER['REQUEST_URI'];
		exit();
	} else {
		$USER = &$_SESSION['user'];
		$USER->refresh();
		$USER->optionsarray = false; /* will be reconstructed if needed */
		
		apache_note("CS_USER",urlencode($USER->login)); //for logging

		$ACCESS = &$_SESSION['access'];
		$ACCESS->loadPermissions(true);

		if (!$USER->enabled || $USER->deleted || !$USER->authorize('loginweb')) {
			exit();
		}
	}
}

// load customer/user locale 
//this needs the USER object to already be loaded
require_once("../inc/locale.inc.php");

?>
