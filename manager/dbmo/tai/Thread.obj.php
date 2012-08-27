<?
class Thread extends DBMappedObject{

	var $organizationid;
	var $originatinguserid;
	var $recipientuserid;
	var $topicid;
	var $parentthreadid;
	var $wassentanonymously = 0;
	var $modifiedtimestamp;
	var $threadtype;

	function Thread($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "tai_thread";
		$this->_fieldlist = array("organizationid","originatinguserid", "recipientuserid", "topicid", "parentthreadid","wassentanonymously","modifiedtimestamp","threadtype");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>