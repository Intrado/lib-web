<?


function reldate ($reldate, $astimestamp = false) {
	switch($reldate) {
		default:
		case "today":
			$targetdate = QuickQuery("select curdate()");
			break;
		case "yesterday":
			$targetdate = QuickQuery("select date_sub(curdate(),interval 1 day)");
			break;
		case "lastweekday":
			//1 = Sunday, 2 = Monday, ..., 7 = Saturday
			$dow = QuickQuery("select dayofweek(curdate())");

			//normally go back 1 day
			$daydiff = 1;
			//if it is sunday, go back 2 days
			if ($dow == 1)
				$daydiff = 2;
			//if it is monday, go back 3 days
			if ($dow == 2)
				$daydiff = 3;

			$targetdate = QuickQuery("select date_sub(curdate(),interval $daydiff day)");
			break;
		case "weektodate": //note that this actually just returns the first day of this week, or last sunday if today is a sunday
			$targetdate = date("Y-m-d",strtotime("last sunday"));
			break;
		case "monthtodate": //note that this actually just returns the first day of this month
			$targetdate = date("Y-m-01");
			break;
	}
	if($astimestamp)
		return strtotime($targetdate);
	else
		return date("m/d/Y", strtotime($targetdate));
}

$RELDATE_OPTIONS = array('today' => 'Today', 'yesterday' => 'Yesterday', 'lastweekday' => 'Last Weekday');

function getStartEndDate($type, $arguments = array()){
	switch($type){
		case 'today':
		case 'lastweekday':
		case 'yesterday':
			$enddate = $startdate = reldate($type, true);
			break;
		case 'weektodate':
		case 'monthtodate':
			$startdate = reldate($type, true);
			$enddate = reldate("today", true);
			break;
		case 'xdays':
			$lastxdays = 0 + $arguments['lastxdays'];
			$startdate = QuickQuery("select date_sub(curdate(),interval $lastxdays day)");
			$startdate = strtotime($startdate);
			$enddate = reldate("today", true);
			break;
		case 'daterange':
			$startdate = strtotime($arguments['startdate']);
			$enddate = strtotime($arguments['enddate']);
			break;
		default:
			$enddate = $startdate = reldate("today", true);
			break;
	}
	$res = array($startdate, $enddate);
	sort($res); //ensure between order
	return $res;
}

?>