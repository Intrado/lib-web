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
	
	function PeopleList ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "list";
		$this->_fieldlist = array("userid", "type", "name", "description","modifydate","lastused", "deleted");
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
	
}

?>
