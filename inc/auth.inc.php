<?

function getCustomerName($url) {
	$params = array($url);
	$method = "AuthServer.getCustomerName";
	$request = xmlrpc_encode_request($method,$params);

	$context = stream_context_create(array('http' => array(
    	'method' => "POST",
    	'header' => "Content-Type: text/xml",
    	'content' => $request
	)));
	global $SETTINGS;
	$file = file_get_contents($SETTINGS['authserver']['url'], false, $context);
	$response = xmlrpc_decode($file);
	if (xmlrpc_is_fault($response)) {
	    trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
	} else if ($response[result] != "") {
	    // failure
	    error_log("getCustomerName failed " . $response[result]);
	} else {
		// success
		return $response[customerName];
	}
	return false;
}

function doLogin($loginname, $password, $url) {
	$params = array($loginname, $password, $url);
	$method = "AuthServer.login";
	$request = xmlrpc_encode_request($method,$params);

	$context = stream_context_create(array('http' => array(
    	'method' => "POST",
    	'header' => "Content-Type: text/xml",
    	'content' => $request
	)));
	global $SETTINGS;
	$file = file_get_contents($SETTINGS['authserver']['url'], false, $context);
	$response = xmlrpc_decode($file);
	if (xmlrpc_is_fault($response)) {
	    trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
	} else if ($response[result] != "") {
	    // login failure
	    error_log("doLogin failed " . $response[result]);
	} else {
		// login success
		session_id($response[sessionID]); // set the session id
		doStartSession();
		return $response[userID];
	}
}

function doLoginPhone($loginname, $password, $inboundnumber = null, $url = null) {
	$params = array($loginname, $password, $inboundnumber, $url);
	$method = "AuthServer.loginPhone";
	$request = xmlrpc_encode_request($method,$params);

	$context = stream_context_create(array('http' => array(
    	'method' => "POST",
    	'header' => "Content-Type: text/xml",
    	'content' => $request
	)));
	global $SETTINGS;
	$file = file_get_contents($SETTINGS['authserver']['url'], false, $context);
	$response = xmlrpc_decode($file);
	if (xmlrpc_is_fault($response)) {
	    trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
	} else if ($response[result] != "") {
	    // login failure
	    error_log("doLoginPhone failed " . $response[result]);
	} else {
		// login success
		global $SESSIONDATA;
		$SESSIONDATA[authSessionID] = $response[sessionID];
		getSessionData($response[sessionID]); // load customer db connection
		return $response[userID];
	}
}


function forceLogin($loginname, $url) {
	$params = array($loginname, $url, session_id());
	$method = "AuthServer.forceLogin";
	$request = xmlrpc_encode_request($method,$params);

	$context = stream_context_create(array('http' => array(
    	'method' => "POST",
    	'header' => "Content-Type: text/xml",
    	'content' => $request
	)));
	global $SETTINGS;
	$file = file_get_contents($SETTINGS['authserver']['url'], false, $context);
	$response = xmlrpc_decode($file);
	if (xmlrpc_is_fault($response)) {
	    trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
	} else if ($response[result] != "") {
	    // login failure
	    error_log("forceLogin failed " . $response[result]);
	} else {
		// login success
		return $response[userID];
	}
}

function getSessionData($id) {
	$params = array($id);
	$method = "AuthServer.getSessionData";
	$request = xmlrpc_encode_request($method,$params);

	$context = stream_context_create(array('http' => array(
    	'method' => "POST",
    	'header' => "Content-Type: text/xml",
    	'content' => $request
	)));
	global $SETTINGS;
	$file = file_get_contents($SETTINGS['authserver']['url'], false, $context);
	$response = xmlrpc_decode($file);
	if (xmlrpc_is_fault($response)) {
	    trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
	} else if ($response[result] != "") {
	    // login failure
	    error_log("getSessionData failed " . $response[result]);
	} else {
		// success
		$db['host'] = $response[dbhost];
		$db['user'] = $response[dbuser];
		$db['pass'] = $response[dbpass];
		$db['db'] = $response[dbname];

		// now connect to the customer database
		global $_dbcon;

		$_dbcon = mysql_connect($db['host'], $db['user'], $db['pass']);
		if (!$_dbcon) {
			error_log("Problem connecting to MySQL server at " . $db['host'] . " error:" . mysql_error());
		} else if (mysql_select_db($db['db'])) {
			// successful connection to customer database, return session data
			return $response[sessionData];
		} else {
			error_log("Problem selecting database for " . $db['host'] . " error:" . mysql_error());
		}
	}

	return "";
}

function putSessionData($id, $sess_data) {
	$params = array($id, $sess_data);
	$method = "AuthServer.putSessionData";
	$request = xmlrpc_encode_request($method,$params);

	$context = stream_context_create(array('http' => array(
    	'method' => "POST",
    	'header' => "Content-Type: text/xml",
    	'content' => $request
	)));
	global $SETTINGS;
	$file = file_get_contents($SETTINGS['authserver']['url'], false, $context);
	$response = xmlrpc_decode($file);
	if (xmlrpc_is_fault($response)) {
	    trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
	} else if ($response[result] != "") {
	    // login failure
	    error_log("putSessionData failed " . $response[result]);
	} else {
		// success
		return true;
	}

	return false;
}

?>
