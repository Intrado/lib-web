<?
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];


//get the customer URL

if ($IS_COMMSUITE) {
	$CUSTOMERURL = "default";
} /*CSDELETEMARKER_START*/ else {
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
} /*CSDELETEMARKER_END*/


require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("XML/RPC.php");
require_once("../inc/auth.inc.php");
require_once("../inc/sessionhandler.inc.php");
require_once("../inc/utils.inc.php");


// we just want to confirm that someone is logged in to this customer
doStartSession();

if (!isset($_SESSION['user']))
	header("Location: ..");
else
	header("Location: html/CommSuite_User_Guide.htm");

?>