<?
class JobList extends DBMappedObject {

	var $jobid;
	var $listid;

	function JobList ($id = NULL) {
		$this->_tablename = "joblist";
		$this->_fieldlist = array("jobid", "listid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>