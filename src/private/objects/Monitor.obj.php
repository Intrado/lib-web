<?

class Monitor extends DBMappedObject {

	var $userid;
	var $type;
	var $action;

	function Monitor ($id = NULL) {
		$this->_tablename = "monitor";
		$this->_fieldlist = array("userid","type","action");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>