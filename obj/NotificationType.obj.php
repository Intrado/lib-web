<?
class NotificationType extends DBMappedObject {

	var $name;
	var $systempriority;
	var $info;
	var $deleted;
	var $type;

	function NotificationType($id = NULL) {
		$this->_tablename = "notificationtype";
		$this->_fieldlist = array("name","systempriority","info","deleted","type");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	static function getUserJobTypes($issurvey = false) {
		global $USER;
		$typesql = "and type = 'job' ";
		if($issurvey)
			$typesql = " and type = 'survey' ";
		$jobtypes = DBFindMany("NotificationType","from notificationtype jt, userjobtypes ujt where ujt.jobtypeid = jt.id and ujt.userid=$USER->id and jt.deleted=0 $surveysql order by systempriority, name","jt");

		if (count($jobtypes) == 0) {
			$jobtypes = DBFindMany("NotificationType","from notificationtype where deleted=0 $surveysql order by systempriority desc, name");
		}

		return $jobtypes;
	}

}
?>