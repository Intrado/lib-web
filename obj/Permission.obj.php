<?

class Permission extends DBMappedObject {

	var $accessid;
	var $name;
	var $value;

	function Permission ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "permission";
		$this->_fieldlist = array("accessid","name","value");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>