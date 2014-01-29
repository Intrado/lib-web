<?

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

require_once("inc/appserver.inc.php");
// load the thrift api requirements.
$thriftdir = 'Thrift';
require_once("{$thriftdir}/Base/TBase.php");
require_once("{$thriftdir}/Protocol/TProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocolAccelerated.php");
require_once("{$thriftdir}/Transport/TTransport.php");
require_once("{$thriftdir}/Transport/TSocket.php");
require_once("{$thriftdir}/Transport/TBufferedTransport.php");
require_once("{$thriftdir}/Transport/TFramedTransport.php");
require_once("{$thriftdir}/Exception/TException.php");
require_once("{$thriftdir}/Exception/TProtocolException.php");
require_once("{$thriftdir}/Exception/TApplicationException.php");
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/messagelink/Types.php");
require_once("{$thriftdir}/packages/messagelink/MessageLink.php");


session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if (isset($_GET['code'])) {
	$code = $_GET['code'];
}

list($appserverprotocol, $appservertransport) = initMessageLinkApp();
if($appserverprotocol == null || $appservertransport == null) {
	error_log("Cannot use AppServer");
	exit(0);
}
$attempts = 0;
while(true) {
	try {
		$client = new MessageLinkClient($appserverprotocol);
		// Open up the connection
		$appservertransport->open();
		$logo = $client->getLogo($code);
		$data = $logo->data;
		$contenttype = $logo->contenttype;
		$appservertransport->close();
		break;
	} catch (messagelink_MessageLinkCodeNotFoundException $e) {
		error_log("Unable to find the messagelinkcode: " . $code);
		$data = file_get_contents("img/logo_small.gif");
		$contenttype = "image/gif";
		$appservertransport->close();
		break;
	} catch (TException $tx) {
		$attempts++;
		// a general thrift exception, like no such server
		error_log("getLogo: Exception Connection to AppServer (" . $tx->getMessage() . ")");
		$appservertransport->close();
		if($attempts > 2) {
			error_log("getLogo: Failed 3 times to get content from appserver");
			$data = file_get_contents("img/logo_small.gif");
			$contenttype = "image/gif";
			break;
		}
	}
}
header("Content-type: " . $contenttype);
header("Pragma: ");
header("Cache-Control: private");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
echo $data;

?>
