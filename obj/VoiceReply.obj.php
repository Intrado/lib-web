<?
class VoiceReply extends DBMappedObject{

	var $jobtaskid;
	var $jobworkitemid;
	var $personid;
	var $jobid;
	var $userid;
	var $customerid;
	var $contentid;
	var $replytime;
	var $listened = 0;


	function VoiceReply($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "voicereply";
		$this->_fieldlist = array("jobtaskid", "jobworkitemid", "personid",
									"jobid", "userid", "customerid", "contentid", "replytime", "listened");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);

	}

	/**static functions**/

}

?>