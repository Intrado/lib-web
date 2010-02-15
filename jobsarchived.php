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


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:jobs:archived";
$TITLE = "Archived Jobs";

include_once("nav.inc.php");



print '<br>';

$data = DBFindMany("Job","from job where userid=$USER->id and deleted = 2 order by id desc");
$titles = array(	"name" => "Name",
					"description" => "Description",
					"type" => "#Type",
					"startdate" => "Start Date",
					"Status" => "Status",
					"enddate" => "End Date",
					"responses" => "Responses (Unplayed/Total)",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status', "type" => "fmt_obj_delivery_type_list","startdate" => "fmt_job_startdate", "enddate" => "fmt_job_enddate", "responses" => "fmt_response_count");
if(!$USER->authorize('leavemessage')){
	unset($titles["responses"]);
	unset($formatters["responses"]);
}
startWindow('My Archived Jobs ' . help('Jobs_MyArchivedJobs'),'padding: 3px;', false, true);
showObjects($data, $titles, $formatters);
endWindow();

include_once("navbottom.inc.php");
