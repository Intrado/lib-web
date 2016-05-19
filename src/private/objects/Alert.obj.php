<?
class Alert extends DBMappedObject {
	var $eventid;
	var $personid;
	var $date;
	var $time;
	function Alert ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "alert";
		$this->_fieldlist = array(
			"eventid",
			"personid",
			"date",
			"time"
		);

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>
