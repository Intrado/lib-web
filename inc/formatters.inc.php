<?

function fmt_obj_date ($obj,$name) {
	if (isset($obj->$name) && $obj->$name != "") {
		$time = strtotime($obj->$name);
		if ($time !== -1 && $obj->$name != "0000-00-00 00:00:00")
			return date("M j, Y g:i a",$time);
	}
	return "- Never -";
}

function fmt_date ($row,$index) {
	if (isset($row[$index])) {
		$time = strtotime($row[$index]);
		if ($time !== -1 && $time !== false)
			return date("M j, Y g:i a",$time);
	}
	return "&nbsp;";
}

// Requires a not null value
function fmt_txt_date ($row, $index) {
	return date("M j, Y",strtotime($row[$index]));
}

function fmt_number ($row,$index) {
	if (isset($row[$index])) {
		return number_format($row[$index]);
	}
	return "&nbsp;";
}

function fmt_phone ($row,$index) {
	return Phone::format($row[$index]);
}

function fmt_email ($row,$index) {
	$txt = fmt_null($row,$index);
	$max = 25;
	if (strlen($txt) > $max) {
		$parts = explode("@",$txt);
		return implode("<wbr></wbr>@", $parts);
	} else
		return $txt;
}

function fmt_obj_email ($obj,$name) {
	$txt = (isset($obj->$name) ? $obj->$name : "&nbsp;");
	$max = 25;
	if (strlen($txt) > $max) {
		$parts = explode("@",$txt);
		return implode("<wbr></wbr>@", $parts);
	} else
		return $txt;
}


function fmt_null ($row,$index) {
	if (isset($row[$index]))
		return escapehtml($row[$index]);
	else
		return "&nbsp;";
}

//index+2 should be the editlock flag;
function fmt_editlocked_destination($row, $index) {
	if (isset($row[$index])) {
		$output = fmt_destination($row, $index);
		if ($row[$index+2] == 1) {
			$output = "<img src='img/padlock.gif'>&nbsp;" . $output;
		}
		return $output;
	}
	return "&nbsp;";
}

function fmt_destination ($row,$index) {
	if (isset($row[$index])) {
		if (preg_match("/[0-9]{10}/", $row[$index])) {
			return fmt_phone($row,$index);
		} else {
			return $row[$index];
		}
	}
	return "&nbsp;";
}

//used by renderedlist to format dest
function fmt_renderedlist_destination_sequence($row, $index){
	// destination type is +2 to the sequence index
	$typeindex = $index+2;
	if($row[$typeindex] != "" || $row[$typeindex] != false){
		return escapehtml(destination_label($row[$typeindex], $row[$index]));
	} else {
		return "";
	}
}

function fmt_languagecode ($row,$index) {
	return escapehtml(Language::getName($row[$index]));
}

function fmt_obj_languagecode ($obj,$name) {
	return escapehtml(Language::getName($obj->$name));	
}

function fmt_limit_25 ($row,$index) {
	$txt = fmt_null($row,$index);
	$max = 25;
	if (strlen($txt) > $max)
		return substr($txt,0,$max-3) . "...";
	else
		return $txt;
}


function fmt_percent ($row,$index) {
	if (isset($row[$index])) {
		return sprintf("%0.2f%%", $row[$index]*100);
	}
	return "&nbsp;";
}


function fmt_idmagnify ($row,$index) {
	// TODO must I load the person in order to get the person->userid ?
	$person = new Person($row[1]);
	if ($person->userid == NULL) {
		$result = "<a href=\"viewcontact.php?id=$row[1]\">  <img src=\"img/icons/diagona/16/049.gif\"></a>";
	} else {
		$result = "<a href=\"addressedit.php?id=$row[1]&origin=preview\">  <img src=\"img/icons/pencil.png\"></a>";
	}
	$result .= "&nbsp;". escapehtml($row[$index]);
	return $result;
}


function fmt_persontip ($row, $index) {
	global $USER;

	$pkey = escapehtml($row[1]);
	$personid = $row[0];
	
	if ($personid == '')
		return "";
	else
		return "<a href=\"viewcontact.php?id=$personid\" class=\"actionlink\">" 
				. "<img src=\"img/icons/diagona/16/049.gif\" /> $pkey</a>";
}


function fmt_jobs_actions ($obj, $name) {
	return action_links(jobs_actionlinks ($obj));
}

