<?

class Publish extends DBMappedObject {

	var $userid;
	var $action;
	var $type;
	var $messagegroupid;

	function Publish ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "publish";
		$this->_fieldlist = array("userid", "action", "type", "messagegroupid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}

?>