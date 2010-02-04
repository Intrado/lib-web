<?

class PeopleList extends DBMappedObject {

	var $userid;
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
		$this->_fieldlist = array("userid", "name", "description","modifydate","lastused", "deleted");
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
	
	// Returns a subquery on the person table, taking into account organization/section listentries.
	// Also takes into account user organization/section restrictions.
	function getPersonSubquerySQL() {
		return Person::makePersonSubquery(array_keys($this->getOrganizations()), array_keys($this->getSections()));
	}
	
	function getListRuleSQL () {
		//get and compose list rules
		$listrules = $this->getListRules();

		if (count($listrules) > 0)
			$listsql = "1" . Rule::makeQuery($listrules, "p");
		else if (count($this->getOrganizations()) == 0 && count($this->getSections()) == 0)
			$listsql = "0"; //dont assume anyone is in the list if there are no restrictions on rules, organizations, or sections.
		else {
			// TODO: Need to restrict by list's organization and section sql
			$listsql = "2";
		}
		
		return $listsql;
	}
}

?>