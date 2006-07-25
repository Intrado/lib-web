<?

class UserRule extends DBMappedObject {
	var $ruleid;
	var $userid;
	var $sequence;

	function UserRule ($id = NULL) {
		$this->_tablename = "userrule";
		$this->_fieldlist = array("ruleid","userid","sequence");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>