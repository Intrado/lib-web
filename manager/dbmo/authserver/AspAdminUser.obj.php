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
	
	// Example: authorize('sendemail', 'sendphone') returns true only if user has both permissions.
	// Example: authorize(array('sendemail', 'sendphone')) returns true if user has either permission.
	function authorized() {
		if ($this->permsarray === false)
			$this->permsarray = explode(",",$this->permissions);
		
		$features = func_get_args();
		if(isset($this->permsarray)) {
			foreach($features as $feature) {
				if(is_array($feature)) {
					$any = false;
					foreach($feature as $or) {
						if(in_array($or,$this->permsarray)) {
							$any = true;
							break;
						}
					}
					if(!$any)
						return false;
				}
				elseif(!in_array($feature,$this->permsarray)) {
					return false;
				}
			}
			return true;
		}
		return false;
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