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

	if (isset($SETTINGS['feature']['force_ssl']) && $SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect("index.php"); //the index page will redirect to https
	}

	if(!isset($_SESSION["aspadminuserid"]))
		redirect("./?logout=1");

	//check to make sure the url component is the username
	$expectedusername = substr($_SERVER["SCRIPT_NAME"],1);
	$expectedusername = strtolower(substr($expectedusername,0,strpos($expectedusername,"/")));
	$username = QuickQuery("select login from aspadminuser where id=" . $_SESSION["aspadminuserid"]);
	if ($username != $expectedusername) {
		redirect("index.php?logout=1");
	}

}

?>