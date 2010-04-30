<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/Schedule.obj.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/JobType.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemcompleted')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:completedjobs";
$TITLE = "Completed Jobs";

include_once("nav.inc.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

startWindow('Completed Notification Jobs ' . help('System_CompletedJobs'), 'padding: 3px;');


//get this page's worth of jobs
$query = "select SQL_CALC_FOUND_ROWS j.id from job j, user u
where j.userid=u.id and j.deleted = 0 and
            	(j.status = 'complete' or j.status = 'cancelled')
order by finishdate desc limit $start, $limit
";

$jobtypes = DBFindMany("JobType","from jobtype");

$jobids = QuickQueryList($query);
$query = "select FOUND_ROWS()";
$total = QuickQuery($query);

if ($total == 0) {
	$jobs = array();
} else {

	$jobidlist = implode(",",$jobids);

	//just get the stats data for this page
	$query = "select rp.jobid,
					rp.type,
					sum(rp.numcontacts) as total,
					100 * sum(rp.numcontacts and rp.status='success') / (sum(rp.numcontacts and rp.status != 'duplicate') +0.00) as success_rate
	from reportperson rp
	where rp.jobid in ($jobidlist)
	group by rp.jobid, rp.type
	";

	$jobstats = array();
	$result = Query($query);
	while ($row = DBGetRow($result)) {
		$jobstats[$row[0]][$row[1]] = array ("total" => $row[2], "rate" => $row[3]);
	}

	$jobs = DBFindMany("Job", "from job where id in ($jobidlist) order by finishdate desc");
}

function getJobTypes($job) {
	$types = array();
		if ($job->type == "survey") {
			$questionnaire = new SurveyQuestionnaire($job->questionnaireid);

			if ($questionnaire->hasphone)
				$types[] = "phone";
			if ($questionnaire->hasweb)
				$types[] = "email";
		} else {
			$mg = new MessageGroup($job->messagegroupid);
			
			$types = array();
			foreach (array("phone","email","sms") as $type)
				if ($mg->hasMessage($type))
					$types[] = $type;
	}
	return $types;
}

function fmt_job_owner ($obj, $name) {
	static $users = array();
	if (isset($users[$obj->userid])) {
		return $users[$obj->userid];
	} else {
		$user = new User($obj->userid);
		$users[$obj->userid] = $user->login;
		return $user->login;
	}
}

function fmt_total ($obj, $name) {
	global $jobstats;
	$total = "";
	foreach (getJobTypes($obj) as $type)
		$total .= format_delivery_type($type) . ": " . (isset($jobstats[$obj->id][$type]['total']) ? $jobstats[$obj->id][$type]['total'] : "") . "<br>";
	return $total;
}
function fmt_rate ($obj, $name) {
	global $jobstats;

	$rate = "";
	foreach (getJobTypes($obj) as $type)
		$rate .= format_delivery_type($type) . ": " . sprintf("%0.2f",(isset($jobstats[$obj->id][$type]['rate']) ? $jobstats[$obj->id][$type]['rate'] : "")) . "%<br>";
	return $rate;
}

function fmt_jobmode ($obj,$name) {
	global $jobtypes;
	return escapehtml($jobtypes[$obj->jobtypeid]->name . " " . ucfirst($obj->type));
}

$titles = array ("Owner" => "Submitted by",
				"name" => 'Job Name',
				"Mode" => "Mode",
				"status" => 'Status',
				"Total" => 'Total',
				"Rate" => '% Contacted',
				"startdate" => 'Start Date',
				"enddate" => 'End Date',
				"responses" => "Responses",
				"Actions" => 'Actions');
$formatters = array(
				"Mode" => "fmt_jobmode",
				"Owner" => 'fmt_job_owner',
				"startdate" => 'fmt_job_startdate',
				"status" => "fmt_ucfirst",
				"Total" => 'fmt_total',
				"Rate" => 'fmt_rate',
				"enddate" => 'fmt_job_enddate',
				"responses" => 'fmt_response_count',
				"Actions" => 'fmt_jobs_actions_customer');

if(!$USER->authorize('leavemessage')){
	unset($titles["responses"]);
	unset($formatters["responses"]);
}

showPageMenu($total, $start, $limit);
showObjects($jobs, $titles, $formatters, false, false);
showPageMenu($total, $start, $limit);
endWindow();

include_once("navbottom.inc.php");

?>
