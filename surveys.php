<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/formatters.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/text.inc.php");
include_once("obj/SurveyQuestionnaire.obj.php");
include_once("obj/SurveyQuestion.obj.php");
include_once("obj/Job.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('survey')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:survey";
$TITLE = "Survey Builder";

include_once("nav.inc.php");


startWindow('My Active and Pending Surveys ' . help('SurveyBuilder_MyActiveAndPending', NULL, 'blue'),'padding: 3px;',true, true);

button_bar(button('schedule_survey', null,"survey.php?id=new") );

$data = DBFindMany("Job","from job where userid=$USER->id and type='survey' and (status='new' or status='active' or status='cancelling') and deleted=0 order by id desc");

$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"Type" => "#Type",
					"startdate" => "Start date",
					"Status" => "#Status",
					"responses" => "Responses",
					"Actions" => "Actions"
					);
$actions = array("Type" => "fmt_surveytype",
				'Status' => 'fmt_status',
				"startdate" => "fmt_job_startdate",
				"finishdate" => "fmt_obj_date",
				"responses" => "fmt_response_count",
				"Actions" => "fmt_jobs_actions"
				);
showObjects($data, $titles, $actions, count($data) > 8, true);


endWindow();

echo "<br>";

startWindow('My Completed Surveys '  . help('SurveyBuilder_MyCompleted', NULL, 'blue'),'padding: 3px;',true, true);

$data = DBFindMany("Job","from job where userid=$USER->id and type='survey' and (status='complete' or status='cancelled') and deleted=0 order by id desc");

$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"Type" => "#Type",
					"startdate" => "Start date",
					"Status" => "#Status",
					"responses" => "Responses",
					"Actions" => "Actions"
					);
$actions = array("Type" => "fmt_surveytype",
				'Status' => 'fmt_status',
				"startdate" => "fmt_job_startdate",
				"finishdate" => "fmt_obj_date",
				"responses" => "fmt_response_count",
				"Actions" => "fmt_jobs_actions"
				);
showObjects($data, $titles, $actions, count($data) > 8, true);


endWindow();

include_once("navbottom.inc.php");
?>