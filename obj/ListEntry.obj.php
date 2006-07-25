<?

class ListEntry extends DBMappedObject {

	var $listid;
	var $type;
	var $ruleid;
	var $personid;
	var $sequence;

	var $rule;

	function ListEntry ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "listentry";
		$this->_fieldlist = array("listid","type","ruleid","personid","sequence");
		$this->_childobjects = array("rule");
		$this->_childclasses = array("Rule");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>