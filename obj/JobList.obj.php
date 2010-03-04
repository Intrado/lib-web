<?
class JobList extends DBMappedObject {

	var $jobid;
	var $listid;
	var $thesql;

	function JobList ($id = NULL) {
		$this->_tablename = "joblist";
		$this->_fieldlist = array("jobid", "listid", "thesql");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>