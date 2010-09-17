<?

if(!isset($_GET['code'])) {
	exit();
}

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);
require_once("inc/thrift.inc.php");
require_once $GLOBALS['THRIFT_ROOT'].'/packages/messagelink/MessageLink.php';

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if($appserverprotocol == null || $appservertransport == null) {
	error_log("Can not use AppServer");
	exit();
}


try {
	
	$client = new MessageLinkClient($appserverprotocol);
	
	// Open up the connection
	$appservertransport->open();
	
	$code = $_GET['code'];
	
	if(isset($_GET['partnum'])) {
		
		$messagepartnumber = $_GET['partnum'];
		
		// Perform operation on the server
		$audiopart = $client->getAudioPart($code,$messagepartnumber);
		//$audiopart = $client->getAudioFull($code);
		
		if ($audiopart) {
			header("HTTP/1.0 200 OK");
			if (isset($_GET['download']))
				header('Content-type: application/x-octet-stream');
			else {
				header("Content-Type: " . $audiopart->contenttype);
			}
			header("Content-disposition: attachment; filename=message.mp3");
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
			header("Content-Length: " . strlen($audiopart->data));
			header("Connection: close");
			echo $audiopart->data;
		} else {
			echo "An error occurred trying to generate the preview file. Please try again.";
		}
		
	} else {
		// Perform operation on the server
		$audiofull = $client->getAudioFull($code);
		
		if ($audiofull) {
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
		} else {
			echo "An error occurred trying to generate the preview file. Please try again.";
		}
	}
	// And finally, we close the thrift connection
	$appservertransport->close();

} catch (TException $tx) {
	// a general thrift exception, like no such server
	echo "An error occurred trying to generate the preview file. Please try again.";
	error_log("Exception Connection to AppServer (" . $tx->getMessage() . ")");
	//echo "ThriftException: ".$tx->getMessage()."\r\n";
}



?>
