<?
// NOTE deprecated, please use NotificationType as of ASP_9-3 release
class JobType extends DBMappedObject {

	var $name;
	var $systempriority;
	var $info;
	var $issurvey;
	var $deleted;

	function JobType($id = NULL) {
		$this->_tablename = "jobtype";
		$this->_fieldlist = array("name","systempriority","info","issurvey","deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	static function getUserJobTypes($issurvey = false) {
		global $USER;
		$surveysql = "and not issurvey ";
		if($issurvey)
			$surveysql = " and issurvey ";
		$jobtypes = DBFindMany("JobType","from jobtype jt,userjobtypes ujt where ujt.jobtypeid = jt.id and ujt.userid=$USER->id and jt.deleted=0 $surveysql order by systempriority, name","jt");

		if (count($jobtypes) == 0) {
			$jobtypes = DBFindMany("JobType","from jobtype where deleted=0 $surveysql order by systempriority desc, name");
		}

		return $jobtypes;
	}

}
?>