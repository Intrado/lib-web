<?

function classroomavailable($schedule = false) {
	if($schedule && strpos($schedule->daysofweek, (String) (Date('w',time()) + 1)) !== false  && strtotime($schedule->time) > time())
		return true;
	return false;
}

function classroomnextavailable($schedule) {
	$currenttime = time();
	$available = '';
	if($schedule && $schedule->daysofweek != "") {
		$dows = explode(',',$schedule->daysofweek);
		$today = Date('w',$currenttime) + 1;
		$next = $today % 7 + 1;
		while(!in_array($next,$dows) && $next != $today) {
			$next = $next % 7 + 1;
		}
		$weekdays = array(_L('Sunday'),_L('Monday'),_L('Tuesday'),_L('Wednesday'),_L('Thursday'),_L('Friday'),_L('Saturday'));
		echo _L('Classroom Messaging is currently unavailable until %s at 12:00 am.',$weekdays[$next - 1]);

	} else {
		echo _L('Classroom Messaging is unavailable.');;
	}
}


?>
