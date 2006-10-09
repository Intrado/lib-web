<?

function fmt_obj_date ($obj,$name) {
	if (isset($obj->$name) && $obj->$name != "") {
		$time = strtotime($obj->$name);
		if ($time !== -1 && $obj->$name != "0000-00-00 00:00:00")
			return date("M j, g:i a",$time);
	}
	return "- Never -";
}

function fmt_date ($row,$index) {
	if (isset($row[$index])) {
		$time = strtotime($row[$index]);
		if ($time !== -1)
			return date("M j, g:i a",$time);
	}
	return "&nbsp;";
}

function fmt_phone ($row,$index) {
	if (strlen($row[$index]) == 10)
		return "(" . substr($row[$index],0,3) . ")&nbsp;" . substr($row[$index],3,3) . "-" . substr($row[$index],6,4);
	else if (strlen($row[$index]) == 7)
		return  substr($row[$index],0,3) . "-" . substr($row[$index],3,4);
	else
		return $row[$index];
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
		return $row[$index];
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

/**
	Function to perform formatting based on a numeric index rather than an object field name
*/
function fmt_jobs_actions_index($row, $index) {
	return fmt_jobs_generic($row[$index], $row[$index + 1], $row[$index + 2]);
}

function fmt_jobs_actions ($obj, $name) {
	return fmt_jobs_generic($obj->id, $obj->status, $obj->deleted);
}

/**
	Generalized formatting function handle formatting using the pertinent fields
*/
function fmt_jobs_generic ($id, $status, $deleted) {
	//return "$id, $status, $deleted";
	global $USER;

	$editbtn = '<a href="job.php?id=' . $id . '">Edit</a>';
	$editrepeatingbtn = '<a href="jobrepeating.php?id=' . $id . '">Edit</a>';

	$cancelbtn = '<a href="jobs.php?cancel=' . $id . '" onclick="return confirm(\'Are you sure you want to cancel this job?\');">Cancel</a>';
	$reportbtn = '<a href="reportsummary.php?jobid=' . $id . '">Report</a>';
	$monitorbtn = '<a href="#" onclick="popup(\'jobmonitor.php?jobid=' . $id . '\', 500, 450);" >Monitor</a>';
	$graphbtn = '<a href="#" onclick="popup(\'jobmonitor.php?jobid=' . $id . '&noupdate\', 500, 450);" >Graph</a>';

	$deletebtn = '<a href="jobs.php?delete=' . $id . '" onclick="return confirmDelete();">Delete</a>';
	$archivebtn = '<a href="jobs.php?archive=' . $id . '">Archive</a>';

	switch ($status) {
		case "new":
			$buttons = array($editbtn,$cancelbtn);
			break;
		case "active":
			if ($USER->authorize('createreport'))
				$buttons = array($editbtn,$reportbtn,$monitorbtn,$cancelbtn);
			else
				$buttons = array($editbtn,$cancelbtn);
			break;
		case "complete":
		case "cancelled":
			if ($deleted == 2)
				$usedelbtn = $deletebtn;
			else
				$usedelbtn = $archivebtn;

			if ($USER->authorize('createreport'))
				$buttons = array($editbtn,$reportbtn,$graphbtn,$usedelbtn);
			else
				$buttons = array($editbtn,$usedelbtn);
			break;
		case "repeating":
			$buttons = array($editrepeatingbtn,$deletebtn);
			break;
		default:
			if ($USER->authorize('createreport'))
				$buttons = array($editbtn,$reportbtn,$graphbtn);
			else
				$buttons = array($editbtn);
			break;
	}
	return implode("&nbsp;|&nbsp;", $buttons);
}

function fmt_job_enddate ($obj,$name) {
	if ($obj->finishdate)
		return date("M j, g:i a",strtotime($obj->finishdate));
	else
		return date("M j, g:i a",strtotime($obj->enddate . " " . $obj->endtime));
}

function fmt_job_startdate ($obj,$name) {
	return date("M j, g:i a",strtotime($obj->startdate . " " . $obj->starttime));
}

function fmt_status($obj, $name) {
	global $USER;
	if ($obj->status == 'new') {
		$assigned = QuickQuery("select assigned from job where id='$obj->id'");
		if (!$assigned)
			return 'Not Submitted';
		else
			return "Processing";
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
	//return fmt_jobs_generic($row[$index], $row[$index + 1], $row[$index + 2]);
	if ($row instanceof Job) {

		$id = $row->id;
		$status = $row->status;
		$deleted = $row->deleted;
		$jobowner = new User($row->userid);
		$jobowner = $jobowner->id;
	} else {
		$id = $row[$index];
		$status = $row[$index + 1];
		$deleted = $row[$index + 2];
		$jobowner = $row[$index + 4];//change to id
	}

	if ($USER->id == $jobowner) {
		$editLink = '<a href="job.php?id=' . $id . '">Edit</a>';
	} elseif ($USER->authorize('manageaccount')) {
		$editLink = '<a href="./?login=' . $jobowner . '">Login&nbsp;as&nbsp;this&nbsp;user</a>';
	}

	if ($USER->authorize('viewsystemreports')) {
		$reportLink = '&nbsp;|&nbsp;<a href="reportsummary.php?jobid=' . $id . '">Report</a>';
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

	switch ($status) {
		case "new":
			return "$editLink$cancelLink";
		case "active":
			return "$editLink$reportLink$cancelLink";
		case "complete":
		case "cancelled":
			if ($deleted == 2) {
				return "$editLink$reportLink$deleteLink";
			} else {
				return "$editLink$reportLink$archiveLink";
			}
		case "repeating":
			if ($USER->login == $jobowner) {
				$editLink = '<a href="jobrepeating.php?id=' . $id . '">Edit</a>';
			}
			return "$editLink$deleteLink";
		default:
			return "$editLink$reportLink";
	}
}

/**
	Function using a numeric index rather than a field name to access the column to print
*/
function fmt_status_index($row, $index) {
	return $row[$index] == 'new' ? 'Not Submitted' : ucfirst($row[$index]);
}

function fmt_ucfirst($obj, $name) {
	return ucfirst($obj->status);
}



function fmt_next_repeat($row, $index) {
	if ($row[$index] === NULL)
		$nextrun = "- Never -";
	else {
		$nextrun = date("F jS, Y h:i a", strtotime($row[$index]));
	}

	return $nextrun;
}

?>