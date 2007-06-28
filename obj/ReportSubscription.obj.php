<?

class ReportSubscription extends DBMappedObject {
	var $userid;
	var $name = "";
	var $description;
	var $reportinstanceid;
	var $dow;
	var $dom;
	var $date;
	var $lastrun;
	var $nextrun;
	var $time;


	//var $reportinstance; // doesnt make sence in this context, subscriptions are children of reportinstance
	var $reportschedule;

	//new constructor
	function ReportSubscription ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "reportsubscription";
		$this->_fieldlist = array("userid", "name", "description", "reportinstanceid","dow", "dom", "date", "lastrun", "nextrun", "time");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function setReportInstance ($reportinstanceobj) {
		//$this->$reportinstance = $reportinstanceobj; // doesnt make sence in this context, subscriptions are children of reportinstance
		$this->reportinstanceid = $reportinstanceobj->id;
	}

	function calcNextRun () {
		if ($this->date != null) {
			$nextrun = $this->date ." " . $this->time;
			return $nextrun;

		} else if ($this->dom != null) {

			$nextrun = Date("Y-m-d");
			// TODO if day is later than today, take from first of current month, otherwise last_day into next month
			$nextrun = QuickQuery("select last_day('$nextrun')");
			$nextrun = QuickQuery("select date_add('$nextrun', interval ".$this->dom." day)") ." " . $this->time;
			return $nextrun;

		} else if ($this->dow != null) {
			$enableddows = explode(",", $this->dow);
			$nextrun = Date("Y-m-d") ." " . $this->time;

			for ($x = 0; $x < 8 ; $x++) {
				$query = "select convert('$nextrun', datetime) > now(), dayofweek('$nextrun')";

				list($islater, $nextrundow) = QuickQueryRow($query);
				if ($islater && in_array($nextrundow, $enableddows))
					return $nextrun;

				$nextrun = QuickQuery("select date_add('$nextrun',interval 1 day)");
			}
		}
		return NULL;
	}

}

?>