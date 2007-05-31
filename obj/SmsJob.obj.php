<?

class SmsJob extends DBMappedObject {

	var $userid;
	var $listid;
	var $name;
	var $description;
	var $txt;
	var $sendoptout;
	var $sentdate;
	var $status;
	var $deleted = 0;

	function SmsJob ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "smsjob";
		$this->_fieldlist = array("userid","listid", "name", "description", "txt", "sendoptout", "sentdate", "status","deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>