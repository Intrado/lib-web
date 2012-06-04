<?
class UserMessage extends DBMappedObject{

	var $messageid;
	var $userid;
	var $isread = 0;
	var $isdeleted = 0;

	function UserMessage($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "tai_usermessage";
		$this->_fieldlist = array("messageid","userid", "isread", "isdeleted");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>