<?

// TODO dup function with auth.inc.php should move to common code?
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


function portalCreateAccount($username, $password, $firstname, $lastname, $zipcode) {
	$params = array(new XML_RPC_Value($username, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($firstname, 'string'), new XML_RPC_Value($lastname, 'string'), new XML_RPC_Value($zipcode, 'string'));
	$method = "PortalServer.portal_createAccount";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// account created
		return true;
	}
	return false;
}


function portalActivateAccount($activationtoken) {
	$params = array(new XML_RPC_Value($activationtoken, 'string'));
	$method = "PortalServer.portal_activateAccount";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// account activated
		session_id($result['sessionID']); // set the session id
		return $result['userID'];
	}
	return false;
}


function portalLogin($username, $password) {
	$params = array(new XML_RPC_Value($username, 'string'), new XML_RPC_Value($password, 'string'));
	$method = "PortalServer.portal_login";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
		session_id($result['sessionID']); // set the session id
		return $result['userID'];
	}
	return false;
}




?>
