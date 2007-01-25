<?
class User extends DBMappedObject {

	var $accessid = 0;
	var $login = "";
	//Do not store password
	var $accesscode;
	//Do not store pincode
	var $customerid;
	var $firstname = "";
	var $lastname = "";
	var $phone = "";
	var $email = "";
	var $enabled = 0;
	var $lastlogin;
	var $deleted = 0;
	var $ldap;

	//new constructor
	function User ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "user";
		$this->_fieldlist = array("accessid", "login", "accesscode", "customerid", "firstname",
								"lastname", "email", "phone", "enabled","lastlogin","deleted", "ldap");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

/* static functions */

	function doLogin ($username, $password, $url = null) {
		
		GLOBAL $IS_LDAP;
		GLOBAL $SETTINGS;
		GLOBAL $IS_COMMSUITE;
		$username = dbsafe($username);
		$password = dbsafe($password);
		
		$LDAP_CONNECT = $SETTINGS['ldap']['ldapconnect'];
		$LDAP_EXTENSION = $SETTINGS['ldap']['ldapextension'];
		if($IS_COMMSUITE) {
			$userldap = QuickQuery("select user.ldap from user where user.login='$username'");
		} else {
			$userldap = QuickQuery("select user.ldap from user, customer where user.login='$username'
					and user.customerid = customer.id and customer.hostname = '$url'");
		}	
		
		if($IS_LDAP && $userldap){
			if(strpos('@',$username)!== false){
				$ldapusername = $username.$LDAP_EXTENSION;
			}
			if($ds=ldap_connect($LDAP_CONNECT)) {
				if(@ldap_bind($ds,$ldapusername,$password) && $password) {
					$query = "select id from user where user.login='$username'";
					return QuickQuery($query);
				} else {
					return false;
				}
				ldap_close($ds);
			}
		}
		if($password == ""){
			return false;
		}
		if (isset($url)) {
			$url = DBSafe($url);
			$query = "select u.id from user u inner join customer c on (u.customerid=c.id and c.hostname='$url') "
					."where u.enabled=1 and c.enabled=1 and u.deleted=0 and "
					."login='$username' and password=password('$password')";
		} else {
			$query = "select id from user where enabled=1 and deleted=0 and "
					."login='$username' and password=password('$password')";
		}
		return QuickQuery($query);
	}

	function doLoginPhone ($accesscode, $pin, $url = null) {
		$accesscode = DBSafe($accesscode);
		$pin = DBSafe($pin);

		if (isset($url)) {
			$url = DBSafe($url);
			$query = "select u.id from user u inner join customer c on (u.customerid=c.id and c.hostname='$url') "
					."where u.enabled=1 and c.enabled=1 and u.deleted=0 and "
					."accesscode='$accesscode' and pincode=password('$pin')";
		} else {
			$query = "select id from user where enabled=1 and deleted=0 and "
					."accesscode='$accesscode' and pincode=password('$pin')";
		}
		return QuickQuery($query);

	}

	function forceLogin ($username, $url, $customerid) {
		$username = dbsafe($username);
		$url = DBSafe($url);
		$query = "select u.id from user u inner join customer c on (u.customerid=c.id and c.hostname='$url') "
				."where u.enabled=1 and c.enabled=1 and u.deleted=0 and "
				."login='$username' and c.id=$customerid";

		return QuickQuery($query);
	}

/* instance functions */


	function setPassword ($password) {
		$query = "update user set password=password('$password') "
				."where id=$this->id";
		QuickUpdate($query);
	}

	function setPincode ($password) {
		$query = "update user set pincode=password('$password')"
				."where id=$this->id";
		QuickUpdate($query);
	}

	function authorize () {
		$features = func_get_args();
		if(isset($_SESSION['access'])) {
			foreach($features as $feature) {
				if(is_array($feature)) {
					$all = false;
					foreach($feature as $or) {
						if($_SESSION['access']->getValue($or)) {
							$all = true;
							break;
						}
					}
					if(!$all)
						return false;
				}
				elseif(!$_SESSION['access']->getValue($feature)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	function authorizeField($field) {
		$fields = $_SESSION['access']->getValue('datafields');
		return !$fields || in_array($field, explode('|', $_SESSION['access']->getValue('datafields')));
	}

	function shortName()
	{
		return ($this->firstname ? substr($this->firstname, 0, 1) . '. ' : NULL) . $this->lastname;
	}

	function rules()
	{
		return DBFindMany("Rule","from rule inner join userrule on rule.id = userrule.ruleid where userid = $this->id order by sequence");
	}

	function userSQL ($alias = NULL, $pdalias =  NULL) {
		if (!$alias){
			return "customerid=$this->customerid " . Rule::makeQuery($this->rules(), $pdalias);}
		else{
			return "$alias.customerid=$this->customerid " . Rule::makeQuery($this->rules(), $pdalias);}
	}

	function getCustomer () {
		static $customer = null;
		if ($customer == null)
			$customer = new Customer($this->customerid);

		return $customer;
	}


	//see if the login is used
	function checkDuplicateLogin ($newlogin, $customerid, $id) {
		$newlogin = DBSafe($newlogin);

		if (QuickQuery("select count(*) from user where customerid=$customerid and id!=" . (0+ $id) . " and login='$newlogin' and deleted=0") > 0 )
			return true;
		else
			return false;
	}
	//see if the accesscode is used
	function checkDuplicateAccesscode ($newaccesscode, $customerid, $id = 0) {
		$newaccesscode = DBSafe($newaccesscode);
		if (QuickQuery("select count(*) from user where customerid=$customerid and id!=$id and accesscode='$newaccesscode'") > 0)
			return true;
		else
			return false;
	}



/* user settings */

	function getSetting ($name, $defaultvalue = false, $refresh = false) {
		static $settings = null;

		if ($settings === null || $refresh) {
			$settings = array();
			if ($res = Query("select name,value from usersetting where userid='$this->id'")) {
				while ($row = DBGetRow($res)) {
					$settings[$row[0]] = $row[1];
				}
			}
		}

		if (isset($settings[$name]))
			return $settings[$name];
		else
			return $defaultvalue;
	}

	function setSetting ($name, $value) {
		$old = $this->getSetting($name,false,true);

		if ($old === false) {
			$settings[$name] = $value;
			if ($value)
				QuickUpdate("insert into usersetting (userid,name,value) values ($this->id,'" . DBSafe($name) . "','" . DBSafe($value) . "')");
		} else {
			if ($value !== false && $value !== '' && $value !== null) {
				QuickUpdate("update usersetting set value='" . DBSafe($value) . "' where userid=$this->id and name='" . DBSafe($name) . "'");
			} else {
				QuickUpdate("delete from usersetting where userid=$this->id and name='" . DBSafe($name) . "'");

			}
		}
	}

	//gets a user setting or access profile setting.
	//if no user setting exists, gets the value of the access profile, if nether exist, returns the $def param
	function getDefaultAccessPref ($setting, $def) {
		global $ACCESS;

		$profile = $ACCESS->getValue($setting);
		$pref = $this->getSetting($setting);

		if ($profile === false && $pref === false)
			return $def;
		else if ($pref !== false)
			return $pref;
		else
			return $profile;
	}


	function getCallEarly () {
		global $ACCESS;

		$profile = $ACCESS->getValue("callearly");
		$pref = $this->getSetting("callearly");

		if (!$profile && !$pref)
			return "8:00 am"; //default
		else if ($pref && $profile) {
			if (strtotime($pref) < strtotime($profile)) //use profile if pref is too early
				return $profile;
			else
				return $pref;
		} else if ($pref)
			return $pref; //no profile restriction, use pref
		else
			return $profile; //no pref, use profile
	}

	function getCallLate () {
		global $ACCESS;

		$profile = $ACCESS->getValue("calllate");
		$pref = $this->getSetting("calllate");

		if (!$profile && !$pref)
			return "9:00 pm"; //default
		else if ($pref && $profile) {
			if (strtotime($pref) > strtotime($profile)) //use profile if pref is too late
				return $profile;
			else
				return $pref;
		} else if ($pref)
			return $pref; //no profile restriction, use pref
		else
			return $profile; //no pref, use profile
	}
}

?>