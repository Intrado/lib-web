<?

class Customer extends DBMappedObject {

	var $name = "";
	var $logocontentid;

	//new constructor
	function Customer ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "customer";
		$this->_fieldlist = array("name","logocontentid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}




}


?>