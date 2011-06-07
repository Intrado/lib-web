<? 

/////////////////////////////////////
// AppServer MessageLink service

global $appserversocket;
global $appservertransport;
global $appserverprotocol;

try {
	if (isset($SETTINGS['appserver']) && isset($SETTINGS['appserver']['host'])) {
		$appserverhost = explode(":",$SETTINGS['appserver']['host']);
		if (count($appserverhost) == 2) {
			$appserversocket = new TSocket($appserverhost[0], $appserverhost[1]);
			//$appserversocket->setDebug(true);
			if (isset($SETTINGS['appserver']['timeout'])) {
				$appserversocket->setRecvTimeout($SETTINGS['appserver']['timeout']);
			} else {
				$appserversocket->setRecvTimeout(5500);
			}
			$appservertransport = new TFramedTransport($appserversocket);
			//$appserverprotocol = new TBinaryProtocol($appservertransport);
			
			$appserverprotocol = new TBinaryProtocolAccelerated($appservertransport);
			
		}
	}
} catch (TException $tx) {
	// a general thrift exception, like no such server
	error_log("Exception Connection to AppServer MessageLink service (" . $tx->getMessage() . ")");
}

//////////////////////////////////////////////////////////
// AppServer CommSuite service

global $appserverCommsuiteSocket;
global $appserverCommsuiteTransport;
global $appserverCommsuiteProtocol;

try {
	if (isset($SETTINGS['appserver_commsuite']) && isset($SETTINGS['appserver_commsuite']['host'])) {
		$appserverhost = explode(":",$SETTINGS['appserver_commsuite']['host']);
		if (count($appserverhost) == 2) {
			$appserverCommsuiteSocket = new TSocket($appserverhost[0], $appserverhost[1]);
			//$appserverCommsuiteSocket->setDebug(true);
			if (isset($SETTINGS['appserver_commsuite']['timeout'])) {
				$appserverCommsuiteSocket->setRecvTimeout($SETTINGS['appserver_commsuite']['timeout']);
			} else {
				$appserverCommsuiteSocket->setRecvTimeout(5500);
			}
			$appserverCommsuiteTransport = new TFramedTransport($appserverCommsuiteSocket);
			//$appserverCommsuiteProtocol = new TBinaryProtocol($appserverCommsuiteTransport);
			
			$appserverCommsuiteProtocol = new TBinaryProtocolAccelerated($appserverCommsuiteTransport);
			
		}
	}
} catch (TException $tx) {
	// a general thrift exception, like no such server
	error_log("Exception Connection to AppServer CommSuite service (" . $tx->getMessage() . ")");
}




?>