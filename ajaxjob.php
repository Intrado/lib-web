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


//used in listcontacts as a callback for gen2cache
function calc_job_list_total ($listid) {
	$list = new PeopleList($listid);
	$renderedlist = new RenderedList2();
	$renderedlist->initWithList($list);
	return $renderedlist->getTotal();
}

function fmt_job_content($obj, $name) {
	$str = "";
	if ($obj->hasPhone()){
		$str .= " <img src=\"themes/newui/phone-grey.png\"/>";
	}
	if ($obj->hasEmail()){
		$str .= " <img src=\"themes/newui/email-grey.png\"/>";
	}
	if ($obj->hasSMS()){
		$str .= " <img src=\"themes/newui/sms-grey.png\"/>";
	}
	if ($obj->hasPost()){
		$str .= " <img src=\"themes/newui/social-grey.png\"/>";
	}
	return $str;
	//return "<img src=\"themes/newui/phone-grey.png\"/> <img src=\"themes/newui/email-grey.png\"/> <img src=\"themes/newui/sms-grey.png\"/> <img src=\"themes/newui/social-grey.png\"/>";
}

function fmt_job_recipients($obj, $name) {
	$lists = QuickQueryList("select listid from joblist where jobid = ?", false, false, array($obj->id));
	$total = 0;
	foreach ($lists as $id) {
		//expect the list mod date hasnt changed when using cache
		$list = new PeopleList($id);
		$expect = array("modifydate" => $list->modifydate);
		$total += gen2cache(300, $expect, null, "calc_job_list_total", $id);
	}
	return $total;
}

function fmt_obj_date_no_time ($obj,$name) {
	if (isset($obj->$name) && $obj->$name != "") {
		$time = strtotime($obj->$name);
		if ($time !== -1 && $obj->$name != "0000-00-00 00:00:00")
			return date('l m/d/y',$time);
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
		return $user->login;
	}
}

function fmt_scheduledjobs_action ($obj) {
	return "window.location = 'reportjobsummary.php?id=$obj->id';";
}

function fmt_job_default_action ($obj) {
	if ($obj->status == 'complete' || $obj->status == 'cancelled')
		return "window.location = 'reportjobsummary.php?id=$obj->id';";
	else
		return "window.location = 'job.php?id=$obj->id';";
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
	
	switch($_REQUEST['action']) {
		case 'activejobs':
			$activejobs = DBFindMany("Job", "from job j
				where (j.userid = ? or exists (select * from userlink ul where ul.userid = ? and j.userid = ul.subordinateuserid)) 
					and not j.deleted and j.finishdate is null and j.modifydate is not null and j.status in ('processing','procactive','active','cancelling') 
					and j.type != 'alert' 
				order by j.modifydate desc 
				limit $start,$limit","j",array($USER->id,$USER->id));
			
			$titles = array(
				"status" => "Status",
				"userid" => "Author",
				"name" => "Subject",
				"rcpts" => "Rcpts",
				"content" => "Content"
			);
			
			$formatters = array(
				'status' => 'fmt_status',
				'userid' => 'fmt_job_ownername',
				'rcpts' => 'fmt_job_recipients',
				'content' => 'fmt_job_content'
			);
			
			$rowActionFormatter = 'fmt_job_default_action';
			
			return prepareAjaxTableObjects($activejobs, $titles, $formatters,$rowActionFormatter);
			
		case 'scheduledjobs':
			$activejobs = DBFindMany("Job", "from job j
				where (j.userid = ? or exists (select * from userlink ul where ul.userid = ? and j.userid = ul.subordinateuserid)) 
					and not j.deleted and j.modifydate is not null and j.status in ('scheduled') 
					and j.type != 'alert' 
				order by j.modifydate desc 
				limit $start,$limit","j",array($USER->id,$USER->id));
		
			$titles = array(
				"startdate" => "Scheduled For",
				"userid" => "Author",
				"name" => "Subject",
				"rcpts" => "Rcpts",
				"content" => "Content"
			);
				
			$formatters = array(
				'startdate' => 'fmt_obj_date_no_time',
				'userid' => 'fmt_job_ownername',
				'rcpts' => 'fmt_job_recipients',
				'content' => 'fmt_job_content'
			);
			
			$rowActionFormatter = 'fmt_job_default_action';
			
			return prepareAjaxTableObjects($activejobs, $titles, $formatters, $rowActionFormatter);
				
		case 'completedjobs':
			$activejobs = DBFindMany("Job", "from job j
				where (j.userid = ? or exists (select * from userlink ul where ul.userid = ? and j.userid = ul.subordinateuserid)) 
					and not j.deleted and j.modifydate is not null and j.status in ('complete','cancelled') 
					and j.type != 'alert' 
				order by j.modifydate desc 
				limit $start,$limit","j",array($USER->id,$USER->id));
				
			$titles = array(
				"finishdate" => "Sent On",
				"userid" => "Author",
				"name" => "Subject",
				"rcpts" => "Rcpts",
				"content" => "Content"
				//,"tools" => "Tools"
			);
			
			$formatters = array(
				'finishdate' => 'fmt_obj_date_no_time',
				'userid' => 'fmt_job_ownername',
				'rcpts' => 'fmt_job_recipients',
				'content' => 'fmt_job_content'
				//,'tools' => 'fmt_jobs_actions'
			);
			
			$rowActionFormatter = 'fmt_job_default_action';
			
			return prepareAjaxTableObjects($activejobs, $titles, $formatters, $rowActionFormatter);
		
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