<?
class VoiceReply extends DBMappedObject{

	var $jobid;
	var $userid;
	var $contentid;
	var $replytime;
	var $listened = 0;


	function VoiceReply($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "voicereply";
		$this->_fieldlist = array("personid",
									"jobid", "userid", "contentid", "replytime", "listened");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);

	}

	/**static functions**/

}

?>