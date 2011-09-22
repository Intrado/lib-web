<?
/* Serves up properties files from a cvs repository
 * The client will be validated via ip/hostname before a file is provided
 * Checks authserver database to see which runmode property file should be distributed
 *
 * The client MUST include the following arguments
 *   host=<client hostname>
 *   service=<service name>
 *
 * - Nickolas Heckman
 */

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("Server.obj.php");
require_once("Service.obj.php");
require_once("CvsServer.obj.php");

// client request MUST contain a hostname and service name
$hostname = ($_GET['host']?$_GET['host']:false);
$servicename = ($_GET['service']?$_GET['service']:false);

// remote ip must resolve to the same hostname in the request
if (!$hostname || gethostbyname($hostname) != $_SERVER['REMOTE_ADDR']) {
	header("HTTP/1.0 404 Not Found");
	echo "<h1>This client is not authorized to view requested content.</h1>";
	
	error_log("getprops refusing to serve client " . $_SERVER['REMOTE_ADDR'] . " expected " . gethostbyname($hostname) . " ($hostname)");
	
	exit;
}

$SETTINGS = parse_ini_file("managersettings.ini.php",true);

global $_dbcon;
try {
	$dsn = 'mysql:dbname='.$SETTINGS['db']['db'].';host='.$SETTINGS['db']['host'];
	$_dbcon = new PDO($dsn, $SETTINGS['db']['user'], $SETTINGS['db']['pass']);
	$_dbcon->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
	$_dbcon->query($setcharset);
} catch (PDOException $e) {
	error_log("PDO Exception : ".$e->getMessage());
}

// get the current runmode and the service definintion for it
$serverid = QuickQuery("select id from server where hostname = ?", false, array($hostname));
$serviceid = false;
if ($serverid) {
	$server = new Server($serverid);
	if ($server->runmode != 'testing') {
		// look up most specific service definition first. otherwise check for an "all" run mode
		$serviceid = QuickQuery("select id from service where serverid = ? and type = ? and runmode = ?", false, array($server->id, $servicename, $server->runmode));
		if (!$serviceid)
			$serviceid = QuickQuery("select id from service where serverid = ? and type = ? and runmode = 'all'", false, array($server->id, $servicename));
		$service = new Service($serviceid);
	}
}
if (!$serverid || !$serviceid) {
	header("HTTP/1.0 404 Not Found");
	echo "<h1>Failed to find server/service definition for this client request.</h1>";
	exit;
}

$cvs = new CvsServer($SETTINGS['servermanagement']['cvsurl']);
$file = $cvs->co("{$server->hostname}/{$service->runmode}/{$service->type}/service.properties");
if ($file) {
	readfile($file);
} else {
	header("HTTP/1.0 404 Not Found");
	echo "<h1>Requested properties file was not found.</h1>";
}
$cvs->cleanupTempFiles();
?>
