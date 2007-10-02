<?

$SETTINGS = parse_ini_file("parentportalsettings.ini.php",true);
$IS_COMMSUITE = false;
$_dbcon = mysql_connect("localhost", "root", "");
mysql_select_db("authserver");

require_once("XML/RPC.php");
require_once("authportal.inc.php");

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");
require_once("PortalUser.obj.php");

if(!isset($ppNotLoggedIn)){
/*
	TODO:unsure if needed
	if (!isset($_SERVER["HTTPS"])){
		redirect("https://" . $_SERVER["SERVER_NAME"] . "/junk/parentportal/index.php?logout=1");
	}
*/
	if(!isset($_SESSION["portaluserid"]))
		redirect("./?logout=1");
}
?>