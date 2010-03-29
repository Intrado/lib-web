<?

// XML-RPC functions
function pearxmlrpcDisk($method, $params) {
	global $SETTINGS;
	$host = $SETTINGS['diskserver']['host'];
	$path = $SETTINGS['diskserver']['path'];

	$msg = new XML_RPC_Message($method, $params);

	$cli = new XML_RPC_Client($path, $host);

	$resp = $cli->send($msg);

	if (!$resp) {
    	error_log($method . ' communication error: ' . $cli->errstr);
	} else if ($resp->faultCode()) {
		error_log($method . ' Fault Code: ' . $resp->faultCode() . ' Fault Reason: ' . $resp->faultString());
	} else {
		$val = $resp->value();
    	$data = XML_RPC_decode($val);
		if (isset($data['faultCode'])) {
			error_log($method . ' Fault Code: ' . $data['faultCode'] . ' Fault Reason: ' . $data['faultString']);
		} else if ($data['resultcode'] == "SUCCESS") {
			// success
			return $data;
		} else {
			// error
			error_log($method . " " .$data['resultcode']);
		}
	}
	return false;
}

function getAgentList() {
	$params = array();
	$method = "Internalapi.getAgentList";
	$result = pearxmlrpcDisk($method, $params);
	if ($result !== false) {
		// success
		return $result['agentlist'];
	}
	return false;
}

function resetAgent($uuid) {
	error_log("resetAgent");
	$params = array(new XML_RPC_Value($uuid, 'string'), new XML_RPC_Value("RESET", 'string'), new XML_RPC_Value("", 'string'));
	$method = "Internalapi.sendCommandToAgent";
	$result = pearxmlrpcDisk($method, $params);
	error_log($result);
	if ($result !== false) {
		// success
		return true;
	}
	return false;
}

?>
