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


function subscriberCreateAccount($customerurl, $username, $password, $options) {
//	$customerurl = "";
//	if (isset($_GET['u'])) {
//		$customerurl = $_GET['u'];
//	}
	$params = array(new XML_RPC_Value($customerurl, 'string'), new XML_RPC_Value($username, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($options, 'string'));
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
		
		// TODO set charset
		$setcharset = "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'";
		$_dbcon->query($setcharset);
		
		return true;
	} catch (PDOException $e) {
		error_log("Problem connecting to MySQL server at " . $_DBHOST . " error:" . $e->getMessage());
	}
	return false;
}


function doStartSession($subscriberID = false) {
	$todo = "todo"; // TODO unique name, maybe use the subscriber name
	session_name($todo . "_session");
	session_start();

	if (isset($_SESSION['timezone'])) {
		@date_default_timezone_set($_SESSION['timezone']);
		QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
	}
	
	if ($subscriberID) {
		$_SESSION['subscriberid'] = $subscriberID;
		$_SESSION['personid'] = $pid = QuickQuery("select personid from subscriber where id=?", false, array($subscriberID));
		$_SESSION['custname'] = QuickQuery("select value from setting where name='displayname'");		
	
		$firstnameField = FieldMap::getFirstNameField();
		$lastnameField = FieldMap::getLastNameField();
	
		$_SESSION['subscriber.username'] = QuickQuery("select username from subscriber where id=?", false, array($subscriberID));
		$_SESSION['subscriber.firstname'] = QuickQuery("select ".$firstnameField." from person where id=?", false, array($pid));
		$_SESSION['subscriber.lastname'] = QuickQuery("select ".$lastnameField." from person where id=?", false, array($pid));
	
		$_SESSION['colorscheme']['_brandtheme']   = "3dblue";
		$_SESSION['colorscheme']['_brandtheme1']  = "89A3CE";
		$_SESSION['colorscheme']['_brandtheme2']  = "89A3CE";
		$_SESSION['colorscheme']['_brandprimary'] = "26477D";
		$_SESSION['colorscheme']['_brandratio']   = ".3";
		
		$prefs = QuickQuery("select preferences from subscriber where id=?", false, array($subscriberID));
		$preferences = json_decode($prefs, true);
		if (isset($preferences['_locale']))
			$_SESSION['_locale'] = $preferences['_locale'];
		else
			$_SESSION['_locale'] = "en_US"; // US English
			
	}
}


?>
