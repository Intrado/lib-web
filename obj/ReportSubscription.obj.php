<?

class ReportSubscription extends DBMappedObject {
	var $userid;
	var $name = "";
	var $reportinstanceid;
	var $dow;
	var $dom;
	var $date;
	var $nextrun;
	var $time;

	//var $reportinstance; // doesnt make sence in this context, subscriptions are children of reportinstance
	var $reportschedule;

	//new constructor
	function ReportSubscription ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "reportsubscription";
		$this->_fieldlist = array("userid", "name","reportinstanceid","dow", "dom", "date", "nextrun", "time");

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
			// TODO

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