<?

class ReportSchedule extends DBMappedObject {

	var $name = "";
	var $shared = 0;

	var $nextrun;
	var $scheduletype;

	var $callscheduleid = 0;

	//new constructor
	function ReportSchedule ($id = NULL) {
		$this->_tablename = "reportschedule";
		$this->_fieldlist = array("name","shared","nextrun", "scheduletype", "callscheduleid");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function getNextRun ($timefrom=NULL) {
		
		$nextrun = false;
		
		if ($timefrom==NULL)
			$timefrom = QuickQuery("select now()");
//		echo "scheduletype $this->scheduletype\n";
		//what scheduletype?
		switch ($this->scheduletype) {
			
			case "C":
				
				//get the call schedule for this
				$query = "select SunValidDay, MonValidDay, TueValidDay, WedValidDay, 
								ThuValidDay, FriValidDay, SatValidDay, 
								SunStartTime, MonStartTime, TueStartTime, WedStartTime, 
								ThuStartTime, FriStartTime, SatStartTime, 
								SunEndTime, MonEndTime, TueEndTime, WedEndTime, 
								ThuEndTime, FriEndTime, SatEndTime, 
								ExpireDays, Temporary, Deleted, CreateDate, ModifyDate
				from callschedule where callscheduleid = $this->callscheduleid";
				
				$row = QuickQueryRow($query, true);
								
				$dow = array (1 =>"Sun", 2 =>"Mon", 3=>"Tue", 4=>"Wed", 5=>"Thu", 6=>"Fri", 7=>"Sat");
				foreach ($dow as $day) {
//					echo "$day ValidDay = " . $row[$day . "ValidDay"] . "\n";
					if ($row[$day . "ValidDay"]) {
						$sched[$day] = $row[$day . 'EndTime'];
					} else {
						$sched[$day] = NULL;
					}
				}
				
				//check all of the days starting from the curent day of the timefrom
				$curdow = $timefromdow = QuickQuery("select dayofweek('$timefrom')");
				$timefromdate = substr($timefrom,0,10);
				
//				echo "timefromdate $timefromdate\n";
				
				$count = 0;			
				//see if it is later today
				if ($endtime = $sched[$dow[$curdow]]) {
					$testtime = $timefromdate . " " . $endtime;
					
//					echo "today testime = $testtime\n";
//					echo "timefrom $timefrom\n";
					//offset nextime by 5 minutes and check
					$query = "select convert('$testtime', datetime) > '$timefrom'";
					if (QuickQuery($query)) {
//						echo "it is later today\n";
						$nextrun = $testtime;
					} else {
						$curdow++;//dont check today again
						$count = 1;
					}
				}


				while ($count < 8) {
					if ($endtime = $sched[$dow[$curdow]]) {

						$daydiff = $count;//makes sense
						$query = "select date_add('$timefromdate', INTERVAL $daydiff day)";
						$testtime = QuickQuery($query);
						$testtime .= " " . $endtime;
					
						$nextrun = $testtime;
						break;
					}

					$count++;
					$curdow++;
					if ($curdow > 7) {
						$curdow -= 7;
					}
				}
				
				//will never run
				if ($count == 8)
					return false;
				
				break;
		}
		
		
		return $nextrun;
	}

	function updateNextRun ($timefrom=NULL) {

		// calls getNextRun
		$this->nextrun = $this->getNextRun ($timefrom);

		// update this object
		$this->update();
	}

	function setScheduleType ($schedtype="T") {
		$this->scheduletype = $schedtype;
	}

}
?>