<?

class DMResourceSchedule extends DBMappedObject {

	var $dmid;
	var $daysofweek = "2,3,4,5,6";
	var $starttime = "08:00:00";
	var $endtime = "17:00:00";
	var $resourcepercentage = 1;

	function DMResourceSchedule ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "dmschedule";
		$this->_fieldlist = array("dmid", "daysofweek", "starttime", "endtime", "resourcepercentage");
		DBMappedObject::DBMappedObject($id);
	}

}

?>