function jobs_actionlinks ($obj) {
	global $USER;

	$id = $obj->id;
	$status = $obj->status;
	$deleted = $obj->deleted;
	$type = $obj->type;

	if ($type == "survey") {
		$editbtn = action_link(_L("Edit"),"pencil","survey.php?id=$id");
		$copybtn = ''; // no copy survey feature
	} else {
		$editbtn = action_link(_L("Edit"),"pencil","job.php?id=$id");
		$copybtn = action_link(_L("Copy"),"page_copy","jobs.php?copy=$id");
	}

	$editrepeatingbtn = action_link(_L("Edit"),"pencil","jobrepeating.php?id=$id");

	$cancelbtn = action_link(_L("Cancel"),"stop","jobs.php?cancel=$id", "return confirm('Are you sure you want to cancel this job?');");

	$reportbtn = action_link(_L("Report"),"layout", $type == "survey" ? "reportsurveysummary.php?jobid=$id" : "reportjobsummary.php?jobid=$id");

	$monitorbtn = action_link(_L("Monitor"), "chart_pie", "#", "popup('jobmonitor.php?jobid=$id', 650, 450);");

	$graphbtn = action_link(_L("Graph"), "chart_pie", "#", "popup('jobmonitor.php?jobid=$id&noupdate', 650, 450);");

	$deletebtn = action_link(_L("Delete"),"cross","jobs.php?delete=$id","return confirmDelete();");

	$archivebtn = action_link(_L("Archive"),"fugue/broom","jobs.php?archive=$id");

	$runrepeatbtn = action_link(_L("Run Now"),"page_go","jobs.php?runrepeating=$id", "return confirm('Are you sure you want to run this job now?');");

	$viewresponses = action_link(_L("Responses"),"comment","replies.php?jobid=$id");

	$unarchivebtn = action_link(_L("Unarchive"),"fugue/broom__arrow","jobs.php?unarchive=$id");

	switch ($status) {
		case "new":
		case "scheduled":
		case "processing":
		case "procactive":
			if ($status != "new" && $USER->authorize('createreport')) {
				if ($type == "survey") {
					$buttons = array($editbtn, $monitorbtn, $cancelbtn);
				} else {
					$buttons = array($editbtn, $copybtn, $monitorbtn, $cancelbtn);
				}
			} else {
				if ($type == "survey") {
					$buttons = array($editbtn, $cancelbtn);
				} else {
					$buttons = array($editbtn, $copybtn, $cancelbtn);
				}
			}
			break;
		case "active":
			if($USER->authorize('createreport') && $USER->authorize('leavemessage')) {
				if ($type == "survey") {
					$buttons = array($editbtn, $reportbtn, $monitorbtn, $viewresponses, $cancelbtn);
				} else {
					$buttons = array($editbtn, $copybtn, $reportbtn, $monitorbtn, $viewresponses, $cancelbtn);
				}
			} else if($USER->authorize('leavemessage')) {
				if ($type == "survey") {
					$buttons = array($editbtn, $viewresponses, $cancelbtn);
				} else {
					$buttons = array($editbtn, $copybtn, $viewresponses, $cancelbtn);
				}
			} else if ($USER->authorize('createreport')) {
				if ($type == "survey") {
					$buttons = array($editbtn, $reportbtn, $monitorbtn, $cancelbtn);
				} else {
					$buttons = array($editbtn, $copybtn, $reportbtn, $monitorbtn, $cancelbtn);
				}
			} else {
				if ($type == "survey") {
					$buttons = array($editbtn, $cancelbtn);
				} else {
					$buttons = array($editbtn, $copybtn, $cancelbtn);
				}
			}
			break;
		case "complete":
		case "cancelled":
		case "cancelling":
			if ($deleted == 2)
				$usedelbtn = $deletebtn;
			else
				$usedelbtn = $archivebtn;
			if ($type == "alert") {
				$buttons[] = $reportbtn;
			} else {
				if ($type == "survey") {
					$buttons = array($editbtn);
				} else {
					$buttons = array($editbtn, $copybtn);
				}

				if ($USER->authorize('createreport')){
					$buttons[] = $reportbtn;
					$buttons[] = $graphbtn;
				}
				if($USER->authorize('leavemessage'))
					$buttons[] = $viewresponses;
			}
			if($deleted == 2)
				$buttons[] = $unarchivebtn;

			$buttons[] = $usedelbtn;
			break;
		case "repeating":
			$buttons = array($editrepeatingbtn, $copybtn, $runrepeatbtn, $deletebtn);
			break;
		default:
			if ($USER->authorize('createreport')) {
				if ($type == "survey") {
					$buttons = array($editbtn, $reportbtn, $graphbtn);
				} else {
					$buttons = array($editbtn, $copybtn, $reportbtn, $graphbtn);
				}
			} else {
				if ($type == "survey") {
					$buttons = array($editbtn);
				} else {
					$buttons = array($editbtn, $copybtn);
				}
			}
			break;
	}
	return $buttons;
}

