<?

if(!isset($_GET['code'])) {
	exit();
}

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
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/messagelink/Types.php");
require_once("{$thriftdir}/packages/messagelink/MessageLink.php");

list($appserverprotocol, $appservertransport) = initMessageLinkApp();
if($appserverprotocol == null || $appservertransport == null) {
	error_log("Cannot use AppServer");
	exit();
}
$attempts = 0;
while(true) {
	try {
		$client = new MessageLinkClient($appserverprotocol);
		
		// Open up the connection
		$appservertransport->open();
		
		$code = $_GET['code'];
		
		try {
			// Get either the part or the full message
			$audio = isset($_GET['partnum'])?$client->getAudioPart($code,$_GET['partnum']):$client->getAudioFull($code);
			header("HTTP/1.0 200 OK");
			header("Content-Type: $audio->contenttype");
			if (isset($_GET['download'])) {
				header("Content-disposition: attachment; filename=message.mp3");
			}
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
			header("Content-Length: " . strlen($audio->data));
			header("Connection: close");
			echo $audio->data;
		} catch (messagelink_MessageLinkCodeNotFoundException $e) {
			echo "The requested information was not found. The message you are looking for does not exist or has expired.";
			error_log("Unable to find the messagelinkcode: " . $code . " Attempt: " . $attempts);
		}
		// And finally, we close the thrift connection
		$appservertransport->close();
		break;
	} catch (TException $tx) {
		$attempts++;
		// a general thrift exception, like no such server
		
		error_log((isset($_GET['partnum'])?"getAudioPart":"getAudioFull") .
		 ": Exception Connection to AppServer (" . $tx->getMessage() . ") Attempt: " . $attempts);
		$appservertransport->close();
		if($attempts > 2) {
			header("HTTP/1.0 500 Internal Server Error");
			
			echo "An error occurred trying to generate the preview file. Please try again.";
			error_log((isset($_GET['partnum'])?"getAudioPart":"getAudioFull") .
						 ": Failed 3 times to get content from appserver");
			break;
		}
	}
}



?>
