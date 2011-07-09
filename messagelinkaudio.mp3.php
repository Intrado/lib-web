<?

if(!isset($_GET['code'])) {
	exit();
}

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

require_once("inc/appserver.inc.php");
require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/packages/messagelink/MessageLink.php';

list($appserverprotocol, $appservertransport) = initMessageLinkApp();
if($appserverprotocol == null || $appservertransport == null) {
	error_log("Can not use AppServer");
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
			if(isset($_GET['partnum'])) {
				$messagepartnumber = $_GET['partnum'];
				$audiopart = $client->getAudioPart($code,$messagepartnumber);
				header("HTTP/1.0 200 OK");
				header("Content-Type: " . $audiopart->contenttype);
				header("Content-disposition: attachment; filename=message.mp3");
				header('Pragma: private');
				header('Cache-control: private, must-revalidate');
				header("Content-Length: " . strlen($audiopart->data));
				header("Connection: close");
				echo $audiopart->data;
			} else {		
				$audiofull = $client->getAudioFull($code);
				header("HTTP/1.0 200 OK");
				if (isset($_GET['download']))
					header('Content-type: application/x-octet-stream');
				else {
					header("Content-Type: " . $audiofull->contenttype);
				}
				header("Content-disposition: attachment; filename=message.mp3");
				header('Pragma: private');
				header('Cache-control: private, must-revalidate');
				header("Content-Length: " . strlen($audiofull->data));
				header("Connection: close");
				echo $audiofull->data;
			}
		} catch (messagelink_MessageLinkCodeNotFoundException $e) {
			echo "The requested information was not found. The message you are looking for does not exist or has expired.";
			error_log("Unable to find the messagelinkcode: " . $code + " Attempt: " . $attempts);
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
