<?
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");

session_start();
if(!isset($isasplogin)){
	if(!isset($_SESSION["aspadminuserid"]))
		redirect("./?logout=1");
}

?>