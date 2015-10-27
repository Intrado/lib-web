<?

function pearxmlrpc($method, $params) {
	global $SETTINGS;
	$authhost = $SETTINGS['authserver']['host'];

	$msg = new XML_RPC_Message($method, $params);

	$cli = new XML_RPC_Client('/xmlrpc', $authhost);

	$isAlive = false;
	$timetostop = time() + 30; // 30 seconds from now
	
	// retry authserver for a while, maybe mid-restart
	while (!$isAlive && $timetostop > time()) {
		$resp = $cli->send($msg, 90);
		if (!$resp) {
			error_log($method . ' communication error: ' . $cli->errstr);
			usleep(100000);
		} else {
			$isAlive = true;
		}
	}
	
	if (!$resp) {
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		echo "
			<html>
				<head>
					<title>503 Service Temporarily Unavailable</title>
				</head>
				<body>
					<h1>Service Temporarily Unavailable</h1>

					The server is temporarily unable to service your request. Please try again later.
				</body>
			</html>
			";
		exit();
	} else if ($resp->faultCode()) {
		error_log($method . ' Fault Code: ' . $resp->faultCode() . ' Fault Reason: ' . $resp->faultString());
	} else {
		$val = $resp->value();
    	$data = XML_RPC_decode($val);
		if ($data['result'] == "") {
			// success
		} else if ($data['result'] == "warning") {
			// warning we do not log, but handle like failure
		} else {
			// error
			error_log($method . " " .$data['result']);
		}
		return $data;
	}
	$data = array();
	$data['result'] = "unknown error";
	$data['resultdetail'] = "unexpected failure condition";

	return $data;
}


function portalGetCustomerAssociations() {
	$sessionid = session_id();
	
	// find optional customerurl either by url param or cookie
	$customerurl = false;
	if (isset($_GET['u'])) {
		$customerurl = $_GET['u'];
	} else if (isset($_COOKIE['customerurl'])) {
		$customerurl = $_COOKIE['customerurl'];
	}
	if ($customerurl) {
		// store in session for logout, just in case user's cookies are disabled
		$_SESSION['customerurl'] = $customerurl; // store this for logout to append
	} else {
		$customerurl = "";
	}
	
	// remove cookie to clear future logins
	if (isset($_COOKIE['customerurl'])) {
		setcookie('customerurl', '', time() - 3600);
	}
	
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($customerurl, 'string'));
	$method = "PortalServer.portal_getCustomerAssociations";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalAccessCustomer($customerid) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($customerid, 'int'));
	$method = "PortalServer.portal_accessCustomer";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") {
		// success
		if (!doDBConnect($result)) {
			$result['result'] = "unknown error";
			$result['resultdetail'] = "unexpected failure condition";
		} else {
			$_SESSION['_dbcid'] = $customerid;
		}
	}
	return $result;
}


function portalAssociatePerson($token, $validationdata) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value(trim($token), 'string'), new XML_RPC_Value(trim($validationdata), 'string'));
	$method = "PortalServer.portal_associatePerson";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalGetPortalUser() {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'));
	$method = "PortalServer.portal_getMyPortalUser";
	$result = pearxmlrpc($method, $params);
	$result['portaluser']['portaluser.preferences'] = json_decode($result['portaluser']['portaluser.preferences'], true);
	return $result;
}


function portalUpdateUserPreferences($email, $sms, $preferences) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'),
		new XML_RPC_Value($email, 'string'),
		new XML_RPC_Value($sms, 'string'),
		new XML_RPC_Value(json_encode($preferences), 'string'));
		
	$method = "PortalServer.portal_updateMyPortalUserPreferences";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalCreatePhoneActivation($customerid, $portaluserid, $pkeyList, $createCode) {
	sleep(2); // slow down any DOS attack

	$pkeyarray = array();
	foreach ($pkeyList as $pkey) {
		$pkeyarray[] = new XML_RPC_Value($pkey, 'string');
	}
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($customerid, 'int'), new XML_RPC_Value($portaluserid, 'int'), new XML_RPC_Value($pkeyarray, 'array'), new XML_RPC_Value($createCode, 'boolean'));
	$method = "PortalServer.portal_createPhoneActivation";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalDisassociateCustomer($customerid) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($customerid, 'int'));
	$method = "PortalServer.portal_disassociateCustomer";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalGetSessionData($id) {
	$params = array(new XML_RPC_Value($id, 'string'));
	$method = "PortalServer.portal_getSessionData";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") {
		// success
		$sess_data = base64url_decode($result['sessionData']);
		if (isset($result['dbhost'])) {
			doDBConnect($result);
		}
		return $sess_data;
	}
	return "";
}


function portalPutSessionData($id, $sess_data) {
	$sess_data = base64url_encode($sess_data);

	$params = array(new XML_RPC_Value($id, 'string'), new XML_RPC_Value($sess_data, 'string'));
	$method = "PortalServer.portal_putSessionData";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") return true;
	return false;
}

function doDBConnect($result) {
	global $_DBHOST;
	global $_DBNAME;
	global $_DBUSER;
	global $_DBPASS;

	$_DBHOST = $result['dbhost'];
	$_DBUSER = $result['dbuser'];
	$_DBPASS = $result['dbpass'];
	$_DBNAME = $result['dbname'];

	global $_dbcon;
	$_dbcon = DBConnect($_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);
	if ($_dbcon) {
		if (isset($_SESSION['timezone'])) {
			@date_default_timezone_set($_SESSION['timezone']);
			QuickUpdate("SET time_zone='" . $_SESSION['timezone'] . "'");
		}
		return true;
	}
	return false;
}

function doStartSession() {
	$todo = "todo"; // TODO unique name, maybe use the portaluser name
	session_name($todo . "_session");
	session_start();
}

// **************************
// portalauth methods

function getPortalAuthAuthRequestTokenUrl($callbackUrl) {
	$params = array(new XML_RPC_Value(session_id(), 'string'), new XML_RPC_Value($callbackUrl, 'string'));
	$method = "PortalServer.portal_getPortalAuthAuthRequestTokenUrl";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['url'];
	}
	return false;
}

function getPortalAuthLocation() {
	$method = "PortalServer.portal_getPortalAuthLocationUrl";
	$result = pearxmlrpc($method, array());
	if ($result !== false) {
		// success
		return $result;
	}
	return false;
}

function loginViaPortalAuth() {
	$params = array(new XML_RPC_Value(session_id(), 'string'));
	$method = "PortalServer.portal_loginViaPortalAuth";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		//if (doDBConnect($result)) return $result;
		return $result;
	}
	return false;
}

//****************************************************************************************
// anonymous session methods

function newSession() {
	$method = "PortalServer.portal_newSession";
	$result = pearxmlrpc($method, array());
	if ($result !== false && $result["sessionID"] != "") {
		//error_log_helper("set sessionid to ". $result["sessionID"]);
		session_id($result["sessionID"]);

		return true;
	} else {
		error_log_helper("Problem requesting newSession() - result: ". $result["result"]. " resultdetail: ". $result["resultdetail"]);
	}
	return false;
}

?>
