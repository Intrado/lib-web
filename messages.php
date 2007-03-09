<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Message.obj.php");
include_once("obj/MessagePart.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/SurveyQuestionnaire.obj.php");
include_once("obj/SurveyQuestion.obj.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if (isset($_SESSION['messageid']) && ($_SESSION['messageid']== $deleteid))
		$_SESSION['messageid'] = NULL;
	if (userOwns("message",$deleteid)) {
		QuickUpdate("update message set deleted=1 where id='$deleteid'");
		redirect();
	}
}

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

function fmt_actions ($obj,$name) {
	if ($obj->type == "phone") {
		$query = "select mp.audiofileid, count(*) as cnt, mp.type, mp.id
					from message m, messagepart mp
					where m.id=mp.messageid
					and m.id='" . DBSafe($obj->id) . "'
					group by m.id
					having cnt = 1 and mp.type='A' ";
		$audiofileid = QuickQuery($query);
	} else {
		$audiofileid = null;
	}


	$simpleplaybtn = button("play", "popup('previewaudio.php?close=1&id=$audiofileid', 400, 350);");
	$advancedplaybtn = button("play", "popup('previewmessage.php?close=1&id=$obj->id', 400, 500);");
	$editbtn = '<a href="message' . $obj->type . '.php?id=' . $obj->id . '">Edit</a>';
	$deletebtn = '<a href="messages.php?delete=' . $obj->id . '" onclick="return confirmDelete();">Delete</a>';
	$renamebtn = '<a href="messagerename.php?id=' . $obj->id . '">Rename</a>';

	if ($audiofileid) {
		return  "$simpleplaybtn&nbsp;|&nbsp;$renamebtn&nbsp;|&nbsp;$deletebtn";
	} else {
		if ($obj->type == "phone") {
			return "$advancedplaybtn&nbsp;|&nbsp;$editbtn&nbsp;|&nbsp;$renamebtn&nbsp;|&nbsp;$deletebtn";
		} else {
			return "$editbtn&nbsp;|&nbsp;$renamebtn&nbsp;|&nbsp;$deletebtn";
		}
	}
}

function fmt_phonetype ($obj,$name) {

	$query = "select mp.audiofileid, count(*) as cnt, mp.type, mp.id
				from message m, messagepart mp
				where m.id=mp.messageid
				and m.id='" . DBSafe($obj->id) . "'
				group by m.id
				having cnt = 1 and mp.type='A' ";
	$audiofileid = QuickQuery($query);
	if ($audiofileid) {
		return "Simple";
	} else {
		return "Advanced";
	}
}



function fmt_creator ($obj,$name) {
	$creator = DBFind("User","from user where id=$obj->userid");
	return $creator->shortName();
}


function fmt_surveyactions ($obj,$name) {

	return '<a href="surveytemplate.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
			. '<a href="survey.php?scheduletemplate=' . $obj->id . '">Schedule</a>&nbsp;|&nbsp;'
			. '<a href="messages.php?deletetemplate=' . $obj->id . '">Delete</a>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:messages";
$TITLE = "Message " . ($USER->authorize('survey') ? "& Survey Template " : "") . "Builder";

include_once("nav.inc.php");



$scrollThreshold = 8;

if($USER->authorize('sendphone')) {
	$data = DBFindMany("Message",", (name + 0) as foo from message where type='phone' and userid=$USER->id and deleted=0 order by foo, name");
	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My Phone Messages ' . help('Messages_MyPhoneMessages', NULL, 'blue'), 'padding: 3px;', true, true);

	if ($USER->authorize('starteasy')) {
		button_bar(button('callmetorecord', "popup('callme.php?origin=message',500,450);") . help('AudioFileEditor_CallMeToRecord'),
			button('createadphmess', "document.location='messagephone.php?id=new'") . help('Messages_AddPhoneMessage'),
			button('openaudiolib', "popup('audio.php',500,450);") . help('Messages_AudioFileEditor'));
	} else {
		button_bar(button('createadphmess', "document.location='messagephone.php?id=new'") . help('Messages_AddPhoneMessage'),
			button('openaudiolib', "popup('audio.php',500,450);") . help('Messages_AudioFileEditor'));
	}



	$phonetitles = array(	"name" => "#Name",
						"description" => "#Description",
						"Type" => "#Type",
						"Actions" => "Actions"
					);

	showObjects($data, $phonetitles, array("Type" => "fmt_phonetype", "Actions" => "fmt_actions", "userid" => "fmt_creator"), $scroll, true);
	endWindow();
	echo '<br>';
}


$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"Actions" => "Actions"
					);


if($USER->authorize('sendemail')) {
	$data = DBFindMany("Message",", (name + 0) as foo from message where type='email' and userid=$USER->id and deleted=0 order by foo, name");
	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My Email Messages ' . help('Messages_MyEmailMessages', NULL, 'blue'), 'padding: 3px;', true, true);

	button_bar(button('createemail', NULL,'messageemail.php?id=new') . help('Messages_AddEmailMessage'));

	showObjects($data, $titles, array("Actions" => "fmt_actions", "userid" => "fmt_creator"), $scroll, true);
	endWindow();
	echo '<br>';
}


if($USER->authorize('sendprint')) {
	$data = DBFindMany("Message",", (name + 0) as foo from message where type='print' and userid=$USER->id and deleted=0 order by foo, name");
	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My Print Messages ' . help('Messages_MyPrintMessages', NULL, 'blue'), 'padding: 3px;', true, true);
	button_bar(button('createprint', NULL,'messageprint.php?id=new') . help('Messages_AddprintMessage'));
	showObjects($data, $titles, array("Actions" => "fmt_actions", "userid" => "fmt_creator"), $scroll, true);
	endWindow();
	echo '<br>';
}


if($USER->authorize('survey')) {

	startWindow('My Survey Templates '. help('Surveys_MySurveyTemplates', NULL, 'blue'),'padding: 3px;', true, true);
	button_bar(button('create_new_survey', null,"surveytemplate.php?id=new") . help('Surveys_CreateNewTemplateButton') );

	$questionnaires = DBFindMany("SurveyQuestionnaire", "from surveyquestionnaire where userid=$USER->id and deleted = 0 order by name");

	$titles = array("name" => "#Name",
					"description" => "#Description",
					"Type" => "#Type",
					"Questions" => "#Questions",
					"Actions" => "Actions");
	$formatters = array("Type" => "fmt_questionnairetype",
					"Questions" => "fmt_numquestions",
					"Actions" => "fmt_surveyactions");

	showObjects($questionnaires,$titles,$formatters, count($questionnaires) > 8,true);

	endWindow();
}

include_once("navbottom.inc.php");
?>