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



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:completedjobs";
$TITLE = "Completed Jobs";

include_once("nav.inc.php");

startWindow('Completed Notification Jobs ' . help('System_CompletedJobs', NULL, 'blue'), 'padding: 3px;');

$start = 0 + $_GET['pagestart'];
$limit = 100;

// jobworkitem columns are: id jobid type priority personid messageid status resultdata assignedto

$query = "select SQL_CALC_FOUND_ROWS u.login, j.name, ADDTIME(j.startdate, j.starttime), j.status,
				sum(wi.type='phone') as total_phone,
				sum(wi.type='email') as total_email,
				sum(wi.type='print') as total_print,
				j.type LIKE '%phone%' AS has_phone,
				j.type LIKE '%email%' AS has_email,
				j.type LIKE '%print%' AS has_print,
				100 * sum(wi.status='success' and wi.type='phone') / sum( (wi.status='success' or wi.status='fail' or (jt.numattempts > 0 and wi.status='queued' or wi.status='scheduled')) and wi.type='phone' ) as percent_phone,
				100 * sum(wi.status='success' and wi.type='email') / sum( (wi.status='success' or wi.status='fail' or (jt.numattempts > 0 and wi.status='queued' or wi.status='scheduled')) and wi.type='email' ) as percent_email,
				100 * sum(wi.status='success' and wi.type='print') / sum( (wi.status='success' or wi.status='fail' or (jt.numattempts > 0 and wi.status='queued' or wi.status='scheduled')) and wi.type='print' ) as percent_print,
            ADDTIME(j.enddate, j.endtime), j.finishdate, j.id, j.status, j.deleted, jobowner.login

            from job j

            left join user jobowner
            	on (jobowner.id = j.userid)
            left join jobworkitem wi on
            	(j.id = wi.jobid)
        	left join jobtask jt on
				(jt.jobworkitemid=wi.id),
			user u

            where u.customerid = $USER->customerid and j.userid = u.id and j.deleted = 0 and
            	(j.status = 'complete' or j.status = 'cancelled')

            group by j.id order by j.finishdate desc limit $start, $limit";
$result = Query($query);

while ($row = DBGetRow($result)) {
	$data[] = $row;
}


function fmt_total ($row, $index) {
	$data = array();

	if ($row[$index+3])
		$data[] = "Phone:&nbsp;" . $row[$index];
	if ($row[$index+4])
		$data[] = "Email:&nbsp;" . $row[$index+1];
	if ($row[$index+5])
		$data[] = "Print:&nbsp;" . $row[$index+2];

	return implode("<br>",$data);
}

function fmt_complete_percent ($row, $index) {
	$data = array();

	if ($row[$index-3])
		$data[] = "Phone:&nbsp;" . (isset($row[$index]) ? $row[$index] : "0.00") . "%";
	if ($row[$index-2])
		$data[] = "Email:&nbsp;" . (isset($row[$index+1]) ? $row[$index+1] : "0.00") . "%";
	if ($row[$index-1])
		$data[] = "Print:&nbsp;" . (isset($row[$index+2] ) ? $row[$index+2]  : "0.00"). "%";

	return implode("<br>",$data);
}

$titles = array(
				"0" => 'Submitted by',
				"1" => 'Job Name',
				"2" => 'Start Date',
				"3" => 'Status',
				"4" => 'Total',
				"10" => 'Success Rate',
				"13" => 'End Date',
				"15" => 'Actions');
$formatters = array(
				"2" => 'fmt_date',
				"3" => 'fmt_status_index',
				"4" => 'fmt_total',
				"10" => 'fmt_complete_percent',
				"13" => 'fmt_job_date',
				"15" => 'fmt_jobs_actions_customer');

$query = "select FOUND_ROWS()";
$total = QuickQuery($query);
showPageMenu($total, $start, $limit);
echo "\n";
echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
showTable($data, $titles,$formatters);
echo "\n</table>";
showPageMenu($total, $start, $limit);
endWindow();

include_once("navbottom.inc.php");


function fmt_job_date ($row,$index) {
	if (isset($row[$index + 1])) { // Check job.finishdate field first
		$time = strtotime($row[$index + 1]);
		if ($time !== -1) {
			return date("M j, g:i a", $time);
		}
	} else {
		$time = strtotime($row[$index]);
		if ($time !== -1) {
			return date("M j, g:i a", $time);
		}
	}

	return "&nbsp;";
}

?>