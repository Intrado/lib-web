<?
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

$SETTINGS = parse_ini_file("managersettings.ini.php",true);

date_default_timezone_set("US/Pacific"); //to keep php from complaining. TODO move to manager settings.

apache_note("CS_APP","manager"); //for logging
apache_note("CS_CUST","_Manager_"); //for logging

require_once("../inc/db.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBMappedObjectHelpers.php");
require_once("../inc/DBRelationMap.php");
require_once("managerutils.inc.php");
require_once("dbmo/authserver/AspAdminUser.obj.php");
require_once("../inc/locale.inc.php");

global $_dbcon;
$_dbcon = DBConnect($SETTINGS['db']['host'], $SETTINGS['db']['user'], $SETTINGS['db']['pass'], $SETTINGS['db']['db']);

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


function SetUpASPDB(){
    global $SETTINGS, $ASPCALLSDBCONN;

	$ASPCALLSDBCONN = null;
	if (isset($SETTINGS['aspcalls']) && is_array($SETTINGS['aspcalls'])) {
		$db = $SETTINGS['aspcalls'];
		$ASPCALLSDBCONN = DBConnect($db['host'], $db['user'], $db['pass'], $db['db']);
	}
	return $ASPCALLSDBCONN;
}

function SetupASPReportsDB() {
	global $SETTINGS, $ASPREPORTSDBCONN;

	$ASPREPORTSDBCONN = null;
	if (isset($SETTINGS['aspreports']) && is_array($SETTINGS['aspreports'])) {
		$db = $SETTINGS['aspreports'];
		$ASPREPORTSDBCONN = DBConnect($db['host'], $db['user'], $db['pass'], $db['db']);
	}
	return $ASPREPORTSDBCONN;
}
?>