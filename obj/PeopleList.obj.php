<?

class PeopleList extends DBMappedObject {

	var $userid;
	var $name;
	var $description;
	var $lastused;
	var $deleted;

	function PeopleList ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "list";
		$this->_fieldlist = array("userid", "name", "description", "lastused", "deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function getListRuleSQL () {
		//get and compose list rules
		$listrules = DBFindMany("Rule","from listentry le, rule r where le.type='R'
				and le.ruleid=r.id and le.listid='" . $this->id .  "'", "r");

		if (count($listrules) > 0)
			$listsql = "1" . Rule::makeQuery($listrules, "p");
		else
			$listsql = "0";//dont assume anyone is in the list if there are no rules
		return $listsql;
	}
}

?>