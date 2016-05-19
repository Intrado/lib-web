<?

class UserSetting extends DBMappedObject {

	var $userid;
	var $name;
	var $value;

	function UserSetting ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "usersetting";
		$this->_fieldlist = array("userid", "name", "value");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>