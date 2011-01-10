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
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

$query = "from job where userid=$USER->id and deleted = 2";
$total = QuickQuery("select count(*) " . $query);
$data = DBFindMany("Job", $query . " order by id desc limit $start, $limit");
$titles = array(	"name" => "Name",
					"description" => "Description",
					"type" => "#Type",
					"startdate" => "Start Date",
					"Status" => "Status",
					"enddate" => "End Date",
					"responses" => "Responses (Unplayed/Total)",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_jobs_actions",
					'Status' => 'fmt_status',
					"type" => "fmt_obj_delivery_type_list",
					"startdate" => "fmt_job_startdate",
					"enddate" => "fmt_job_enddate",
					"responses" => "fmt_response_count");

if (!$USER->authorize('leavemessage')) {
	unset($titles["responses"]);
	unset($formatters["responses"]);
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:jobs:archived";
$TITLE = "Archived Jobs";

include_once("nav.inc.php");

startWindow('My Archived Jobs ' . help('Jobs_MyArchivedJobs'),'padding: 3px;', false, true);

showPageMenu($total, $start, $limit);

showObjects($data, $titles, $formatters);

showPageMenu($total, $start, $limit);

endWindow();

include_once("navbottom.inc.php");
