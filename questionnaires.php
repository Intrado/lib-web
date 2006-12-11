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
if (!$USER->authorize('survey') && 0) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


if (isset($_GET['deletequestionnaire'])) {
	$id = $_GET['deletequestionnaire'] + 0;
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
function fmt_surveytype ($obj,$name) {
	$types = array();
	if ($obj->hasphone)
		$types[] = "Phone";
	if ($obj->hasweb)
		$types[] = "Web";
	return implode(" &amp; ", $types);
}
function fmt_numquestions ($obj,$name) {
	return QuickQuery("select count(*) from surveyquestion where questionnaireid=$obj->id");
}
function fmt_actions ($obj,$name) {

	return '<a href="questionnaire.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
			. '<a href="surveys.php?deletequestionnaire=' . $obj->id . '">Delete</a>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:questionnaire";
$TITLE = "Questionnaire Builder";

include_once("nav.inc.php");
NewForm($f);


startWindow('My Questionnaires','padding: 3px;');
button_bar(button('add', null,"questionnaire.php?id=new") );

$questionnaires = DBFindMany("SurveyQuestionnaire", "from surveyquestionnaire where userid=$USER->id and deleted = 0 order by name");

$titles = array("name" => "Name",
				"description" => "Description",
				"Type" => "Type",
				"Questions" => "Questions",
				"Actions" => "Actions");
$formatters = array("Type" => "fmt_surveytype",
				"Questions" => "fmt_numquestions",
				"Actions" => "fmt_actions");

showObjects($questionnaires,$titles,$formatters, count($questionnaires) > 8);

endWindow();

EndForm();
include_once("navbottom.inc.php");
?>