<?

// if returndata, return data regardless of result success/failure
function pearxmlrpc($method, $params, $returndata = false) {
	global $SETTINGS;
	$authhost = $SETTINGS['authserver']['host'];

	$msg = new XML_RPC_Message($method, $params);

	$cli = new XML_RPC_Client('/xmlrpc', $authhost);

	$resp = $cli->send($msg, 90);

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
			error_log($method . " " .$data['result'] . " " . $data['resultdetail']);
		} else {
			// error
			error_log($method . " " .$data['result'] . " " . $data['resultdetail']);
		}
		if ($returndata) return $data;
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

function doLogin($loginname, $password, $url = null, $ipaddress = null) {
	$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($url, 'string'), new XML_RPC_Value($ipaddress, 'string'));
	$method = "AuthServer.login";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		if ($result['userID'] == -1) {
			return -1; // user temporarily locked out
		} else {
			// login success
			session_id($result['sessionID']); // set the session id
			return $result['userID'];
		}
	}
	return false;
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
		else if($url != null || $url != "")
			session_id($result['sessionID']);

		if (doDBConnect($result)) return $result['userID'];
	}
	return false;
}

function doLoginPhoneUserEnabled($loginname, $password, $inboundnumber = null, $url = null) {
	$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($inboundnumber, 'string'), new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.loginPhone";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// login success
		return $result['userID'];
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
	return false;
}

function authorizeSurveyWeb($emailcode, $url) {
	$params = array(new XML_RPC_Value($emailcode, 'string'), new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.authorizeSurveyWeb";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		if ($result['reason'] == 'ok') {
			if (doDBConnect($result)) return $result['reason'];
			return 'invalid';
		}
		return $result['reason'];
	}
	return false;
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
	return false;
}

function authorizeTaskRequest($shardid, $taskuuid) {
	$params = array(new XML_RPC_Value($shardid, 'int'), new XML_RPC_Value($taskuuid, 'string'));
	$method = "AuthServer.authorizeTaskRequest";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		if (doDBConnect($result)) return true;
	}
	return false;
}

function authorizeSpecialTask($shardid, $taskuuid) {
	$params = array(new XML_RPC_Value($shardid, 'int'), new XML_RPC_Value($taskuuid, 'string'));
	$method = "AuthServer.authorizeSpecialTask";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success

		//this is in the DMAPI code, we need to set the auth session id in the dmapi session data
		//so that it can load the db connection info on next page hit
		global $SESSIONDATA;
		if (isset($SESSIONDATA))
			$SESSIONDATA['authSessionID'] = $result['sessionID'];

		if (doDBConnect($result)) return $result['sessionID'];
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

function getSessionData($id) {
	$params = array(new XML_RPC_Value($id, 'string'));
	$method = "AuthServer.getSessionData";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		$sess_data = base64url_decode($result['sessionData']);
		if (doDBConnect($result)) return $sess_data;
	} else {
		error_log_helper("ERROR trying to getSessionData for '$id'");
	}
	return "";
}

function putSessionData($id, $sess_data) {
	$sess_data = base64url_encode($sess_data);

	$params = array(new XML_RPC_Value($id, 'string'), new XML_RPC_Value($sess_data, 'string'));
	$method = "AuthServer.putSessionData";
	$result = pearxmlrpc($method, $params);
	if ($result === false) { 
		error_log_helper("ERROR trying to putSessionData for '$id'");
		return false;
	} else {
		return true;
	}
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
	if (isset($_SESSION['timezone'])) {
		@date_default_timezone_set($_SESSION['timezone']);
		QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
	}
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
	try {
		$dsn = 'mysql:dbname='.$_DBNAME.';host='.$_DBHOST;
		$_dbcon = new PDO($dsn, $_DBUSER, $_DBPASS);
		$_dbcon->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		
		// TODO set charset
		$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
		$_dbcon->query($setcharset);
		
		$temp = $_dbcon->query("select connection_id()");
		$cid = $temp->fetchColumn();
		$_SESSION['_dbcid'] = $cid;
		
		return true;
	} catch (PDOException $e) {
		error_log("Problem connecting to MySQL server at " . $_DBHOST . " error:" . $e->getMessage());
	}
	return false;
}

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

function getCustomerData($url){

	$params = array(new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.getCustomerData";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['schememap'];
	}
	return false;
}

function getCustomerLogo($url) {

	$params = array(new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.getCustomerLogo";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['schememap'];
	}
	return false;
}

function getCustomerLoginPicture($url) {

	$params = array(new XML_RPC_Value($url, 'string'));
	$method = "AuthServer.getCustomerLoginPicture";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['schememap'];
	}
	return false;
}

function auth_resetDM($dmid){
	$sessionid = session_id();
	$params =  array(new XML_RPC_Value($dmid, 'int'), new XML_RPC_Value($sessionid, 'string'));
	$method = "AuthServer.resetDM";
	$result = pearxmlrpc($method, $params);
	if($result !== false) {
		if($result['result'] == ""){
			return true;
		}
	}
	return false;
}

