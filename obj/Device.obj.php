<?

class Device extends DBMappedObject {

	var $personId;
	var $deviceUuid;
	var $sequence;

	function Device ($id = NULL) {
		$this->_tablename = "device";
		$this->_fieldlist = array("personId", "deviceUuid", "sequence");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>