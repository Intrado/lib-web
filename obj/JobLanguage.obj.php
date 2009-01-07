<?
class JobLanguage extends DBMappedObject {

	var $jobid;
	var $messageid;
	var $type;
	var $language;
	var $translationeditlock;

	function JobLanguage ($id = NULL) {
		$this->_tablename = "joblanguage";
		$this->_fieldlist = array("jobid", "messageid", "type", "language", "translationeditlock");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}
?>