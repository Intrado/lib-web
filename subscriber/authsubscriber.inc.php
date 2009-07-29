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

function isUsernameUnique($url, $username) {
	$params = array(new XML_RPC_Value($url, 'string'), new XML_RPC_Value($username, 'string'));
	$method = "SubscriberServer.subscriber_isUsernameUnique";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "" && $result['unique'] == "true") {
		return true;
	}
	
	return false;
}

function getCustomerAuthOptions($url) {
	$params = array(new XML_RPC_Value($url, 'string'));
	$method = "SubscriberServer.subscriber_getCustomerAuthOptions";
	$result = pearxmlrpc($method, $params);
	return $result;
}

function getCustomerData($url) {
	$params = array(new XML_RPC_Value($url, 'string'));
	$method = "SubscriberServer.subscriber_getCustomerData";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") {
		// success
		return $result['schememap'];
	}
	return false;
}

function getCustomerLogo($url) {
	$params = array(new XML_RPC_Value($url, 'string'));
	$method = "SubscriberServer.subscriber_getCustomerLogo";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") {
		// success
		return $result['schememap'];
	}
	return false;
}

function getCustomerLoginPicture($url) {
	$params = array(new XML_RPC_Value($url, 'string'));
	$method = "SubscriberServer.subscriber_getCustomerLoginPicture";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") {
		// success
		return $result['schememap'];
	}
	return false;
}


function subscriberCreateAccount($customerurl, $username, $password, $sitepass, $options) {
	$params = array(new XML_RPC_Value($customerurl, 'string'), new XML_RPC_Value($username, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($sitepass, 'string'), new XML_RPC_Value($options, 'string'));
	$method = "SubscriberServer.subscriber_createAccount";
	$result = pearxmlrpc($method, $params);
	return $result; // we do nothing for success/fail
}


function subscriberActivateAccount($activationtoken, $password) {
	$params = array(new XML_RPC_Value(trim($activationtoken), 'string'), new XML_RPC_Value(trim($password), 'string'));
	$method = "SubscriberServer.subscriber_activateAccount";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") {
		// account activated
		session_id($result['sessionID']); // set the session id
	}
	return $result;
}


function subscriberPreactivateForgottenPassword($activationtoken) {
	$params = array(new XML_RPC_Value(trim($activationtoken), 'string'));
	$method = "SubscriberServer.subscriber_preactivateForgottenPassword";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function subscriberLogin($customerurl, $username, $password) {
	$params = array(new XML_RPC_Value($customerurl, 'string'), new XML_RPC_Value(trim($username), 'string'), new XML_RPC_Value(trim($password), 'string'));
	$method = "SubscriberServer.subscriber_login";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") {
		// login success
		@session_destroy(); // destroy anonymous session before creating new one
		session_id($result['sessionID']); // set the session id
	}
	return $result;
}


function subscriberForgotPassword($username) {
	$customerurl = "";
	if (isset($_GET['u'])) {
		$customerurl = $_GET['u'];
	}
	global $CUSTOMERURL;
	$customerurl = $CUSTOMERURL;
	$params = array(new XML_RPC_Value($customerurl, 'string'), new XML_RPC_Value(trim($username), 'string'));
	$method = "SubscriberServer.subscriber_forgotPassword";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function subscriberCreatePhoneActivation($customerid, $subscriberid, $pkeyList, $createCode) {
	sleep(2); // slow down any DOS attack

	$pkeyarray = array();
	foreach ($pkeyList as $pkey) {
		$pkeyarray[] = new XML_RPC_Value($pkey, 'string');
	}
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($customerid, 'int'), new XML_RPC_Value($subscriberid, 'int'), new XML_RPC_Value($pkeyarray, 'array'), new XML_RPC_Value($createCode, 'boolean'));
	$method = "SubscriberServer.subscriber_createPhoneActivation";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function subscriberUpdateUsername($username, $password) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($username, 'string'), new XML_RPC_Value($password, 'string'));
	$method = "SubscriberServer.subscriber_updateUsername";
	$result = pearxmlrpc($method, $params);
	return $result;
}

// sends the token to the email for validation, return true or false
function subscriberPrepareNewEmail($newemail) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($newemail, 'string'));
	$method = "SubscriberServer.subscriber_prepareEmailValidation";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "")
		return true; // success
	return false; // failure
}

// generate phone activation code, return code or false
function subscriberPrepareNewPhone($newphone, $options) {
	sleep(2); // slow down any DOS attack

	$sessionid = session_id();
	
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($newphone, 'string'), new XML_RPC_Value($options, 'string'));
	$method = "SubscriberServer.subscriber_createPhoneActivation";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "" && isset($result['code']) && $result['code'] != "")
		return $result['code']; // success
	return false; // failure
}

// disable/close the active account then logout
function subscriberCloseAccount($password) {
	$sessionid = session_id();
	$params = array(new XML_RPC_Value($sessionid, 'string'), new XML_RPC_Value($password, 'string'));
	$method = "SubscriberServer.subscriber_closeAccount";
	$result = pearxmlrpc($method, $params);
	return $result;
}


function subscriberGetSessionData($id) {
	$params = array(new XML_RPC_Value($id, 'string'));
	$method = "SubscriberServer.subscriber_getSessionData";
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


function subscriberPutSessionData($id, $sess_data) {
	$sess_data = base64url_encode($sess_data);

	$params = array(new XML_RPC_Value($id, 'string'), new XML_RPC_Value($sess_data, 'string'));
	$method = "SubscriberServer.subscriber_putSessionData";
	$result = pearxmlrpc($method, $params);
	if ($result['result'] == "") return true;
	return false;
}


function subscriberCreateAnonymousSession() {
	$params = array();
	$method = "AuthServer.createAnonymousSession";
	$result = pearxmlrpc($method, $params);
	if ($result && $result['result'] == "" && $result['sessionID']) {
		session_id($result['sessionID']); // set the session id
		return true;
	}
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
	try {
		$dsn = 'mysql:dbname='.$_DBNAME.';host='.$_DBHOST;
		$_dbcon = new PDO($dsn, $_DBUSER, $_DBPASS);
		$_dbcon->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		
		$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
		$_dbcon->query($setcharset);
		return true;
	} catch (PDOException $e) {
		error_log("Problem connecting to MySQL server at " . $_DBHOST . " error:" . $e->getMessage());
	}
	return false;
}


function doStartSession() {
	global $CUSTOMERURL;
	session_name($CUSTOMERURL . "_subscriber");
	session_start();

	if (isset($_SESSION['timezone'])) {
		@date_default_timezone_set($_SESSION['timezone']);
		QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
	}
}

?>
