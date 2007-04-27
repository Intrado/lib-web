<?

class Language extends DBMappedObject {

	var $name;
	
	function Language ($id = NULL) {
		$this->_tablename = "language";
		$this->_fieldlist = array("name");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>