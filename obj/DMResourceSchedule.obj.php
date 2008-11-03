<?

class DMResourceSchedule extends DBMappedObject {

	var $dmid;
	var $daysofweek;
	var $starttime;
	var $endtime;
	var $resourcepercentage = 1;

	function DMResourceSchedule ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "dmschedule";
		$this->_fieldlist = array("dmid", "daysofweek", "starttime", "endtime", "resourcepercentage");
		DBMappedObject::DBMappedObject($id);
	}

}

?>