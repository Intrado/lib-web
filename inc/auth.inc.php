<?

function pearxmlrpc($method, $params) {
	$msg = new XML_RPC_Message($method, $params);

	$cli = new XML_RPC_Client('/xmlrpc', 'localhost:8088');

	$resp = $cli->send($msg);

	if (!$resp) {
    	error_log($method . ' communication error: ' . $cli->errstr);
	} else if ($resp->faultCode()) {
		error_log($method . ' Fault Code: ' . $resp->faultCode() . ' Fault Reason: ' . $resp->faultString());
	} else {
		$val = $resp->value();
    	$data = XML_RPC_decode($val);
		if ($data['result'] != "") {
			error_log($method . " " .$data['result']);
		} else {
			// success;
			return $data;
		}
	}
	return false;
}

function getCustomerName($url) {
	$params = array(new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.getCustomerName";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['customerName'];
	}
	return false;
}

function doLogin($loginname, $password, $url) {
	$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.login";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
		session_id($result['sessionID']); // set the session id
		doStartSession();
		return $result['userID'];
	}
}

function doLoginPhone($loginname, $password, $inboundnumber = null, $url = null) {
	$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($inboundnumber, 'string'), new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.loginPhone";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
		global $SESSIONDATA;
		$SESSIONDATA['authSessionID'] = $result['sessionID'];
		getSessionData($result['sessionID']); // load customer db connection
		return $result['userID'];
	}
}

function forceLogin($loginname, $url) {
	$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($url, 'string'), new XML_RPC_Value(session_id(), 'string'));
	$method = "AuthServer.forceLogin";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
		return $result[userID];
	}
}

function asptokenLogin($asptoken, $url) {
	$params = array(new XML_RPC_Value($asptoken, 'string'), new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.asptokenLogin";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
		session_id($result['sessionID']); // set the session id
		doStartSession();
		return $result['userID'];
	}
}

function getSessionData($id) {
	$params = array(new XML_RPC_Value($id, 'string'));
	$method = "AuthServer.getSessionData";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {

		// success
		$db['host'] = $result['dbhost'];
		$db['user'] = $result['dbuser'];
		$db['pass'] = $result['dbpass'];
		$db['db'] = $result['dbname'];
		// 	now connect to the customer database
		global $_dbcon;
		$_dbcon = mysql_connect($db['host'], $db['user'], $db['pass']);
		if (!$_dbcon) {
			error_log("Problem connecting to MySQL server at " . $db['host'] . " error:" . mysql_error());
		} else if (mysql_select_db($db['db'])) {
			// successful connection to customer database, return session data
			return $result['sessionData'];
		} else {
			error_log("Problem selecting database for " . $db['host'] . " error:" . mysql_error());
		}
	}
	return "";
}

function putSessionData($id, $sess_data) {
	$params = array(new XML_RPC_Value($id, 'string'), new XML_RPC_Value($sess_data, 'string'));
	$method = "AuthServer.putSessionData";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) return true;
	return false;
}

?>
