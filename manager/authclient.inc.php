<?

// XML-RPC functions
function pearxmlrpc($method, $params) {
	global $SETTINGS;
	$authhost = $SETTINGS['authserver']['host'];

	$msg = new XML_RPC_Message($method, $params);

	$cli = new XML_RPC_Client('/xmlrpc', $authhost);

	$resp = $cli->send($msg);

	if (!$resp) {
    		error_log($method . ' communication error: ' . $cli->errstr);
	} else if ($resp->faultCode()) {
		error_log($method . ' Fault Code: ' . $resp->faultCode() . ' Fault Reason: ' . $resp->faultString());
	} else {
		$val = $resp->value();
    	$data = XML_RPC_decode($val);
		if ($data['result'] == "") {
			// success
			return $data;
		} else if ($data['result'] == "warning") {
			// warning we do not log, but handle like failure
		} else {
			// error
			error_log($method . " " .$data['result']);
		}
	}
	return false;
}

function blocksms($sms, $action, $notes) {
	$params = array(new XML_RPC_Value($sms, 'string'), new XML_RPC_Value($action, 'string'), new XML_RPC_Value($notes, 'string'));
	$method = "AuthServer.updateBlockedNumber";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['result'];
	}
	return false;
}

function refreshCustomer($cid) {
	$params = array(new XML_RPC_Value($cid, 'int'));
	$method = "AuthServer.refreshCustomer";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['result'];
	}
	return false;
}

?>
