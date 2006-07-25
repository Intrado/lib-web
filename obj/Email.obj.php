<?

class Email extends DBMappedObject {

	var $personid;
	var $email;
	var $sequence;

	function Email ($id = NULL) {
		$this->_tablename = "email";
		$this->_fieldlist = array("personid", "email", "sequence");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}

?>