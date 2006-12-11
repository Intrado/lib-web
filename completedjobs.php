<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/Schedule.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemcompleted')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$start = 0 + $_GET['pagestart'];
$limit = 100;

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:completedjobs";
$TITLE = "Completed Jobs";

include_once("nav.inc.php");

startWindow('Completed Notification Jobs ' . help('System_CompletedJobs', NULL, 'blue'), 'padding: 3px;');


//get this page's worth of jobs
$query = "select SQL_CALC_FOUND_ROWS j.id from job j, user u
where j.userid=u.id and u.customerid = $USER->customerid and j.deleted = 0 and
            	(j.status = 'complete' or j.status = 'cancelled')
order by finishdate desc limit $start, $limit
";

$jobids = QuickQueryList($query);
$query = "select FOUND_ROWS()";
$total = QuickQuery($query);

if ($total == 0) {
	$jobs = array();
} else {

	$jobidlist = implode(",",$jobids);

	//just get the stats data for this page
	$query = "select wi.jobid,
					wi.type,
					count(*) as total,
					100 * sum(wi.status='success') / (sum(wi.status='success' or wi.status='fail' or (jt.numattempts>0 and wi.status = 'queued' or wi.status='scheduled')) +0.00) as success_rate
	from jobworkitem wi left join jobtask jt on (jt.jobworkitemid=wi.id)
	where wi.jobid in ($jobidlist)
	group by wi.jobid, wi.type
	";

	$jobstats = array();
	$result = Query($query);
	while ($row = DBGetRow($result)) {
		$jobstats[$row[0]][$row[1]] = array ("total" => $row[2], "rate" => $row[3]);
	}

	$jobs = DBFindMany("Job", "from job where id in ($jobidlist) order by finishdate desc");
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
	foreach (explode(",",$obj->type) as $type)
		$total .= ucfirst($type) . ": " . $jobstats[$obj->id][$type]['total'] . "<br>";
	return $total;
}
function fmt_rate ($obj, $name) {
	global $jobstats;

	$rate = "";
	foreach (explode(",",$obj->type) as $type)
		$rate .= ucfirst($type) . ": " . sprintf("%0.2f",$jobstats[$obj->id][$type]['rate']) . "%<br>";
	return $rate;
}

$titles = array ("Owner" => "Submitted by",
				"name" => 'Job Name',
				"type" => "Type",
				"status" => 'Status',
				"Total" => 'Total',
				"Rate" => 'Success Rate',
				"startdate" => 'Start Date',
				"enddate" => 'End Date',
				"Actions" => 'Actions');
$formatters = array(
				"type" => "fmt_obj_csv_list",
				"Owner" => 'fmt_job_owner',
				"startdate" => 'fmt_job_startdate',
				"status" => "fmt_ucfirst",
				"Total" => 'fmt_total',
				"Rate" => 'fmt_rate',
				"enddate" => 'fmt_job_enddate',
				"Actions" => 'fmt_jobs_actions_customer');


showPageMenu($total, $start, $limit);
showObjects($jobs, $titles, $formatters, false, false);
showPageMenu($total, $start, $limit);
endWindow();

include_once("navbottom.inc.php");

?>