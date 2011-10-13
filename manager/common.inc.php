<?

$SETTINGS = parse_ini_file("managersettings.ini.php",true);

date_default_timezone_set("US/Pacific"); //to keep php from complaining. TODO move to manager settings.

apache_note("CS_APP","manager"); //for logging
apache_note("CS_CUST","_Manager_"); //for logging

$dsn = 'mysql:dbname='.$SETTINGS['db']['db'].';host='.$SETTINGS['db']['host'];

global $_dbcon;
try {
	$_dbcon = new PDO($dsn, $SETTINGS['db']['user'], $SETTINGS['db']['pass']);
	$_dbcon->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
	$_dbcon->query($setcharset);
} catch (PDOException $e) {
	error_log("PDO Exception : ".$e->getMessage());
}

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");
require_once("managerutils.inc.php");
require_once("dbmo/authserver/AspAdminUser.obj.php");
require_once("../inc/locale.inc.php");

session_start();
if(!isset($isasplogin)){

	if (isset($SETTINGS['feature']['force_ssl']) && $SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect("index.php"); //the index page will redirect to https
	}

	if(!isset($_SESSION["aspadminuserid"]))
		redirect("./?logout=1&reason=nosession");
		
	if (time() > $_SESSION['expiretime'])
		redirect("./?logout=1&reason=timeout");
		
	$MANAGERUSER = new AspAdminUser($_SESSION['aspadminuserid']);
	
	apache_note("CS_USER",urlencode($MANAGERUSER->login)); //for logging

	//check to make sure the url component is the username
	$expectedusername = substr($_SERVER["SCRIPT_NAME"],1);
	$expectedusername = strtolower(substr($expectedusername,0,strpos($expectedusername,"/")));
	if ($MANAGERUSER->login != $expectedusername) {
		redirect("index.php?logout=1&reason=badurl");
	}
	
	//refresh idle timer
	$autologoutminutes = isset($SETTINGS['feature']['autologoutminutes']) ? $SETTINGS['feature']['autologoutminutes'] : 30;
	$_SESSION['expiretime'] = time() + 60*$autologoutminutes; //30 minutes
}

?>
