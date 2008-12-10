<?
class JobLanguage extends DBMappedObject {

	var $jobid;
	var $messageid;
	var $type;
	var $language;

	function JobLanguage ($id = NULL) {
		$this->_tablename = "joblanguage";
		$this->_fieldlist = array("jobid", "messageid", "type", "language");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function copyNew() {
		$newjl = new JobLanguage($this->id);
		$newjl->id = null;
		$newjl->create();
		return $newjl;
	}

}
?>