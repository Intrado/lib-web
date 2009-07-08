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

function fmt_destination ($row,$index) {
	if (isset($row[$index])) {
		if (ereg("[0-9]{10}", $row[$index])) {
			return fmt_phone($row,$index);
		} else {
			return $row[$index];
		}
	}
	return "&nbsp;";
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

function fmt_checkbox_addrbook($row,$index) {
	global $inlistids;

	$result = '<div align="center">';
	//see if it is in add show +, otherwise show blank
	if (in_array($row[0],$inlistids)) {
		$result .= '<img src="img/checkbox-add.png"';
		$checked = true;
	} else {
		$result .= '<img src="img/checkbox-clear.png"';
		$checked = false;
	}
	$result .= " onclick=\"dolistbox(this,'add',";

	$result .=  (($checked) ? "true":"false") . "," . $row[0] . ');" />';
	return $result . '</div>';
}

function fmt_checkbox($row,$index) {
	global $renderedlist;

	$result = '<div align="center">';
	if (in_array($row[1],$renderedlist->pageruleids)) {
		//see if it is in remove show -, otherwise show R
		if (in_array($row[1],$renderedlist->pageremoveids)) {
			$result .= '<img src="img/checkbox-remove.png"';
			$checked = true;
		} else {
			$result .= '<img src="img/checkbox-rule.png"';
			$checked = false;
		}
		$result .= " onclick=\"dolistbox(this,'remove',";
	} else {
		//see if it is in add show +, otherwise show blank
		if (in_array($row[1],$renderedlist->pageaddids)) {
			$result .= '<img src="img/checkbox-add.png"';
			$checked = true;
		} else {
			$result .= '<img src="img/checkbox-clear.png"';
			$checked = false;
		}
		$result .= " onclick=\"dolistbox(this,'add',";
	}
	$result .=  (($checked) ? "true":"false") . "," . $row[1] . ');" />';

	return $result . '</div>';
}

function fmt_idmagnify ($row,$index) {
	// TODO must I load the person in order to get the person->userid ?
	$person = new Person($row[1]);
	if ($person->userid == NULL) {
		$result = "<a href=\"viewcontact.php?id=$row[1]\">  <img src=\"img/magnify.gif\"></a>";
	} else {
		$result = "<a href=\"addressedit.php?id=$row[1]&origin=preview\">  <img src=\"img/pencil.png\"></a>";
	}
	$result .= "&nbsp;". escapehtml($row[$index]);
	return $result;
}



function fmt_jobs_actions ($obj, $name) {
	return fmt_jobs_generic($obj->id, $obj->status, $obj->deleted, $obj->type);
}

/**
	Generalized formatting function handle formatting using the pertinent fields
*/
function fmt_jobs_generic ($id, $status, $deleted, $type) {
	//return "$id, $status, $deleted";
	global $USER;

	if ($type == "survey") {
		$editbtn = '<a href="survey.php?id=' . $id . '">Edit</a>';
		$copybtn = ''; // no copy survey feature
	} else {
		$editbtn = '<a href="job.php?id=' . $id . '">Edit</a>';
		$copybtn = '<a href="jobs.php?copy=' . $id . '">Copy</a>';
	}

	$editrepeatingbtn = '<a href="jobrepeating.php?id=' . $id . '">Edit</a>';

	$cancelbtn = '<a href="jobs.php?cancel=' . $id . '" onclick="return confirm(\'Are you sure you want to cancel this job?\');">Cancel</a>';

	if ($type == "survey")
		$reportbtn = '<a href="reportsurveysummary.php?jobid=' . $id . '">Report</a>';
	else
		$reportbtn = '<a href="reportjobsummary.php?jobid=' . $id . '">Report</a>';

	$monitorbtn = '<a href="#" onclick="popup(\'jobmonitor.php?jobid=' . $id . '\', 500, 450);" >Monitor</a>';

	$graphbtn = '<a href="#" onclick="popup(\'jobmonitor.php?jobid=' . $id . '&noupdate\', 500, 450);" >Graph</a>';

	$deletebtn = '<a href="jobs.php?delete=' . $id . '" onclick="return confirmDelete();">Delete</a>';

	$archivebtn = '<a href="jobs.php?archive=' . $id . '">Archive</a>';

	$runrepeatbtn = '<a href="jobs.php?runrepeating=' . $id . '" onclick="return confirm(\'Are you sure you want to run this job now?\');">Run&nbsp;Now</a>';

	$viewresponses = '<a href="replies.php?jobid=' . $id . '">Responses</a>';

	$unarchivebtn = '<a href="jobs.php?unarchive=' . $id . '">Unarchive</a>';

	switch ($status) {
		case "new":
		case "scheduled":
		case "processing":
		case "procactive":
			if ($type == "survey") {
				$buttons = array($editbtn, $cancelbtn);
			} else {
				$buttons = array($editbtn, $copybtn, $cancelbtn);
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
	return implode("&nbsp;|&nbsp;", $buttons);
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

function fmt_status($obj, $name) {
	global $USER;
	if ($obj->status == 'new') {
		return "Not Submitted";
	} else if ($obj->status == 'procactive') {
			return "Processing (" . $obj->percentprocessed . "%)";
	} else {
		if ($obj->cancelleduserid && $obj->cancelleduserid != $USER->id) {
			$usr = new User($obj->cancelleduserid);
			return "Cancelled (" . $usr->login . ")";
		} else {
			return ucfirst($obj->status);
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
		$type = "job";
		if ($row->questionnaireid != null) {
			$type = "survey";
		}
	} else {
		$id = $row[$index];
		$status = $row[$index + 1];
		$deleted = $row[$index + 2];
		$jobownerlogin = $row[$index + 3];
		$jobownerid = $row[$index + 4];//change to id
		$type = "job";
		if (isset($row[21]) && $row[21] == "survey") {
			$type = "survey";
		}

	}

	if ($USER->id == $jobownerid) {
		$editLink = '<a href="job.php?id=' . $id . '">Edit</a>';
		if ($type == 'survey') {
			$copyLink = ''; // no copy survey feature
		} else {
			$copyLink = '&nbsp;|&nbsp;<a href="jobs.php?copy=' . $id . '">Copy</a>';
		}
	} elseif ($USER->authorize('manageaccount')) {
		$editLink = '<a href="./?login=' . $jobownerlogin . '">Login&nbsp;as&nbsp;this&nbsp;user</a>';
		$copyLink = '';
	} else {
		$editLink= '';
		$copyLink= '';
	}

	if ($USER->authorize('viewsystemreports')) {
		if ($type == 'survey') {
			$reportLink = '&nbsp;|&nbsp;<a href="reportsurveysummary.php?jobid=' . $id . '">Report</a>';
		} else {
			$reportLink = '&nbsp;|&nbsp;<a href="reportjobsummary.php?jobid=' . $id . '">Report</a>';
		}
	} else {
		$reportLink = '';
	}

	if ($USER->authorize('managesystemjobs')) {
		$cancelLink = '&nbsp;|&nbsp;<a href="jobs.php?cancel=' . $id . '" onclick="return confirm(\'Are you sure you want to cancel this job?\');">Cancel</a>';
		$archiveLink = '&nbsp;|&nbsp;<a href="jobs.php?archive=' . $id . '">Archive</a>';
		$deleteLink =  '&nbsp;|&nbsp;<a href="jobs.php?delete=' . $id . '" onclick="return confirmDelete();">Delete</a>';
	} else {
		$cancelLink = '';
		$archiveLink = '';
		$deleteLink = '';
	}

	if ($USER->authorize('managesystemjobs') || $USER->id == $jobownerid) {
		$runrepeatLink = '&nbsp;|&nbsp;<a href="jobs.php?runrepeating=' . $id . '" onclick="return confirm(\'Are you sure you want to run this job now?\');">Run&nbsp;Now</a>';
	} else {
		$runrepeatLink = "";
	}

	switch ($status) {
		case "new":
			return "$editLink$copyLink$cancelLink";
		case "active":
			return "$editLink$copyLink$reportLink$cancelLink";
		case "complete":
		case "cancelled":
		case "cancelling":
			if ($deleted == 2) {
				return "$editLink$copyLink$reportLink$deleteLink";
			} else {
				return "$editLink$copyLink$reportLink$archiveLink";
			}
		case "repeating":
			if ($USER->id == $jobownerid) {
				$editLink = '<a href="jobrepeating.php?id=' . $id . '">Edit</a>';
				$copyLink = '&nbsp;|&nbsp;<a href="jobs.php?copy=' . $id . '">Copy</a>';
			}
			return "$editLink$copyLink$runrepeatLink$deleteLink";
		default:
			return "$editLink$copyLink$reportLink";
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