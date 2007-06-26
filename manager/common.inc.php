<?
$SETTINGS = parse_ini_file("managersettings.ini.php",true);
$IS_COMMSUITE = false;

$_dbcon = mysql_connect($SETTINGS['db']['host'], $SETTINGS['db']['user'], $SETTINGS['db']['pass']);
mysql_select_db($SETTINGS['db']['db'], $_dbcon);


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