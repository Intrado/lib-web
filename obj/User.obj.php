<?
class User extends DBMappedObject {

	var $accessid = 0;
	var $login = "";
	//Do not store password
	var $accesscode;
	//Do not store pincode
	var $firstname = "";
	var $lastname = "";
	var $description = "";
	var $phone = "";
	var $email = "";
	var $aremail = "";
	var $enabled = 0;
	var $lastlogin;
	var $deleted = 0;
	var $ldap = 0;
	var $staffpkey;
	var $importid;
	var $lastimport;
	
	var $rules = false; //local cache of rules
	var $hassections = null; // local cache of boolean indicating that there are sections that the user can see.
	
	//new constructor
	function User ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "user";
		$this->_fieldlist = array("accessid", "login", "accesscode", "firstname", "lastname",
								"description", "email", "aremail", "phone", "enabled",
								"lastlogin","deleted", "ldap","staffpkey","importid","lastimport");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}


	function setPassword ($password) {
		$query = "update user set password=password(?) "
				."where id=?";
		QuickUpdate($query, false, array($password, $this->id));
	}

	function setPincode ($password) {
		$query = "update user set pincode=password(?)"
				."where id=?";
		QuickUpdate($query, false, array($password, $this->id));
	}

	// Example: authorize('sendemail', 'sendphone') returns true only if user has both permissions.
	// Example: authorize(array('sendemail', 'sendphone')) returns true if user has either permission.
	function authorize () {
		$features = func_get_args();
		if(isset($_SESSION['access'])) {
			foreach($features as $feature) {
				if(is_array($feature)) {
					$any = false;
					foreach($feature as $or) {
						if($_SESSION['access']->getValue($or)) {
							$any = true;
							break;
						}
					}
					if(!$any)
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

	// Returns true if the user is associated with this organization or is unrestricted.
	// The user is unrestricted if he has no organization associations.
	function authorizeOrganization($id) {
		$organizations = $this->organizations();
		
		return isset($organizations[$id]);
	}
	
	// Returns true if there are sections that the user is permitted to see.
	function hasSections() {
		if ($this->hassections === null) {
			// If the user cannot see any organizations then he cannot see any sections either.
			if (count($this->organizations()) < 1) {
				$this->hassections = false;
			// If the user has section associations, then he can obviously see those sections.
			} else if (QuickQuery('select sectionid from userassociation where type="section" and userid = ?', false, array($this->id))) {
				$this->hassections = true;
			// If the user does not have section associations, then find out if any of the organizations that he can see has a section.
			} else if (QuickQuery('select id from section where organizationid in ('.implode(',', array_keys($this->organizations())).')')) {
				$this->hassections = true;
			} else {
				$this->hassections = false;
			}
		}
		
		return $this->hassections;
	}
	
	function authorizeField($field) {
		$fields = $_SESSION['access']->getValue('datafields');
		return !$fields || in_array($field, explode('|', $_SESSION['access']->getValue('datafields')));
	}

	function shortName()
	{
		return ($this->firstname ? substr($this->firstname, 0, 1) . '. ' : NULL) . $this->lastname;
	}

	function getRules() {
		if ($this->rules === false)
			$this->rules = DBFindMany("Rule","from rule r inner join userassociation ua on r.id = ua.ruleid where userid =?", 'r', array($this->id));
		return $this->rules;
	}

	// Returns associated organizations or all organizations if unrestricted.
	// The user is unrestricted if he has no organization associations.
	function organizations() {
		// Global cache of users' organizations; we do not keep a local cache because
		// it would serialize upon each request.
		// This array is indexed by userid.
		global $USERSORGANIZATIONS;
		
		if (!isset($USERSORGANIZATIONS))
			$USERSORGANIZATIONS = array();
			
		if (!isset($USERSORGANIZATIONS[$this->id]) || $USERSORGANIZATIONS[$this->id] === false) {
			$associatedorganizations = DBFindMany('Organization', 'from organization o inner join userassociation ua on o.id = ua.organizationid where userid = ?', 'o', array($this->id));

			// If the user has specific organization associations, return those organizations.
			// But if the user only has section associations, he can only see the organizations of those sections.
			// Otherwise he can see all organizations.
			if (count($associatedorganizations) > 0) {
				$USERSORGANIZATIONS[$this->id] = $associatedorganizations;
			} else if (count($associatedsectionids = QuickQueryList('select sectionid from userassociation where userid = ? and type = "section"', false, false, array($this->id))) > 0) {
				$sectionorganizationids = QuickQueryList('select distinct organizationid from section where id in (' . implode(',', $associatedsectionids) . ')', false, false);
				$USERSORGANIZATIONS[$this->id] = DBFindMany('Organization', 'from organization where id in ('. implode(',', $sectionorganizationids).')');
			} else {
				$USERSORGANIZATIONS[$this->id] = DBFindMany('Organization', 'from organization where not deleted');
			}
		}
		
		return $USERSORGANIZATIONS[$this->id];
	}

	function userSQL ($alias = false) {
		$r = Rule::makeQuery($this->getRules(), $alias);
		return $r;
	}

	//see if the login is used
	function checkDuplicateLogin ($newlogin, $id) {
		if (QuickQuery("select count(*) from user where id != ? and login=? and not deleted", false, array($id, $newlogin)) > 0 )
			return true;
		else
			return false;
	}
	//see if the accesscode is used
	function checkDuplicateAccesscode ($newaccesscode, $id) {
		if (QuickQuery("select count(*) from user where id != ? and accesscode = ? and not deleted", false, array($id, $newaccesscode)) > 0)
			return true;
		else
			return false;
	}
	//see if the Staff ID is used
	function checkDuplicateStaffID ($newstaffid, $id) {
		if ($newstaffid == "") return false;

		if (QuickQuery("select count(*) from user where id != ? and staffpkey = ? and not deleted", false, array($id, $newstaffid)) > 0 )
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
				QuickUpdate("insert into usersetting (userid,name,value) values (?, ?, ?)",
					false, array($this->id, $name, $value));
		} else {
			if ($value !== false && $value !== '' && $value !== null) {
				QuickUpdate("update usersetting set value=? where userid=? and name=?",
					false, array($value, $this->id, $name));
			} else {
				QuickUpdate("delete from usersetting where userid=? and name=?",
					false, array($this->id, $name));

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
