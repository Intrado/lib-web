<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Schedule.obj.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/JobType.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemactive')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$jobtypes = DBFindMany("JobType","from jobtype");


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:activejobs";
$TITLE = "Active & Pending Jobs";

include_once("nav.inc.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

startWindow('Active & Pending Notification Jobs ' . help('System_ActiveJobs'),NULL);

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;
$data = array();
// reportperson columns are: jobid type userid personid messageid status
// reportcontact columns are : resultdata
$result = Query(
			"select SQL_CALC_FOUND_ROWS jobowner.login, j.name, j.status,
				sum(rc.type='phone') as total_phone,
            	sum(rc.type='email') as total_email,
            	sum(rc.type='print') as total_print,
            	sum(rc.type='sms') as total_sms,
            	j.type LIKE '%phone%' AS has_phone,
				j.type LIKE '%email%' AS has_email,
				j.type LIKE '%print%' AS has_print,
				j.type LIKE '%sms%' AS has_sms,
            	sum(rc.result not in ('A', 'M', 'duplicate', 'nocontacts', 'blocked') and rc.type='phone' and rc.numattempts < js.value) as remaining_phone,
            	sum(rc.result not in ('sent', 'duplicate', 'nocontacts') and rc.type='email' and rc.numattempts < 1) as remaining_email,
            	sum(rc.result not in ('sent', 'duplicate', 'nocontacts') and rc.type='print' and rc.numattempts < 1) as remaining_print,
            	sum(rc.result not in ('sent', 'duplicate', 'nocontacts', 'blocked') and rc.type='sms' and rc.numattempts < 1) as remaining_sms,
            ADDTIME(j.startdate, j.starttime), j.id, j.status, j.deleted, jobowner.login, jobowner.id, j.type, j.percentprocessed, j.cancelleduserid, j.jobtypeid
            from job j
            left join reportcontact rc
            	on j.id = rc.jobid
            left join user jobowner
            	on j.userid = jobowner.id
            left join jobsetting js on (js.jobid = j.id and js.name = 'maxcallattempts')
            where (j.status = 'active' or j.status='scheduled' or j.status='procactive' or j.status='processing' or j.status = 'new' or j.status = 'cancelling') and j.deleted=0
            group by j.id order by j.id desc limit $start, $limit");

while ($row = DBGetRow($result)) {
	$data[] = $row;
}


function fmt_job_type ($row,$index) {
	global $jobtypes;

	return $jobtypes[$row[24]]->name . " " . ucfirst($row[21]);
}


function fmt_total ($row, $index) {
	$data = array();

	if ($row[$index+4])
		$data[] = "Phone:&nbsp;" . $row[$index];
	if ($row[$index+5])
		$data[] = "Email:&nbsp;" . $row[$index+1];
	if ($row[$index+6])
		$data[] = "Print:&nbsp;" . $row[$index+2];
	if ($row[$index+7])
		$data[] = "SMS:&nbsp;" . $row[$index+3];


	return implode("<br>",$data);
}

function fmt_remaining ($row, $index) {
	$data = array();

	if ($row[$index-4])
		$data[] = "Phone:&nbsp;" . $row[$index];
	if ($row[$index-3])
		$data[] = "Email:&nbsp;" . $row[$index+1];
	if ($row[$index-2])
		$data[] = "Print:&nbsp;" . $row[$index+2];
	if ($row[$index-1])
		$data[] = "SMS:&nbsp;" . $row[$index+3];

	return implode("<br>",$data);
}

// index 22 is percent processed
// index 23 is cancelled user id
function fmt_status_index($row, $index) {
	global $USER;
	if($row[$index] == 'procactive'){
		return "Processing (" . $row[22] . "%)";
	} else {
		if ($row[23] && $row[23] != $USER->id) {
			$usr = new User($row[23]);
			return "Cancelled (" . $usr->login . ")";
		} else {
			return $row[$index] == 'new' ? 'Not Submitted' : ucfirst($row[$index]);
		}
	}
}

$titles = array(
				"0" => 'Submitted by',
				"1" => 'Job Name',
				"21" => "Mode",
				"2" => 'Status',
				"3" => 'Total',
				"11" => 'Remaining',
				"15" => 'Scheduled Start',
				"16" => 'Actions');
$formatters = array(
				"21" => "fmt_job_type",
				"2" => 'fmt_status_index',
				"3" => 'fmt_total',
				"11" => 'fmt_remaining',
				"15" => 'fmt_date',
				"16" => 'fmt_jobs_actions_customer');

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
