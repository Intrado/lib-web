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

if (isset($_GET['deletetemplate'])) {
	$id = $_GET['deletetemplate'] + 0;
	if (userOwns("surveyquestionnaire",$id)) {
		$questionnaire = new SurveyQuestionnaire($id);
		$questionnaire->deleted = 1;
		$questionnaire->update();
	}
	redirectToReferrer();
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_surveyactions ($obj,$name) {

	return '<a href="surveytemplate.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
			. '<a href="survey.php?scheduletemplate=' . $obj->id . '">Schedule</a>&nbsp;|&nbsp;'
			. '<a href="surveys.php?deletetemplate=' . $obj->id . '">Delete</a>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:survey";
$TITLE = "Survey Builder";

include_once("nav.inc.php");
NewForm($f);

startWindow('My Survey Templates','padding: 3px;');
button_bar(button('create_new_survey', null,"surveytemplate.php?id=new") );

$questionnaires = DBFindMany("SurveyQuestionnaire", "from surveyquestionnaire where userid=$USER->id and deleted = 0 order by name");

$titles = array("name" => "Name",
				"description" => "Description",
				"Type" => "Type",
				"Questions" => "Questions",
				"Actions" => "Actions");
$formatters = array("Type" => "fmt_questionnairetype",
				"Questions" => "fmt_numquestions",
				"Actions" => "fmt_surveyactions");

showObjects($questionnaires,$titles,$formatters, count($questionnaires) > 8);

endWindow();

echo "<br>";

startWindow('My Active and Pending Survey Jobs','padding: 3px;',true, true);

$data = DBFindMany("Job","from job where userid=$USER->id and type='survey' and (status='new' or status='active' or status='cancelling') and deleted=0 order by id desc");

$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"Type" => "Type",
					"startdate" => "#Start date",
					"Status" => "#Status",
					"Actions" => "Actions"
					);
$actions = array("Type" => "fmt_surveytype",
				'Status' => 'fmt_status',
				"startdate" => "fmt_job_startdate",
				"finishdate" => "fmt_obj_date",
				"Actions" => "fmt_jobs_actions"
				);
showObjects($data, $titles, $actions, count($data) > 8, true);


endWindow();

echo "<br>";

startWindow('My Completed Survey Jobs','padding: 3px;',true, true);

$data = DBFindMany("Job","from job where userid=$USER->id and type='survey' and (status='complete' or status='cancelled') and deleted=0 order by id desc");

$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"Type" => "Type",
					"startdate" => "#Start date",
					"Status" => "#Status",
					"Actions" => "Actions"
					);
$actions = array("Type" => "fmt_surveytype",
				'Status' => 'fmt_status',
				"startdate" => "fmt_job_startdate",
				"finishdate" => "fmt_obj_date",
				"Actions" => "fmt_jobs_actions"
				);
showObjects($data, $titles, $actions, count($data) > 8, true);


endWindow();

EndForm();
include_once("navbottom.inc.php");
?>