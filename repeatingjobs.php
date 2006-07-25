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
if (!$USER->authorize('viewsystemrepeating')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:repeatingjobs";
$TITLE = "Repeating Jobs";

include_once("nav.inc.php");

startWindow('Repeating Notification Jobs ' . help('System_RepeatingJobs', NULL, 'blue'), 'padding: 3px;');

$start = 0 + $_GET['pagestart'];
$limit = 100;

// jobworkitem columns are: id jobid type priority personid messageid status resultdata assignedto
$result = Query(
			"select SQL_CALC_FOUND_ROWS u.login, j.name, schedule.nextrun, j.id, j.status, j.deleted, jobowner.login, name+0 as foo
            	from job j
            	left join user jobowner
            		on j.userid = jobowner.id,
            	user u, schedule
            	where u.customerid = $USER->customerid and j.userid = u.id and j.status = 'repeating' and j.scheduleid = schedule.id
            	group by j.id order by u.login,foo,name limit $start, $limit");

while ($row = DBGetRow($result)) {
	$data[] = $row;
}

$titles = array(
				"0" => 'Submitted by',
				"1" => 'Job Name',
				"2" => 'Next Scheduled Run',
				"3" => 'Actions');
$formatters = array(
				"2" => 'fmt_next_repeat',
				"3" => 'fmt_jobs_actions_customer');

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
?>