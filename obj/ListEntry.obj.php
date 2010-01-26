<?

class ListEntry extends DBMappedObject {

	var $listid;
	var $type; // 'rule', 'organization', 'section'
	var $ruleid;
	var $personid;
	var $organizationid;
	var $sectionid;

	var $rule;

	function ListEntry ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "listentry";
		$this->_fieldlist = array("listid","type","ruleid","personid","organizationid","sectionid");
		$this->_childobjects = array("rule");
		$this->_childclasses = array("Rule");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>
