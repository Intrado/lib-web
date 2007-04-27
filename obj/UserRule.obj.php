<?

class UserRule extends DBMappedObject {
	var $ruleid;
	var $userid;

	function UserRule ($id = NULL) {
		$this->_tablename = "userrule";
		$this->_fieldlist = array("ruleid","userid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>