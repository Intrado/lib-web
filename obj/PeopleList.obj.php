<?

class PeopleList extends DBMappedObject {
	var $userid;
	var $type = "person"; // enum ('person','section','alert')
	var $name;
	var $description;
	var $modifydate;
	var $lastused;
	var $deleted;

	var $rules = false; // Local cache.
	var $organizations = false; // Local cache.
	var $sections = false; // Local cache.
	var $restrictedGuardianCategoryIds = false; // local cache, array of guardian category ids
	
	var $recipientmode = 'selfAndGuardian';
	public static $RECIPIENTMODE_MAP = array(1 => 'self', 2 => 'guardian', 3 => 'selfAndGuardian');

	function PeopleList ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "list";
		$this->_fieldlist = array("userid", "type", "name", "recipientmode", "description","modifydate","lastused", "deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function getListRules() {
		if ($this->rules === false) {
			$this->rules = DBFindMany("Rule",
				"from rule r inner join listentry le on (r.id = le.ruleid) where le.listid = ?",
				"r",
				array($this->id));
		}
		
		return $this->rules;
	}
	
	function getRestrictedGuardianCategoryIds() {
		if ($this->restrictedGuardianCategoryIds === false) {
			$this->restrictedGuardianCategoryIds = QuickQueryList("select guardiancategoryid from listguardiancategory where listid = ?", false, false, array($this->id));
		}
		return $this->restrictedGuardianCategoryIds;
	}
	
	function getOrganizations() {
		if ($this->organizations === false) {
			$this->organizations = DBFindMany("Organization",
				"from organization o inner join listentry le on (o.id = le.organizationid) where le.listid = ?",
				"o",
				array($this->id));
		}
		
		return $this->organizations;
	}
	
	function getSections() {
		if ($this->sections === false) {
			$this->sections = DBFindMany("Section",
				"from section s inner join listentry le on (s.id = le.sectionid) where le.listid = ?",
				"s",
				array($this->id));
		}
			
		return $this->sections;
	}
	
	
	function countRemoved () {
		$query = "select count(*) from listentry le inner join person p on (p.id = le.personid) where le.type='negate' and le.listid = ?";
		return QuickQuery($query, false, array($this->id));
	}

	function countAdded () {
		$query = "select count(*) from listentry le inner join person p on (p.id = le.personid and not p.deleted) where  le.type='add' and le.listid = ?";
		return QuickQuery($query, false, array($this->id));
	}
	
	function updateManualAddByPkeys($pkeys, $removeExisting = true) {
		global $USER;
		
		// find all personids
		$temppersonids = array();
		foreach ($pkeys as $pkey) {
			if ($pkey == "")
				continue;
			// only allow system contacts (not guardians)
			$p = DBFind("Person","from person where pkey=? and type='system'", false, array($pkey));
			if ($p && $USER->canSeePerson($p->id)) {
				//use associative array to dedupe pids
				$temppersonids[$p->id] = 1;
			}
		}

		// sync up the ids
		if ($removeExisting) {
			$oldids = QuickQueryList("select p.id from person p, listentry le where p.id=le.personid and le.type='add' and p.userid is null and le.listid=$this->id");
			$personids = array_keys($temppersonids);
			$addids = array_diff($personids, $oldids);
			$deleteids = array_diff($oldids, $personids);
			if (count($deleteids) > 0) {
				$query = "delete from listentry where personid in ('" . implode("','",$deleteids) . "') and listid = " . $this->id;
				QuickUpdate($query);
			}
		}
		else {
			$addids = array_keys($temppersonids);
		}

		if (count($addids) > 0) {
			$query = "insert into listentry (listid, type, personid) values ($this->id,'add','" . implode("'),($this->id,'add','",$addids) . "')";
			QuickUpdate($query);
		}

		// return number of system contacts manually added to list
		return count($temppersonids);
	}
	
	function softDelete() {
		$isSuccess = false;
		if (userOwns("list",$this->id) && $this->type != 'alert') {
			Query("BEGIN");
			QuickUpdate("update list set deleted=1 where id=?", false, array($this->id));
			$isSuccess = true;
			// if there are any publish records for this list, remove them
			if (isPublished('list', $this->id)) {
				$publications = DBFindMany("Publish", "from publish where type = 'list' and listid = ?", false, array($this->id));
				foreach ($publications as $publish)
					$publish->destroy();
			}
			Query("COMMIT");
		} else {
			$isSuccess = false;
		}
		return $isSuccess;
	}
}

?>
