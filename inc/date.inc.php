<?


function reldate ($reldate) {
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
	}
	return date("m/d/Y", strtotime($targetdate));
}

$RELDATE_OPTIONS = array('today' => 'Today', 'yesterday' => 'Yesterday', 'lastweekday' => 'Last Weekday');
?>