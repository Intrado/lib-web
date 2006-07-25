<?

class Voice extends DBMappedObject {

	var $ttsname;
	var $name;
	var $language;
	var $gender;

	function Voice ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "ttsvoice";
		$this->_fieldlist = array("ttsname", "name", "language", "gender");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>