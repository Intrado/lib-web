<?

class Sms extends DBMappedObject{

	var $personid;
	var $sms;
	var $sequence;
	var $editlock = 0;

	function Sms ($id = NULL) {
		$this->_tablename = "sms";
		$this->_fieldlist = array("personid", "sms", "sequence","editlock");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}


}