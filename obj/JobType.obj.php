<?
class JobType extends DBMappedObject {

	var $name;
	var $priority;
	var $systempriority;
	var $timeslices;
	var $infoforparents;
	var $issurvey;
	var $deleted;

	function JobType($id = NULL) {
		$this->_tablename = "jobtype";
		$this->_fieldlist = array("name", "priority","systempriority","timeslices","infoforparents","issurvey","deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	static function getUserJobTypes($issurvey = false) {
		global $USER;
		$surveysql = "and not issurvey ";
		if($issurvey)
			$surveysql = " and issurvey ";
		$jobtypes = DBFindMany("JobType","from jobtype jt,userjobtypes ujt where ujt.jobtypeid = jt.id and ujt.userid=$USER->id and jt.deleted=0 $surveysql order by name","jt");

		if (count($jobtypes) == 0) {
			$jobtypes = DBFindMany("JobType","from jobtype where deleted=0 $surveysql order by priority");
		}

		return $jobtypes;
	}

}
?>