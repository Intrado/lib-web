<? 

// TODO move hlobals to better location
$GLOBALS['THRIFT_ROOT'] = 'thrift';
// Load up all the thrift stuff
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
//require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';

global $appserversocket;
global $appservertransport;
global $appserverprotocol;


try {
	if(isset($SETTINGS['appserver']) && isset($SETTINGS['appserver']['host'])) {
		$appserverhost = explode(":",$SETTINGS['appserver']['host']);
		if(count($appserverhost) == 2) {
			$appserversocket = new TSocket($appserverhost[0], $appserverhost[1]);
			//$appservertransport = new TFramedTransport($appserversocket);
			$appservertransport = new TBufferedTransport($appserversocket);
			$appserverprotocol = new TBinaryProtocolAccelerated($appservertransport);
			//$appserverprotocol = new TBinaryProtocol($appservertransport);
		}
	}
} catch (TException $tx) {
	// a general thrift exception, like no such server
	error_log("Exception Connection to AppServer (" . $tx->getMessage() . ")");
}





?>