<?

class Language extends DBMappedObject {

	var $customerid;
	var $name;
	var $code;
	
	function Language ($id = NULL) {
		$this->_tablename = "language";
		$this->_fieldlist = array("customerid","name","code");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>