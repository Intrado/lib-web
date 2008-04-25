<?

class DMCallerIDRoute extends DBMappedObject {

	var $dmid;
	var $callerid = "";
	var $prefix = "";

	function DMCallerIDRoute ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "dmcalleridroute";
		$this->_fieldlist = array("dmid", "callerid", "prefix");
		DBMappedObject::DBMappedObject($id);
	}

}

?>