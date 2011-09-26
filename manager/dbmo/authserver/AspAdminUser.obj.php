<?
class AspAdminUser extends DBMappedObject{

	var $firstname = "";
	var $lastname = "";
	var $email = "";
	var $login = "";
	var $preferences = "";
	var $permissions = "";
	var $queries = "";
	
	var $prefsarray = false;
	var $permsarray = false;
	var $queryarray = false;
	
	var $deleted = 0;
	
	function AspAdminUser($id = NULL){
		$this->_allownulls = true;
		$this->_tablename = "aspadminuser";
		$this->_fieldlist = array("firstname","lastname", "email", "login","preferences","permissions","queries","deleted");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	function authorized($auth) {
		if ($this->permsarray === false)
			$this->permsarray = explode(",",$this->permissions);
		return in_array($auth,$this->permsarray) ? true : false;
	}
	function authorizedAny($auths) {
		if ($this->permsarray === false)
			$this->permsarray = explode(",",$this->permissions);
			
		foreach ($auths as $auth)
			if (in_array($auth,$this->permsarray))
				return true;
		
		return false;
	}
	
	function preference($pref) {
		if ($this->prefsarray === false)
			$this->prefsarray = unserialize($this->preferences);
		return isset($this->prefsarray[$pref]) ? $this->prefsarray[$pref] : false;
	}
	
	function setPreference($pref,$value) {
		if ($this->prefsarray === false)
			$this->prefsarray = unserialize($this->preferences);
		$this->prefsarray[$pref] = $value;
		$this->preferences = serialize($this->prefsarray);
	}
	
	function addFavCustomer ($cid) {
		$fav = $this->preference("favcustomers");
		if (!$fav)
			$fav = array();
		$fav[] = $_GET["addfavorites"] + 0;
		$fav = array_unique($fav);
		$this->setPreference("favcustomers",$fav);
		$this->update();
	}
	
	function delFavCustomer ($cid) {
		$fav = $this->preference("favcustomers");
		if (is_array($fav) && ($k = array_search($cid + 0,$fav)) !== false) {
			unset($fav[$k]);
			$this->setPreference("favcustomers",$fav);
			$this->update();
		}
	}
	
	
	function authQuery($queryid) {
		if ($this->queryarray === false)
			$this->queryarray = explode(",",$this->queries);
		
		if ($this->queries == "unrestricted" || in_array($queryid,$this->queryarray))
			return true;
		
		return false;
	}
	

	/**static functions**/

	function doLogin($login, $password) {
		$query = "SELECT id FROM aspadminuser WHERE login=?
					AND password=password(?)";
		return QuickQuery($query, false, array($login, $password));
	}
}

?>