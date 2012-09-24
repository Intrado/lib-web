<?
require_once("inc/common.inc.php");
require_once("obj/Job.obj.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("inc/html.inc.php");
require_once("inc/date.inc.php");


function fmt_activestatus($obj, $name) {
	if ($obj->status == 'cancelling')
		return "<span class=\"activejob\">" . _L("Cancelling...") . "</span>";
	else
		return "<span class=\"activejob\">" . _L("Active...") . "</span>";
}

function fmt_job_content($obj, $name) {
	$theme = $_SESSION['colorscheme']['_brandtheme'];
	$str = "";
	if ($obj->hasPhone()){
		$str .= " <img src=\"themes/$theme/phone-grey.png\" title=\"" . _L("Phone") . "\" />";
	} else {
		$str .= " <img src=\"themes/$theme/blank-content.png\" />";
	}
	
	if ($obj->hasEmail()){
		$str .= " <img src=\"themes/$theme/email-grey.png\" title=\"" . _L("Email") . "\" />";
	} else {
		$str .= " <img src=\"themes/$theme/blank-content.png\" />";
	}
	
	if ($obj->hasSMS()){
		$str .= " <img src=\"themes/$theme/sms-grey.png\" title=\"" . _L("SMS") . "\" />";
	}
	else {
		$str .= " <img src=\"themes/$theme/blank-content.png\" />";
	}
	
	if ($obj->hasPost()){
		$str .= " <img src=\"themes/$theme/social-grey.png\" title=\"" . _L("Social") . "\" />";
	} else {
		$str .= " <img src=\"themes/$theme/blank-content.png\" />";
	}
	
	return $str;
}

function fmt_job_recipients($obj, $name) {
	$lists = QuickQueryList("select listid from joblist where jobid = ?", false, false, array($obj->id));
	$total = 0;
	foreach ($lists as $id) {
		$total += RenderedList2::caclListTotal($id);
	}
	return $total;
}


function fmt_completedjob_recipients($obj, $name) {
	return QuickQuery("select count(distinct personid) FROM reportperson WHERE jobid=?", false, array($obj->id));
}

function fmt_obj_date_no_time ($obj,$name) {
	if (isset($obj->$name) && $obj->$name != "") {
		$time = strtotime($obj->$name);
		if ($time !== -1 && $obj->$name != "0000-00-00 00:00:00")
			return date('D n/j/y',$time);
	}
	return "- Never -";
}

function fmt_job_ownername ($obj, $name) {
	static $users = array();
	if (isset($users[$obj->userid])) {
		return $users[$obj->userid];
	} else {
		$user = new User($obj->userid);
		$users[$obj->userid] = $user->firstname . ' ' . $user->lastname;
		return $users[$obj->userid];
	}
}

function fmt_job_name ($obj, $name) {
	if ($obj->status == "cancelled")
		return "<span style='text-decoration:line-through;'>".escapehtml($obj->$name)."</span> (canceled)"; // canceled
	else
		return "<span>".escapehtml($obj->$name)."</span>";
}

function fmt_scheduledjobs_action ($obj) {
	return "window.location = 'reportjobsummary.php?id=$obj->id';";
}

function fmt_job_default_action ($obj) {
	if ($obj->status == 'complete' || $obj->status == 'cancelled') {
		if ($obj->type == "survey")
			return "window.location = 'reportsurveysummary.php?jobid==$obj->id';";
		else
			return "window.location = 'reportjobsummary.php?id=$obj->id';";
	} else {
		if ($obj->type == "survey")
			return "window.location = 'survey.php?id=$obj->id';";
		else
			return "window.location = 'job.php?id=$obj->id';";
	}
}


function frm_job_tools($obj, $name) {
	$actions = fmt_jobs_actions($obj,$name);
	return "<img id=\"actionlink_{$obj->id}\" class=\"jobtools\" src=\"themes/{$_SESSION['colorscheme']['_brandtheme']}/tools.png\" /><div class=\"hidden\">{$actions}</div>";
}

// All actions require a valid messagegroupid; the user must own the messagegroup.
function handleRequest() {
	global $USER;
	
	if (!isset($_REQUEST['action']))
		return false;
	
	$start = 0;
	$limit = 10;
	if (isset($_REQUEST['start']) && isset($_REQUEST['limit'])) {
		$start = $_REQUEST['start']+0;
		$limit = $_REQUEST['limit']+0;
	}
	
	// Add User query part depending on who is requested, me, everyone or a specific user
	$queryArgs = array($USER->id);
	if (isset($_REQUEST['who'])) {
		if ($_REQUEST["who"] == "me") {
			$queryUsers = " j.userid = ?";
		} else if ($_REQUEST["who"] == "everyone") {
			$queryUsers = " (j.userid = ? or exists (select * from userlink ul where ul.userid = ? and j.userid = ul.subordinateuserid and j.userid = ul.subordinateuserid)) ";
			$queryArgs[] = $USER->id;
		} else {
			$queryUsers = " exists (select * from userlink ul where ul.userid = ? and ul.subordinateuserid = ? and j.userid = ul.subordinateuserid) ";
			$queryArgs[] = $_REQUEST['who'];
		}
	} else {
		$queryUsers = " j.userid = ? ";
	}
	
	switch($_REQUEST['action']) {
		case 'activejobs':
			$activejobs = DBFindMany("Job", "from job j
				where $queryUsers 
					and not j.deleted and j.finishdate is null and j.status in ('processing','procactive','active','cancelling') 
					and j.type != 'alert' 
				order by j.modifydate desc 
				limit $start,$limit","j",$queryArgs);
			
			$titles = array(
				"status" => "Status",
				"userid" => "Author",
				"name" => "Subject",
				"rcpts" => "Rcpts",
				"content" => "Content",
				"tools" => ""
			);
			
			$formatters = array(
				'status' => 'fmt_activestatus',
				'userid' => 'fmt_job_ownername',
				'rcpts' => 'fmt_job_recipients',
				'content' => 'fmt_job_content',
				'tools' => 'frm_job_tools'
			);
			
			return prepareAjaxTableObjects($activejobs, $titles, $formatters,false);
		
		case 'scheduledjobs':
			$activejobs = DBFindMany("Job", "from job j
				where $queryUsers 
					and not j.deleted and j.status in ('scheduled') 
					and j.type != 'alert' 
				order by j.startdate desc 
				limit $start,$limit","j",$queryArgs);
		
			$titles = array(
				"startdate" => "Scheduled For",
				"userid" => "Author",
				"name" => "Subject",
				"rcpts" => "Rcpts",
				"content" => "Content",
				"tools" => ""
			);
				
			$formatters = array(
				'startdate' => 'fmt_obj_date_no_time',
				'userid' => 'fmt_job_ownername',
				'rcpts' => 'fmt_job_recipients',
				'content' => 'fmt_job_content',
				'tools' => 'frm_job_tools'
			);
			
			return prepareAjaxTableObjects($activejobs, $titles, $formatters, false);
		
		case 'completedjobs':
			$activejobs = DBFindMany("Job", "from job j
				where $queryUsers 
					and not j.deleted and j.status in ('complete','cancelled') 
					and j.type != 'alert' 
				order by j.finishdate desc 
				limit $start,$limit","j",$queryArgs);
				
			$titles = array(
				"finishdate" => "Sent On",
				"userid" => "Author",
				"name" => "Subject",
				"rcpts" => "Rcpts",
				"content" => "Content",
				"tools" => ""
			);
			
			$formatters = array(
				'finishdate' => 'fmt_obj_date_no_time',
				'userid' => 'fmt_job_ownername',
				'name' => 'fmt_job_name',
				'rcpts' => 'fmt_completedjob_recipients',
				'content' => 'fmt_job_content',
				'tools' => 'frm_job_tools'
			);
			
			return prepareAjaxTableObjects($activejobs, $titles, $formatters, false);
		
		default:
			error_log("Unknown request " . $_REQUEST['action']);
			return false;
	}
	return false;
}

header('Content-Type: application/json');
$data = handleRequest();
echo json_encode(!empty($data) ? $data : false);
?>
