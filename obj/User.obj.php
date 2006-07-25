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


	var $customer = null;


	//new constructor
	function User ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "user";
		$this->_fieldlist = array("accessid", "login", "accesscode", "customerid", "firstname",
								"lastname", "email", "phone", "enabled","lastlogin");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

/* static functions */

	function doLogin ($username, $password, $url = null) {
			$username = dbsafe($username);
			$password = dbsafe($password);

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

	function forceLogin ($username, $url=null) {
		$username = dbsafe($username);

		if (isset($url)) {
			$url = DBSafe($url);
			$query = "select u.id from user u inner join customer c on (u.customerid=c.id and c.hostname='$url') "
					."where u.enabled=1 and c.enabled=1 and u.deleted=0 and "
					."login='$username'";
		} else {
			$query = "select id from user where enabled=1 and deleted=0 and "
					."login='$username'";
		}
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
		if ($customer == null)
			$customer = new Customer($this->customerid);

		return $customer;
	}


	//see if the login is used
	function checkDuplicateLogin ($newlogin, $customerid, $id = 0) {
		$newlogin = DBSafe($newlogin);

		if (QuickQuery("select count(*) from user where customerid=$customerid and id!=$id and login='$newlogin'") > 0 )
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
}

?>