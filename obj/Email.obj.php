<?

class Email extends DBMappedObject {

	var $personid;
	var $email;
	var $sequence;
	var $editlock;

	function Email ($id = NULL) {
		$this->_tablename = "email";
		$this->_fieldlist = array("personid", "email", "sequence", "editlock");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}

?>