function fmt_job_enddate ($obj,$name) {
	if ($obj->finishdate)
		return date("M j, Y g:i a",strtotime($obj->finishdate));
	else
		return date("M j, Y g:i a",strtotime($obj->enddate . " " . $obj->endtime));
}

function fmt_job_startdate ($obj,$name) {
	return date("M j, Y g:i a",strtotime($obj->startdate . " " . $obj->starttime));
}

function secondsToDayHourMinArray($secs)
{
	$units = array(
                "days"    =>   24*3600,
                "hours"   =>      3600,
                "minutes" =>        60,
                "seconds" =>         1,
	);

	foreach ( $units as &$unit ) {
		$quot  = intval($secs / $unit);
		$secs -= $quot * $unit;
		$unit  = $quot;
	}

	return $units;
}

// return string in format "Nd HH:MM" where N=number of days, HH hours, MM minutes
function secondsToDayHourMin($seconds) {
	// if less than one minute, return 0
	if ($seconds < 60)
	return "00:00";

	$v = secondsToDayHourMinArray($seconds);

	if ($v["days"] == 0) {
		$days = ""; // nothing to print
	} else {
		$days = $v["days"] . "d "; // pad with trailing space
	}

	if ($v["hours"] > 9) {
		$hours = $v["hours"]; // double digit
	} else {
		$hours = "0" . $v["hours"]; // pad the tens column
	}

	if ($v["minutes"] > 9) {
		$minutes = $v["minutes"]; // double digit
	} else {
		$minutes = "0" . $v["minutes"]; // pad the tens column
	}

	return $days . $hours . ":" . $minutes;
}


// assumes $row[0] is jobid
// $row[$index] is activedate
function fmt_job_first_pass($row, $index) {
	global $JOB_STATS; // [jobid][jobstatname]=value
	$statname = "complete-seconds-phone-attempt-0-sequence-0";

	// find jobid
	if (isset($JOB_STATS[$row[0]])) {
		// find stat
		if (isset($JOB_STATS[$row[0]][$statname])) {
			/*
			// job activedate as base to compute 'first pass' display
			$activedate = strtotime($row[$index]); // unix timestamp in seconds
			// add the seconds of first pass
			$startsecs = $activedate + $JOB_STATS[$row[0]][$statname];
			// format the date time
			return date("M j, Y g:i a", $startsecs);
			*/
			return secondsToDayHourMin($JOB_STATS[$row[0]][$statname]);
	    }
		// else not found
	}
	return _L("N/A");
}

function fmt_obj_job_first_pass($obj, $name) {
	$row = array();
	$row[0] = $obj->id;
	$row[1] = $obj->activedate;
	return fmt_job_first_pass($row, 1);
}

function fmt_status($obj, $name) {
	global $USER;
	if ($obj->status == 'new') {
		return _L("Not Submitted");
	} else if ($obj->status == 'procactive') {
			return _L('Processing (%1$s%%)',$obj->percentprocessed);
	} else {
		if ($obj->cancelleduserid && $obj->cancelleduserid != $USER->id) {
			$usr = new User($obj->cancelleduserid);
			return _L('Cancelled (%1$s)', $usr->login);
		} else {
			switch($obj->status) {
				case "scheduled":
					return _L("Scheduled");
				case "active":
					return _L("Active");
				case "complete":
					return _L("Complete");
				case "cancelled":
					return _L("Cancelled");
				case "cancelling":
					return _L("Cancelling");
				case "repeating":
					return _L("Repeating");
				default:
					return ucfirst($obj->status);
			}
		}
	}
}


