<?

class Address extends DBMappedObject {

	var $personid;
	var $addr1;
	var $addr2;
	var $city;
	var $state;
	var $zip;
	var $addressee;

	function Address ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "address";
		$this->_fieldlist = array("personid", "addr1", "addr2", "city", "state", "zip", "addressee");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}

?>