<?

class ReportSubscription extends DBMappedObject {
	var $userid;
	var $name = "";
	var $description;
	var $reportinstanceid;
	var $type;
	var $daysofweek;
	var $dayofmonth;
	var $lastrun;
	var $nextrun;
	var $time;
	var $modifydate;
	var $email;

	var $reportschedule;

	//new constructor
	function ReportSubscription ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "reportsubscription";
		$this->_fieldlist = array("userid", "name", "description", "reportinstanceid","type","daysofweek","dayofmonth", "lastrun", "nextrun", "time", "modifydate", "email");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function setReportInstance ($reportinstanceobj) {
		//$this->$reportinstance = $reportinstanceobj; // doesnt make sence in this context, subscriptions are children of reportinstance
		$this->reportinstanceid = $reportinstanceobj->id;
	}

	function calcNextRun () {
		if ($this->type == 'once') {
			return $this->nextrun; // once time should be calculated by the GUI

		} else if ($this->type == 'monthly') {

			$nextrun = Date("Y-m-d");

			// if -1 treat as 'end of month'
			if ($this->dayofmonth == -1) {
				$today = $nextrun;
				$nextrun = QuickQuery("select last_day('$nextrun')");
				// if today is the end of month, set for next month
				if ($nextrun == $today) {
					$nextrun = QuickQuery("select date_add('$nextrun', interval 1 day)");
					$nextrun = QuickQuery("select last_day('$nextrun')");
				}
			} else {
				// if day is later than today, take from first of current month, otherwise last_day into next month
				$currentdayofmonth = QuickQuery("select dayofmonth('$nextrun')");
				if ($currentdayofmonth < $this->dayofmonth) {
					$diff = $this->dayofmonth - $currentdayofmonth;
					$nextrun = QuickQuery("select date_add('$nextrun', interval ".$diff." day)");
				} else {
					$nextrun = QuickQuery("select last_day('$nextrun')");
					$nextrun = QuickQuery("select date_add('$nextrun', interval ".$this->dayofmonth." day)");
				}
			}
			return $nextrun ." " . $this->time;

		} else if ($this->type == 'weekly') {

			$enableddows = explode(",", $this->daysofweek);
			$nextrun = Date("Y-m-d") ." " . $this->time;

			for ($x = 0; $x < 8 ; $x++) {
				$query = "select convert('$nextrun', datetime) > now(), dayofweek('$nextrun')";

				list($islater, $nextrundow) = QuickQueryRow($query);
				if ($islater && in_array($nextrundow, $enableddows))
					return $nextrun;

				$nextrun = QuickQuery("select date_add('$nextrun',interval 1 day)");
			}
		}
		return NULL; // null means notscheduled
	}

	function createDefaults($name){
		global $USER;
		$this->name = $name . " " . date("M j, Y g:i a", strtotime("now"));;
		$this->type = "notscheduled";
		$this->userid = $USER->id;
		$this->description = "";
		$this->email = $USER->email;
	}

}

?>