/*
	Function to format jobs when viewed by users that might not be the job owner
	Selectively shows and hides options based on job ownership
*/
function fmt_jobs_actions_customer($row, $index) {
	global $USER;

	if ($row instanceof Job) {
		$id = $row->id;
		$status = $row->status;
		$deleted = $row->deleted;
		$jobowner = new User($row->userid);
		$jobownerlogin = $jobowner->login;
		$jobownerid = $jobowner->id;
		$type = $row->type;
	} else {
		$id = $row[$index];
		$status = $row[$index + 1];
		$deleted = $row[$index + 2];
		$jobownerlogin = $row[$index + 3];
		$jobownerid = $row[$index + 4];//change to id
		$type = $row[$index+5];
	}

	if ($USER->id == $jobownerid) {
		if ($type == 'survey') {
			$editLink = action_link(_L("Edit"),"pencil","survey.php?id=$id");
			$copyLink = ''; // no copy survey feature
		} else {
			if ($type == "notification") {
				$editLink = action_link(_L("Edit"),"pencil","job.php?id=$id");
				$copyLink = action_link(_L("Copy"),"page_copy","jobs.php?copy=$id");
			} else {
				$editLink = '';
				$copyLink = '';
			}
		}
	} elseif ($USER->authorize('manageaccount')) {
		$editLink = action_link(_L("Login as this user"),"key_go","./?login=$jobownerlogin");
		$copyLink = '';
	} else {
		$editLink= '';
		$copyLink= '';
	}

	if ($USER->authorize('viewsystemreports')) {
		$reportLink = action_link(_L("Report"),"layout", $type == "survey" ? "reportsurveysummary.php?jobid=$id" : "reportjobsummary.php?jobid=$id");
	} else {
		$reportLink = '';
	}

	if ($USER->authorize('managesystemjobs')) {
		$cancelLink = action_link(_L("Cancel"),"stop","jobs.php?cancel=$id", "return confirm('Are you sure you want to cancel this job?');");
		$archiveLink = action_link(_L("Archive"),"fugue/broom","jobs.php?archive=$id");
		$deleteLink =  action_link(_L("Delete"),"cross","jobs.php?delete=$id","return confirmDelete();");
	} else {
		$cancelLink = '';
		$archiveLink = '';
		$deleteLink = '';
	}

	if ($USER->authorize('managesystemjobs') || $USER->id == $jobownerid) {
		$runrepeatLink = action_link(_L("Run Now"),"page_go","jobs.php?runrepeating=$id", "return confirm('Are you sure you want to run this job now?');");
	} else {
		$runrepeatLink = "";
	}

	switch ($status) {
		case "new":
			return action_links($editLink,$copyLink,$cancelLink);
		case "active":
			return action_links($editLink,$copyLink,$reportLink,$cancelLink);
		case "complete":
		case "cancelled":
		case "cancelling":
			if ($deleted == 2) {
				return action_links($editLink,$copyLink,$reportLink,$deleteLink);
			} else {
				return action_links($editLink,$copyLink,$reportLink,$archiveLink);
			}
		case "repeating":
			if ($USER->id == $jobownerid) {
				$editLink = action_link(_L("Edit"),"pencil","jobrepeating.php?id=$id");
				$copyLink = action_link(_L("Copy"),"page_copy","jobs.php?copy=$id");
			}
			if ($type == "notification")
				return action_links($editLink,$copyLink,$runrepeatLink,$deleteLink);
			else
				return action_links($runrepeatLink);
		default:
			return action_links($editLink,$copyLink,$reportLink);
	}
}

function fmt_csv_list ($row,$index) {
	$data = explode(",", $row[$index]);
	$data = array_map("ucfirst",$data);
	return implode(", ", $data);
}

function fmt_obj_csv_list ($obj, $name) {
	$data = explode(",", $obj->$name);
	$data = array_map("ucfirst",$data);
	return implode(", ", $data);
}


function fmt_ucfirst($obj, $name) {
	return ucfirst($obj->$name);
}



function fmt_next_repeat($row, $index) {
	if ($row[$index] === NULL)
		$nextrun = "- Never -";
	else {
		$nextrun = date("F jS, Y h:i a", strtotime($row[$index]));
	}

	return $nextrun;
}


function fmt_nextrun ($obj, $name) {
	$nextrun = QuickQuery("select nextrun from schedule where id=$obj->scheduleid");
	if ($nextrun === NULL)
		$nextrun = "- Never -";
	else
		$nextrun = date("F jS, Y h:i a", strtotime($nextrun));
	return $nextrun;
}

function fmt_questionnairetype ($obj,$name) {
	$types = array();
	if ($obj->hasphone)
		$types[] = "Phone";
	if ($obj->hasweb)
		$types[] = "Web";
	return implode(" &amp; ", $types);
}

function fmt_surveytype ($obj,$name) {
	$questionnaire = new SurveyQuestionnaire($obj->questionnaireid);
	return fmt_questionnairetype($questionnaire,"type");
}

function fmt_numquestions ($obj,$name) {
	return QuickQuery("select count(*) from surveyquestion where questionnaireid=$obj->id");
}

function fmt_ms_timestamp($row, $index){
	if($row[$index] == "" || $row[$index] == null){
		return "";
	}
	return date("M j, Y g:i a", $row[$index]/1000);
}

