<?

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

require_once("inc/thrift.inc.php");
require_once $GLOBALS['THRIFT_ROOT'].'/packages/messagelink/MessageLink.php';


session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if (isset($_GET['code'])) {
	$code = $_GET['code'];
}

if($appserverprotocol == null || $appservertransport == null) {
	error_log("Can not use AppServer");
	exit(0);
}

try {
	$client = new MessageLinkClient($appserverprotocol);
	// Open up the connection
	$appservertransport->open();
	$logo = $client->getLogo($code);
	$data = $logo->data;
	$contenttype = $logo->contenttype;
	$appservertransport->close();
} catch (messagelink_MessageLinkCodeNotFoundException $e) {
	error_log("Unable to find the messagelinkcode: " . $code);
	$data = file_get_contents("img/logo_small.gif");
	$contenttype = "image/gif";
} catch (TException $tx) {
	// a general thrift exception, like no such server
	error_log("Exception Connection to AppServer (" . $tx->getMessage() . ")");
	$data = file_get_contents("img/logo_small.gif");
	$contenttype = "image/gif";
}
header("Content-type: " . $contenttype);
header("Pragma: ");
header("Cache-Control: private");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
echo $data;

?>
