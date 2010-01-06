<?
class JobList extends DBMappedObject {

	var $jobid;
	var $listid;

	function JobList ($id = NULL) {
		$this->_tablename = "joblist";
		$this->_fieldlist = array("jobid", "listid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	// generate thesql to search for people in this list (based on user rules, etc)
	function generateSql($userid) {
		// user rules
		$user = new User($userid);

		//get and compose list rules
		$listrules = DBFindMany("Rule","from listentry le, rule r where le.type='rule'
				and le.ruleid=r.id and le.listid='" . $this->listid .  "'", "r");

		if (count($listrules) > 0) {
			$allrules = array_merge($user->rules(), $listrules);
			$rulesql = "1 " . Rule::makeQuery($allrules, "p");
		} else {
			$rulesql = "0";
		}

		return $rulesql;
	}

}
?>
