<?
class JobType extends DBMappedObject {

	var $name;
	var $priority;
	var $systempriority;
	var $deleted;

	function JobType($id = NULL) {
		$this->_tablename = "jobtype";
		$this->_fieldlist = array("name", "priority","systempriority","deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function getUserJobTypes() {
		global $USER;
		$jobtypes = DBFindMany("JobType","from jobtype jt,userjobtypes ujt where ujt.jobtypeid = jt.id and ujt.userid=$USER->id and jt.deleted=0 order by priority","jt");

		if (count($jobtypes) == 0) {
			$jobtypes = DBFindMany("JobType","from jobtype where customerid=$USER->customerid and deleted=0 order by priority");
		}

		return $jobtypes;
	}

}
?>