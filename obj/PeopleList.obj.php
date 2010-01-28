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
		return PeopleList::makePersonSubquery(array_keys($this->getOrganizations()), array_keys($this->getSections()));
	}
	
	// Returns a subquery on the person table, taking into account the provided organizations/sections.
	// Also takes into account user organization/section restrictions.
	// If $sectionids is specified, then $organizationids is ignored because the section is more restrictive.
	static function makePersonSubquery($organizationids = false, $sectionids = false) {
		global $USER;
		
		static $associatedorganizationids = false;
		static $associatedsectionids = false;
		
		if ($associatedorganizationids === false) {
			// TODO: Need to make sure organization is not deleted?
			$associatedorganizationids = QuickQueryList("select organizationid from userassociation where type='organization' and userid = ?", false, false, array($USER->id));
		}
		
		if ($associatedsectionids === false) {
			$associatedsectionids = QuickQueryList("select sectionid from userassociation where type='section' and userid = ?", false, false, array($USER->id));
		}
		
		$findorganizations = count($associatedorganizationids) > 0 || ($organizationids && count($organizationids) > 0);
		$findsections = count($associatedsectionids) > 0 || ($sectionids && count($sectionids) > 0);
		
		if ($findorganizations || $findsections) {
			$sql = "(
				select person.*
				from person
					inner join personassociation pa on (person.id = pa.personid)
				where not person.deleted
					and ";
				
			if ($findsections) {
				$combinedsectionids = $sectionids ? array_intersect($sectionids, $associatedsectionids) : $associatedsectionids;
		
				if (count($combinedsectionids) > 0)
					$sql .= "pa.sectionid in (" . implode(",", $combinedsectionids) . ")";
				else
					$sql .= "0";
			} else {
				$combinedorganizationids = $organizationids ? array_intersect($organizationids, $associatedorganizationids) : $associatedorganizationids;
		
				if (count($combinedorganizationids) > 0)
					$sql .= "pa.organizationid in (" . implode(",", $combinedorganizationids) . ")";
				else
					$sql .= "0";
			}
			
			$sql .= ")"; // Closing parenthesis for the subquery.
		} else {
			$sql = "person";
		}
		
		return $sql;
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