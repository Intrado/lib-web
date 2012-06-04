<?
class UserThread extends DBMappedObject{

	var $userid;
	var $threadid;
	var $folderid;
	var $isdeleted = 0;

	function UserThread($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "tai_userthread";
		$this->_fieldlist = array("userid","threadid", "folderid", "isdeleted");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>