<? 

/////////////////////////////////////
// AppServer MessageLink service

function initMessageLinkApp() {
	global $SETTINGS;
	$appservertransport = null;
	$appserverprotocol = null;
	
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
	return array($appserverprotocol,$appservertransport);
}
//////////////////////////////////////////////////////////
// AppServer CommSuite service

function initCommsuiteApp() {
	global $SETTINGS;
	$appservertransport = null;
	$appserverprotocol = null;
	
	try {
	
		if (isset($SETTINGS['appserver_commsuite']) && isset($SETTINGS['appserver_commsuite']['host'])) {
			$appserverhost = explode(":",$SETTINGS['appserver_commsuite']['host']);
			if (count($appserverhost) == 2) {
				$appserversocket = new TSocket($appserverhost[0], $appserverhost[1]);
				//$appserversocket->setDebug(true);
				if (isset($SETTINGS['appserver_commsuite']['timeout'])) {
					$appserversocket->setRecvTimeout($SETTINGS['appserver_commsuite']['timeout']);
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
		error_log("Exception Connection to AppServer CommSuite service (" . $tx->getMessage() . ")");
	}
	return array($appserverprotocol,$appservertransport);
}


?>