function fmt_response_count($obj, $name) {
	$played = QuickQuery("Select count(*) from voicereply where jobid = '$obj->id' and listened = '0'");
	$total = QuickQuery("Select count(*) from voicereply where jobid = '$obj->id'");
	if($played > 0)
		return "<div id=" . $obj->id ." style='font-weight:bold'>". $played . "/". $total ."</div>";
	else if($total == 0)
		return "- None -";
	else
		return "<div id=" . $obj->id . ">" . $played . "/". $total . "</div>";
}

function report_name($string){
	switch($string){
		case 'jobsummaryreport':
			return "Notification Summary";
		case 'surveynotification':
			return "Survey Notification Summary";
		case 'jobdetailreport':
			return "Job Details";
		case 'phonedetail':
			return "Phone Log";
		case 'emaildetail':
			return "Email Log";
		case 'surveyreport':
			return "Survey Results";
		case 'contacthistory':
			return "Contact history";
		case 'notcontacted':
			return "Recipients Not Contacted";
		case 'jobautoreport':
			return "Auto Report";
		case 'smsdetail':
			return "SMS Log";
		case 'contactchangereport':
			return "Contact Information Changes";
		default:
			return $string;
	}
}

function fmt_result ($row,$index) {
	switch($row[$index]) {
		case "A":
			return "Answered";
		case "M":
			return "Machine";
		case "B":
			return "Busy";
		case "N":
			return "No Answer";
		case "X":
			return "Disconnect";
		case "F":
			return "Unknown";
		case "C":
			return "In Progress";
		case "blocked":
			return "Blocked";
		case "duplicate":
			return "Duplicate";
		case "nocontacts":
			return "No Contacts";
		case "sent":
			return "Sent";
		case "unsent":
			return "Unsent";
		case "notattempted":
			return "Not Attempted";
		case "undelivered":
			return "Not Contacted";
		case "declined":
			return "No Destination Selected";
		case "confirmed":
			return "Confirmed";
		case "notconfirmed":
			return "Not Confirmed";
		case "noconfirmation":
			return "No Confirmation Response";
		default:
			return ucfirst($row[$index]);
	}
}

function display_rel_date($string, $arg1="", $arg2=""){
	switch($string){
		case 'today':
			return "Today";
		case 'yesterday':
			return "Yesterday";
		case 'lastweekday':
			return "Last Week Day";
		case 'weektodate':
			return "Week to Date";
		case 'monthtodate':
			return "Month to Date";
		case 'xdays':
			return "Last $arg1 days";
		case 'daterange':
			return date("M j, Y", strtotime($arg1)) . " To: " . date("M j, Y", strtotime($arg2));
		default:
			return $string;
	}
}

function fmt_message ($row,$index) {
	//index is message type and index+1 is message name
	return '<img src="img/' . $row[$index] . '.png" align="bottom" />&nbsp;' . $row[$index+1];
}

function fmt_scheduled_date($row, $index){
	//expects the start date and end date to be sequential in the row
	$start = date("M j, Y", strtotime($row[$index]));
	$end = date("M j, Y", strtotime($row[$index+1]));
	return $start . " - " . $end;
}

function fmt_scheduled_time($row, $index){
	//expects the start time and end time to be sequential in the row
	$start = date("g:i a", strtotime($row[$index]));
	$end = date("g:i a", strtotime($row[$index+1]));
	return $start . " - " . $end;
}

function fmt_delivery_type_list($row, $index) {
	$types = explode(",", $row[$index]);
	$types = array_map("format_delivery_type", $types);
	return implode(", ", $types);
}

function fmt_obj_delivery_type_list($obj, $index) {
	$types = explode(",", $obj->$index);
	$types = array_map("format_delivery_type", $types);
	return implode(", ", $types);
}

//result formatter for job details.
//index 5 is the delivery type
function fmt_jobdetail_result($row, $index){
	if($row[$index] == "nocontacts"){
		if($row[5] == 'phone')
			return "No Phone #";
		else if($row[5] == 'email')
			return "No Email";
		else if($row[5] == 'sms')
			return "No SMS";
		else
			return "No Contacts";
	} else if($row[$index] == "declined"){
		if($row[5] == 'phone')
			return "No Phone Selected";
		else if($row[5] == 'email')
			return "No Email Selected";
		else if($row[5] == 'sms')
			return "No SMS Selected";
		else
			return "No Selected";
	} else {
		return fmt_result($row, $index);
	}
}


?>