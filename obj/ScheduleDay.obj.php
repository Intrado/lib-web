<?

class ScheduleDay extends DBMappedObject {

	var $scheduleid;
	var $dow;

	function ScheduleDay ($id = NULL) {
		$this->_tablename = "scheduleday";
		$this->_fieldlist = array("scheduleid", "dow");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>