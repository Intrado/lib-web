<?
class Setting extends DBMappedObject {

	var $name;
	var $value;

	function Setting($id = NULL) {
		$this->_tablename = "setting";
		$this->_fieldlist = array("name", "priority");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}
?>