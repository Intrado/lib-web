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
	
	function authorizeField($field) {
		$fields = $_SESSION['access']->getValue('datafields');
		return !$fields || in_array($field, explode('|', $_SESSION['access']->getValue('datafields')));
	}

	function shortName()
	{
		return ($this->firstname ? substr($this->firstname, 0, 1) . '. ' : NULL) . $this->lastname;
	}

	function getRules() {
		// Global cache of users' rules; we do not keep a local cache because it would serialize upon each request.
		// This array is indexed by userid.
		global $USERRULES;
		
		if (!isset($USERRULES))
			$USERRULES = array();
		
		if (!isset($USERRULES[$this->id]))
			$USERRULES[$this->id] = DBFindMany("Rule","from rule r inner join userassociation ua on r.id = ua.ruleid where userid =?", 'r', array($this->id));
		
		return $USERRULES[$this->id];
	}

	// Returns associated organizations.
	function organizations() {
		// Global cache of users' organizations; we do not keep a local cache because it would serialize upon each request.
		// This array is indexed by userid.
		global $USERSORGANIZATIONS;
		
		if (!isset($USERSORGANIZATIONS))
			$USERORGANIZATIONS = array();
		
		if (!isset($USERORGANIZATIONS[$this->id]))
			$USERSORGANIZATIONS[$this->id] = DBFindMany('Organization', 'from organization o inner join userassociation ua on o.id = ua.organizationid where userid = ?', 'o', array($this->id));
		
		return $USERSORGANIZATIONS[$this->id];
	}
	
	
	/**
	 * Takes a list of organizationids or sections, and returns a SQL bit suitable for adding in to a query on the person table as an optional join.
	 * Filters out any sections or organizations the user doesn't have access to, making join as simple as possible.
	 * $searchorgids and $searchsectionids are mutually exclusive, only one should be specified.
	 * The joinsql may cause an impossible query if all of the criteria are invalid.
	 * Calling code should verify that at least one restriction exists by checking that rules, orgs, sections are not all empty.
	 * Note that of course adding a join to person may result in duplicate records returned due to nature of joins.
	 * 
	 * @param $searchorgids list of organization ids to search/filter on.
	 * @param $searchsectionids list of sectionids to search/filter on. 
	 * @param $personalias alias of the person table, false if not aliased
	 * @return string containing a join clause to the person table on personassociation
	 */
	function getPersonAssociationJoinSql ($searchorgids = array(), $searchsectionids = array(), $personalias = false) {
		
		//put orgids in indexed array
		if (count($searchorgids) > 0) {
			$searchorgids = array_fill_keys($searchorgids,true);
		}
		
		//load user restriction info on orgs, sections	
		$userorgids = QuickQueryList("select organizationid,1 from userassociation where type='organization' and userid = ?",true,false,array($this->id));
		$usersectionorgs = QuickQueryList("select ua.sectionid, s.organizationid from userassociation ua inner join section s on (s.id=ua.sectionid) where ua.userid = ?",true,false,array($this->id));

		$isunrestricted = count($userorgids) == 0 && count($usersectionorgs) == 0;
		$aliasid = ($personalias ? $personalias . ".id" : "person.id");

		$joinsql = "";
		
		// ############## Section Mode ##############
		if (count($searchsectionids) > 0) {
			
			//first load org info for all searcsectionids
			$tmp = QuickQueryList("select id, organizationid from section where id in (" . DBParamListString(count($searchsectionids)) . ")" ,true,false,$searchsectionids);
			//create new array with sectionids as keys, all pointing to zeros. then fill in any found orgids. sections not found still point to zero.
			$filtersectionids = array_fill_keys($searchsectionids,0); //any unfound sections will have orgid=0
			foreach ($tmp as $sectionid => $orgid)
				$filtersectionids[$sectionid] = $orgid;
			
			//check all specified sections against user restrictions
			if (!$isunrestricted) {
				foreach ($filtersectionids as $sectionid => $orgid) {
					
					//see if the sectionid matches one of the user's sections
					if (isset($usersectionorgs[$sectionid]))
						continue;
					//see if this section is a child of any of the user orgs
					if (isset($userorgids[$orgid]))
						continue;
					unset($filtersectionids[$sectionid]);
				}
			}
			if (count($filtersectionids) == 0)
				$joinsql = "inner join personassociation pa on (0) /* bad sections */ "; //impossible query, we must have removed everything they were looking for!
			else
				$joinsql = "inner join personassociation pa on (pa.personid = $aliasid "
						. "and pa.sectionid in (" . implode(",",array_keys($filtersectionids)) . "))";
		
		// ############## Organization Mode ##############
		} else if (count($searchorgids) > 0) {
			
			$joinsql = "inner join personassociation pa on (pa.personid = $aliasid";
			
			$joinorgids = array();
			$joinsectionids = array();
			
			//if user is unrestricted, just add all orgs
			if ($isunrestricted) {
				$joinorgids = array_keys($searchorgids);
			} else {	
				//if user is restricted to some orgs, add all matching orgs
				foreach ($searchorgids as $orgid => $dummy) {
					if (isset($userorgids[$orgid])) {
						//add to joinorgids and remove from search (so we can find leftovers as sections)
						$joinorgids[] = $orgid;
						unset($searchorgids[$orgid]);
					}
				}
				//downgrade any remaining search orgs to sections that match user restrictions
				foreach ($usersectionorgs as $sectionid => $orgid) {
					if (isset($searchorgids[$orgid]))
						$joinsectionids[] = $sectionid;
					
				}
			}
			
			$components = array();
			if (count($joinorgids) > 0)
				$components[] = "pa.organizationid in (" . implode(",",$joinorgids) . ")";	
			if (count($joinsectionids) > 0)
				$components[] = "pa.sectionid in (" . implode(",",$joinsectionids) . ")";	
			
			if (count($components) == 0)
				$joinsql .= " and 0 "; //impossible query, nothing valid to search on
			else
				$joinsql .= " and ( " . implode(" or ", $components) . " ) ";
			
			$joinsql .= ")";
			
		// ############## All Mode ##############
		} else {
			//list has no org/section search, join on just user restrictions
			if ($isunrestricted) {
				$joinsql = ""; //no join needed if unrestricted user on unrestricted list (note: something else needs to make sure they at least have a rule somewhere)
			} else {
				$joinsql = "inner join personassociation pa on (pa.personid = $aliasid";
				
				$components = array();
				if (count($userorgids) > 0)
					$components[] = "pa.organizationid in (" . implode(",",array_keys($userorgids)) . ")";	
				if (count($usersectionorgs) > 0)
					$components[] = "pa.sectionid in (" . implode(",",array_keys($usersectionorgs)) . ")";	
				
				//$components can't be empty if user is unrestricted, dont need to check again
				$joinsql .= " and ( " . implode(" or ", $components) . " ) )";
			}
		}
		
		return $joinsql;
	}
	
	/**
	 * Returns sql for rules specified combined with rules from user restriction.
	 * Calling code should verify that at least one restriction exists by checking that rules, orgs, sections are not all empty.
	 * 
	 * @param $personalias alias of the person table, false if not aliased
	 * @param $searchrules array of Rule objects to add to the user's rules (ie for lists, etc)
	 * @param $isreport true if query should be using report tables for historic data
	 * @return unknown_type
	 */
	function getRuleSql ($searchrules = array(), $personalias = false, $isreport = false) {
		return Rule::makeQuery(array_merge($this->getRules(), $searchrules), $personalias, false, $isreport);
	}
	
	
	/**
	 * Checks to see if the specified personid is visible to the user. Checks addressbook, orgs, and rules.
	 * @param $personid
	 * @return unknown_type
	 */
	function canSeePerson ($personid) {
		$joinsql = $this->getPersonAssociationJoinSql(array(), array(), "p");
		$rulesql = $this->getRuleSql(array(), "p");
		$query = "select 1 from person p \n"
				."	$joinsql \n"
				."	where p.id=? and (p.userid=0 OR p.userid=? or (1 $rulesql))  \n";
		
		return QuickQuery($query,false,array($personid,$this->id));
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
