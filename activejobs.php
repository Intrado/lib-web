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
if (!$USER->authorize('viewsystemactive')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:activejobs";
$TITLE = "Active & Pending Jobs";

include_once("nav.inc.php");

startWindow('Active & Pending Notification Jobs ' . help('System_ActiveJobs'),NULL);

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;
$data = array();
// reportperson columns are: jobid type userid personid messageid status
// reportcontact columns are : resultdata
$result = Query(
			"select SQL_CALC_FOUND_ROWS jobowner.login, j.name, j.status,
				sum(rp.type='phone') as total_phone,
            	sum(rp.type='email') as total_email,
            	sum(rp.type='print') as total_print,
            	j.type LIKE '%phone%' AS has_phone,
				j.type LIKE '%email%' AS has_email,
				j.type LIKE '%print%' AS has_print,
            	sum((rp.status!='success' and rp.status!='fail' and rp.status!='duplicate') and rp.type='phone') as remaining_phone,
            	sum((rp.status!='success' and rp.status!='fail' and rp.status!='duplicate') and rp.type='email') as remaining_email,
            	sum((rp.status!='success' and rp.status!='fail' and rp.status!='duplicate') and rp.type='print') as remaining_print,
            ADDTIME(j.startdate, j.starttime), j.id, j.status, j.deleted, jobowner.login, jobowner.id, j.type
            from job j
            left join reportperson rp
            	on j.id = rp.jobid
            left join user jobowner
            	on j.userid = jobowner.id
            where (j.status = 'active' or j.status='scheduled' or j.status='processing' or j.status = 'new' or j.status = 'cancelling') and j.deleted=0
            group by j.id order by j.id desc limit $start, $limit");

while ($row = DBGetRow($result)) {
	$data[] = $row;
}


function fmt_job_type ($row,$index) {
	if ($row[18] == "survey")
		return "Survey";
	else
		return "Notification";
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

function fmt_remaining ($row, $index) {
	$data = array();

	if ($row[$index-3])
		$data[] = "Phone:&nbsp;" . $row[$index];
	if ($row[$index-2])
		$data[] = "Email:&nbsp;" . $row[$index+1];
	if ($row[$index-1])
		$data[] = "Print:&nbsp;" . $row[$index+2];

	return implode("<br>",$data);
}


$titles = array(
				"0" => 'Submitted by',
				"1" => 'Job Name',
				"18" => "Mode",
				"2" => 'Status',
				"3" => 'Total',
				"9" => 'Remaining',
				"12" => 'Scheduled Start',
				"13" => 'Actions');
$formatters = array(
				"18" => "fmt_job_type",
				"2" => 'fmt_status_index',
				"3" => 'fmt_total',
				"9" => 'fmt_remaining',
				"12" => 'fmt_date',
				"13" => 'fmt_jobs_actions_customer');

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