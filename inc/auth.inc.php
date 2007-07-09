<?

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

function doLogin($loginname, $password, $url = null) {
	$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.login";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
		session_id($result['sessionID']); // set the session id
		return $result['userID'];
	}
}

function doLoginPhone($loginname, $password, $inboundnumber = null, $url = null) {
	$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($inboundnumber, 'string'), new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.loginPhone";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success

		//if this is in the DMAPI code, we need to set the auth session id in the dmapi session data
		//so that it can load the db connection info on next page hit
		global $SESSIONDATA;
		if (isset($SESSIONDATA))
			$SESSIONDATA['authSessionID'] = $result['sessionID'];
		getSessionData($result['sessionID']); // load customer db connection
		return $result['userID'];
	}
}

function authorizeUploadImport($uploadkey, $url = null) {
	$params = array(new XML_RPC_Value($uploadkey, 'string'), new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.authorizeUploadImport";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
		session_id($result['sessionID']); // set the session id
		return $result['importID'];
	}
}

function authorizeTaskRequest($shardid, $taskuuid) {
	$params = array(new XML_RPC_Value($shardid, 'int'), new XML_RPC_Value($taskuuid, 'string'));
	$method = "AuthServer.authorizeTaskRequest";
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
			// successful connection to customer database
			return true;
		} else {
			error_log("Problem selecting database for " . $db['host'] . " error:" . mysql_error());
		}
	}
	return false;
}

function forceLogin($loginname, $url = null) {
	$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($url, 'string'), new XML_RPC_Value(session_id(), 'string'));
	$method = "AuthServer.forceLogin";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
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

function doStartSession() {
//	if (session_id() != "") return; // session was already started
	global $CUSTOMERURL;
	session_name($CUSTOMERURL . "_session");
	session_start();

	if (isset($_SESSION['timezone'])) {
		@date_default_timezone_set($_SESSION['timezone']);
		QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
	}
}

function loadCredentials ($userid) {
	global $USER, $ACCESS;

	$USER = $_SESSION['user'] = new User($userid);
	$ACCESS = $_SESSION['access'] = new Access($USER->accessid);
	$_SESSION['custname'] = getSystemSetting("displayname");
	$_SESSION['timezone'] = getSystemSetting("timezone");
}

/*CSDELETEMARKER_START*/
function asptokenLogin($asptoken, $url) {
	$params = array(new XML_RPC_Value($asptoken, 'string'), new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.asptokenLogin";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
		session_id($result['sessionID']); // set the session id
		return $result['userID'];
	}
}
/*CSDELETEMARKER_END*/

?>
