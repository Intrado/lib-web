<?

class Schedule extends DBMappedObject {

	var $userid;
	var $triggertype;
	var $type;
	var $dow;
	var $time;
	var	$nextrun;

	function Schedule ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "schedule";
		$this->_fieldlist = array("userid", "triggertype", "type", "dow", "time", "nextrun");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function calcNextRun () {
		$enableddows = explode(",", $this->dow);

		$nextrun = Date("Y-m-d") ." " . $this->time;

		for ($x = 0; $x < 8 ; $x++) {
			$query = "select convert('$nextrun', datetime) > now(), dayofweek('$nextrun')";

			list($islater, $nextrundow) = QuickQueryRow($query);
			if ($islater && in_array($nextrundow, $enableddows))
				return $nextrun;

			$nextrun = QuickQuery("select date_add('$nextrun',interval 1 day)");
		}

		return NULL;
	}
}

?>