<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/form.inc.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("obj/Job.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hassurvey', true) || !$USER->authorize('survey')) {
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


startWindow('My Surveys ' . help('SurveyBuilder_MyActiveAndPending'),'padding: 3px;',true, true);

button_bar(button('Schedule Survey', null,"survey.php?id=new") );

$data = DBFindMany("Job","from job where userid=$USER->id and type='survey' and (status='new' or status='scheduled' or status='processing' or status='procactive' or status='active' or status='cancelling') and deleted=0 order by id desc");

$titles = array(	"name" => "#Job Name",
					"description" => "#Description",
					"Type" => "#Deliver by",
					"startdate" => "Start date",
					"Status" => "#Status",
					"responses" => "Responses (Unplayed/Total)",
					"Actions" => "Actions"
					);
$actions = array("Type" => "fmt_surveytype",
				'Status' => 'fmt_status',
				"startdate" => "fmt_job_startdate",
				"finishdate" => "fmt_obj_date",
				"responses" => "fmt_response_count",
				"Actions" => "fmt_jobs_actions"
				);

if(!$USER->authorize('leavemessage')){
	unset($titles["responses"]);
	unset($actions["responses"]);
}
showObjects($data, $titles, $actions, count($data) > 8, true);


endWindow();

echo "<br>";

startWindow('My Completed Surveys '  . help('SurveyBuilder_MyCompleted'),'padding: 3px;',true, true);

$data = DBFindMany("Job","from job where userid=$USER->id and type='survey' and (status='complete' or status='cancelled') and deleted=0 order by id desc");

$titles = array(	"name" => "#Job Name",
					"description" => "#Description",
					"Type" => "#Deliver by",
					"startdate" => "Start date",
					"Status" => "#Status",
					"responses" => "Responses (Unplayed/Total)",
					"Actions" => "Actions"
					);
$actions = array("Type" => "fmt_surveytype",
				'Status' => 'fmt_status',
				"startdate" => "fmt_job_startdate",
				"finishdate" => "fmt_obj_date",
				"responses" => "fmt_response_count",
				"Actions" => "fmt_jobs_actions"
				);

if(!$USER->authorize('leavemessage')){
	unset($titles["responses"]);
	unset($actions["responses"]);
}
showObjects($data, $titles, $actions, count($data) > 8, true);


endWindow();

include_once("navbottom.inc.php");
?>