function api_getCustomerURL($oem, $oemid){
	$params = array(new XML_RPC_Value($oem, 'string'), new XML_RPC_Value($oemid, 'string'));
	$method = "AuthServer.getCustomerURL";
	$result = pearxmlrpc($method, $params);
	if($result !== false) {
		if($result['result'] == ""){
			return $result['customerurl'];
		}
	}
	return false;
}

function forgotPassword($username, $customerurl){
	$params = array(new XML_RPC_Value($username, 'string'), new XML_RPC_Value($customerurl, 'string'));
	$method = "AuthServer.forgotPassword";
	$result = pearxmlrpc($method, $params, true);
	if($result !== false){
		return $result;
	}
	return false;
}

function resetPassword($activationcode, $password, $ipaddr){
	$params = array(new XML_RPC_Value($activationcode, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($ipaddr, 'string'));
	$method = "AuthServer.resetPassword";
	$result = pearxmlrpc($method, $params);
	if($result !== false){
		if($result['result'] == ""){
			// login success
			session_id($result['sessionID']); // set the session id
			return $result['userID'];
		}
	}
	return false;
}

function prefetchUserInfo($activationcode) {
	$params = array(new XML_RPC_Value($activationcode, 'string'));
	$method = "AuthServer.prefetchUserInfo";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		return $result;
	}
	return false;
}

function emailUnsubscribe($urlcomponent, $email) {
	$params = array(new XML_RPC_Value($urlcomponent, 'string'), new XML_RPC_Value($email, 'string'));
	$method = "AuthServer.emailUnsubscribe";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return true;
	}
	return false;
}

function setUserPassword($userid, $password) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($userid, 'int'), new XML_RPC_Value($password, 'string'));
	$method = "AuthServer.setUserPassword";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return true;
	}
	return false;
}

function readonlyDBInfo() {
	$sessionid = session_id();
	// if no session, return false
	// this will occur when running 'php runreport' from redialer for autoreport at end of job, or scheduled reports
	if ($sessionid === "")
		return false;
		
	$params = array(new XML_RPC_Value($sessionid, 'string'));
	$method = "AuthServer.getReadonlyDBInfo";
	$result = pearxmlrpc($method, $params);
	return $result;
}

function readonlyDBConnect() {
	$result = readonlyDBInfo();
	if ($result !== false && $result['result'] == '') {
		// success, now try to connect
		try {
			$dsn = 'mysql:dbname='.$result['dbname'].';host='.$result['dbhost'];
			$_readonlyDB = new PDO($dsn, $result['dbuser'], $result['dbpass']);
			$_readonlyDB->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		
			$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
			$_readonlyDB->query($setcharset);
			
			if (isset($_SESSION['timezone'])) {
				$_readonlyDB->query("set time_zone='" . $_SESSION['timezone'] . "'");
			}
			return $_readonlyDB;
		} catch (PDOException $e) {
			error_log("Problem connecting with readonly to MySQL server at " . $result['dbhost'] . " error:" . $e->getMessage() . " now retry primary db");
			global $_dbcon;
			return $_dbcon;
		}
	}
	return false;
}


////////// parent portal methods

function getPortalUsers($portaluserids) {
	$portaluseridstruct = array();
	$i = 0;
	foreach($portaluserids as $id){
		$portaluseridstruct[$i] = new XML_RPC_VALUE($id, 'int');
		$i++;
	}

	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($portaluseridstruct, 'struct'));
	$method = "PortalAdminServer.portal_getPortalUsers";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['usermap'];
	}
	return false;
}

function generatePersonTokens($personids) {
	$personids = implode(",",$personids); // send the CSV format
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($personids, 'string'));
	$method = "PortalAdminServer.portal_generatePersonTokens";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['tokencount'];
	}
	return false;
}

function revokePersonTokens($personids) {
	$personids = implode(",",$personids); // send the CSV format
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($personids, 'string'));
	$method = "PortalAdminServer.portal_revokePersonTokens";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['tokencount'];
	}
	return false;
}

function inboundPortalFindCallerid($callerid) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($callerid, 'string'));
	$method = "PortalServer.inbound_findCallerid";
	$result = pearxmlrpc($method, $params);
	return $result;
}

function inboundPortalPhoneActivation($callerid, $code) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($callerid, 'string'), new XML_RPC_Value($code, 'string'));
	$method = "PortalServer.inbound_activate";
	$result = pearxmlrpc($method, $params);
	return $result;
}

function inboundSubscriberPhoneActivation($callerid, $code) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($callerid, 'string'), new XML_RPC_Value($code, 'string'));
	$method = "SubscriberServer.subscriber_phoneActivation";
	$result = pearxmlrpc($method, $params);
	return $result;
}


?>
