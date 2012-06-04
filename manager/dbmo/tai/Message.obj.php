<?
class Message extends DBMappedObject{

	var $threadid;
	var $senderuserid;
	var $recipientuserid;
	var $method = "web";
	var $modifiedtimestamp;
	var $body;

	function Message($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "tai_message";
		$this->_fieldlist = array("threadid","senderuserid", "recipientuserid", "method", "modifiedtimestamp","body");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>