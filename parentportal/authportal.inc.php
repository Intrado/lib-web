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


function portalCreateAccount($username, $password, $firstname, $lastname, $zipcode) {
	$params = array(new XML_RPC_Value(trim($username), 'string'), new XML_RPC_Value(trim($password), 'string'), new XML_RPC_Value(trim($firstname), 'string'), new XML_RPC_Value(trim($lastname), 'string'), new XML_RPC_Value(trim($zipcode), 'string'));
	$method = "PortalServer.portal_createAccount";
	$result = pearxmlrpc($method, $params);
	return $result; // we do nothing for success/fail
}


function portalActivateAccount($activationtoken, $password) {
	$params = array(new XML_RPC_Value(trim($activationtoken), 'string'), new XML_RPC_Value(trim($password), 'string'));
	$method = "PortalServer.portal_activateAccount";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") {
		// account activated
		session_id($result['sessionID']); // set the session id
	}
	return $result;
}


function portalPreactivateForgottenPassword($activationtoken) {
	$params = array(new XML_RPC_Value(trim($activationtoken), 'string'));
	$method = "PortalServer.portal_preactivateForgottenPassword";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalLogin($username, $password) {
	$params = array(new XML_RPC_Value(trim($username), 'string'), new XML_RPC_Value(trim($password), 'string'));
	$method = "PortalServer.portal_login";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") {
		// login success
		session_id($result['sessionID']); // set the session id
	}
	return $result;
}


function portalGetCustomerAssociations() {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'));
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


function portalForgotPassword($username) {
	$params = array(new XML_RPC_Value(trim($username), 'string'));
	$method = "PortalServer.portal_forgotPassword";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalGetPortalUser() {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'));
	$method = "PortalServer.portal_getMyPortalUser";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalUpdatePortalUser($firstname, $lastname, $zipcode) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value(trim($firstname), 'string'), new XML_RPC_Value(trim($lastname), 'string'), new XML_RPC_Value(trim($zipcode), 'string'));
	$method = "PortalServer.portal_updateMyPortalUser";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalUpdatePortalUserPassword($newpassword, $oldpassword) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value(trim($newpassword), 'string'), new XML_RPC_Value(trim($oldpassword), 'string'));
	$method = "PortalServer.portal_updateMyPortalUserPassword";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function portalUpdatePortalUsername($username, $password) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value(trim($password), 'string'), new XML_RPC_Value(trim($username), 'string'));
	$method = "PortalServer.portal_updateMyPortalUsername";
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

	// 	now connect to the customer database
	global $_dbcon;
	$_dbcon = mysql_connect($_DBHOST, $_DBUSER, $_DBPASS);
	if (!$_dbcon) {
		error_log("Problem connecting to MySQL server at " . $_DBHOST . " error:" . mysql_error());
	} else if (mysql_select_db($_DBNAME)) {
		// successful connection to customer database
		return true;
	} else {
		error_log("Problem selecting database for " . $_DBHOST . " error:" . mysql_error());
	}
	return false;
}


function doStartSession() {
	$todo = "todo"; // TODO unique name, maybe use the portaluser name
	session_name($todo . "_session");
	session_start();

	if (isset($_SESSION['timezone'])) {
		@date_default_timezone_set($_SESSION['timezone']);
		QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
	}

}


?>
