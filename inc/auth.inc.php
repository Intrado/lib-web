<?
/*CSDELETEMARKER_START*/

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
/*CSDELETEMARKER_END*/

function getCustomerName($url) {
	global $IS_COMMSUITE;
	if ($IS_COMMSUITE) {
		return getSystemSetting("displayname");
	/*CSDELETEMARKER_START*/
	} else {
		$params = array(new XML_RPC_Value($url, 'string'));
		$method = "AuthServer.getCustomerName";
		$result = pearxmlrpc($method, $params);
		if ($result !== false) {
			// success
			return $result['customerName'];
		}
		return false;
	/*CSDELETEMARKER_END*/
	}
}

function doLogin($loginname, $password, $url = null) {
	global $IS_COMMSUITE;
	if ($IS_COMMSUITE) {
		GLOBAL $IS_LDAP;
		GLOBAL $SETTINGS;
		$loginname = dbsafe(trim($loginname));
		$password = dbsafe($password);

		$LDAP_CONNECT = $SETTINGS['ldap']['ldapconnect'];
		$ad = $SETTINGS['ldap']['ldap_ad'];
		$com = $SETTINGS['ldap']['ldap_com'];
		$LDAP_EXTENSION = "@" . $ad . "." . $com;
		if($IS_LDAP){
			$userldap = QuickQuery("select user.ldap from user where user.login='$loginname'");

			if($userldap){
				if(strpos('@',$loginname)== false){
					$ldapusername = $loginname.$LDAP_EXTENSION;
				} else {
					$ldapusername = $loginname;
				}
				if($ldapusername == "")
					return false;
				if($ds=ldap_connect($LDAP_CONNECT)) {
					if(@ldap_bind($ds,$ldapusername,$password) && $password) {
						$query = "select id from user where user.login='$loginname'";
						ldap_close($ds);
						return QuickQuery($query);
					} else {
						ldap_close($ds);
						return false;
					}
				}
				return false;
			}
		}
		if($password == ""){
			return false;
		}
		$query = "select id from user where enabled=1 and deleted=0 and "
					."login='$loginname' and password=password('$password')";
		return QuickQuery($query);

	/*CSDELETEMARKER_START*/
	} else {
		$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($password, 'string'), new XML_RPC_Value($url, 'string'));
		$method = "AuthServer.login";
		$result = pearxmlrpc($method, $params);
		if ($result !== false) {
			// login success
			session_id($result['sessionID']); // set the session id
			return $result['userID'];
		}
	/*CSDELETEMARKER_END*/
	}
}

function doLoginPhone($loginname, $password, $inboundnumber = null, $url = null) {
	global $IS_COMMSUITE;
	if ($IS_COMMSUITE) {
		GLOBAL $SETTINGS;
		$IS_LDAP = $SETTINGS['ldap']['is_ldap'];

		$loginname = DBSafe($loginname);
		$password = DBSafe($password);

		if($IS_LDAP){
			$LDAP_CONNECT = $SETTINGS['ldap']['ldapconnect'];
			$ldapusername = $SETTINGS['ldap']['ldapusername'];
			$ldappassword = $SETTINGS['ldap']['ldappassword'];
			$ad = $SETTINGS['ldap']['ldap_ad'];
			$com = $SETTINGS['ldap']['ldap_com'];
			$cn = $SETTINGS['ldap']['ldap_cn'];

			$query = "select login, id, ldap from user where enabled=1 and deleted=0 and "
					."accesscode='$loginname' and pincode=password('$password')";
			$user = Query($query);
			$user = DBGetRow($user);
			if($user[2]){
				if($ds=ldap_connect($LDAP_CONNECT)) {
					if(@ldap_bind($ds,$ldapusername,$ldappassword)) {

						$sr=ldap_search($ds, "cn=$cn, dc=$ad, dc=$com", "sAMAccountName=".$user[0]);
						$info = ldap_get_entries($ds, $sr);
						if(!($info[0]["useraccountcontrol"][0] & 2)){
							ldap_close($ds);
							return $user[1];
						} else {
							ldap_close($ds);
							return false;
						}
					}
					ldap_close($ds);
					return false;
				}
				return false;
			}
		}

		// commsuite is a single customer, do not need customerurl or inboundnumber, just find the user in their database
		$query = "select id from user where enabled=1 and deleted=0 and "
					."accesscode='$loginname' and pincode=password('$password')";
		return QuickQuery($query);

	/*CSDELETEMARKER_START*/
	} else {
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
	/*CSDELETEMARKER_END*/
	}
}

function doLoginUploadImport($uploadkey, $url = null) {
	global $IS_COMMSUITE;
	if ($IS_COMMSUITE) {
		// TODO
		return 0;

	/*CSDELETEMARKER_START*/
	} else {
		$params = array(new XML_RPC_Value($uploadkey, 'string'), new XML_RPC_Value($url, 'string'));
		$method = "AuthServer.loginUploadImport";
		$result = pearxmlrpc($method, $params);
		if ($result !== false) {
			// login success
			session_id($result['sessionID']); // set the session id
			return $result['importID'];
		}
	/*CSDELETEMARKER_END*/
	}
}


function forceLogin($loginname, $url = null) {
	global $IS_COMMSUITE;
	if ($IS_COMMSUITE) {
		$loginname = dbsafe($loginname);
		$query = "select id from user where enabled=1 and deleted=0 and login='$loginname'";

		return QuickQuery($query);

	/*CSDELETEMARKER_START*/
	} else {
		$params = array(new XML_RPC_Value($loginname, 'string'), new XML_RPC_Value($url, 'string'), new XML_RPC_Value(session_id(), 'string'));
		$method = "AuthServer.forceLogin";
		$result = pearxmlrpc($method, $params);
		if ($result !== false) {
			// login success
			return $result['userID'];
		}
	/*CSDELETEMARKER_END*/
	}
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

/*CSDELETEMARKER_END*/
?>
