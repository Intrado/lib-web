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
if (isset($_GET['deletetemplate'])) {
	$id = $_GET['deletetemplate'] + 0;
	if (userOwns("surveyquestionnaire",$id)) {
		$questionnaire = new SurveyQuestionnaire($id);
		$questionnaire->deleted = 1;
		$questionnaire->update();
		notice(_L("The survey template, %s, is now deleted.", escapehtml($questionnaire->name)));
	} else {
		notice(_L("You do not have permission to delete this survey template."));
	}
	redirectToReferrer();
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_surveyactions ($obj,$name) {

	return '<a href="surveytemplatewiz.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
			. '<a href="survey.php?scheduletemplate=' . $obj->id . '">Schedule</a>&nbsp;|&nbsp;'
			. '<a href="surveys.php?deletetemplate=' . $obj->id . '">Delete</a>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:survey";
$TITLE = "Survey Builder";

include_once("nav.inc.php");


startWindow('My Survey Templates '. help('Surveys_MySurveyTemplates'),'padding: 3px;', true, true);
?>
<div class="feed_btn_wrap cf"><?= icon_button(_L('Create New Survey Template'),"add",null,"surveytemplatewiz.php?id=new") ?></div>
<?

$questionnaires = DBFindMany("SurveyQuestionnaire", "from surveyquestionnaire where userid=$USER->id and deleted = 0 order by name");

$titles = array("name" => "#Survey Template Name",
				"description" => "#Description",
				"Type" => "#Type",
				"Questions" => "#Questions",
				"Actions" => "Actions");
$formatters = array("Type" => "fmt_questionnairetype",
				"Questions" => "fmt_numquestions",
				"Actions" => "fmt_surveyactions");

showObjects($questionnaires,$titles,$formatters, count($questionnaires) > 8,true);

endWindow();



startWindow('My Surveys ' . help('SurveyBuilder_MyActiveAndPending'),'padding: 3px;',true, true);

?>
<div class="feed_btn_wrap cf"><?= icon_button(_L('Schedule Survey'),"add",null,"survey.php?id=new") ?></div>
<?

$data = DBFindMany("Job","from job where userid=$USER->id and type='survey' and (status='new' or status='scheduled' or status='processing' or status='procactive' or status='active' or status='cancelling') and deleted=0 order by id desc");

$titles = array(	"name" => "#Survey Name",
					"description" => "#Description",
					"Type" => "#Type",
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



startWindow('My Completed Surveys '  . help('SurveyBuilder_MyCompleted'),'padding: 3px;',true, true);

$data = DBFindMany("Job","from job where userid=$USER->id and type='survey' and (status='complete' or status='cancelled') and deleted=0 order by id desc");

$titles = array(	"name" => "#Survey Name",
					"description" => "#Description",
					"Type" => "#Type",
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