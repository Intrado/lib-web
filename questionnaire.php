<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/text.inc.php");
include_once("obj/Message.obj.php");
include_once("obj/MessagePart.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/Voice.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/SurveyQuestion.obj.php");
include_once("obj/SurveyQuestionnaire.obj.php");



////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$USER->authorize('template') && 0) {
	redirect('unauthorized.php');
}

if (isset($_GET['id'])) {
	setCurrentQuestionnaire($_GET['id']);
	redirect();
}

if (isset($_GET['delete'])) {
	$qn = $_GET['delete'] + 0;
	if ($questionnaireid = getCurrentQuestionnaire()) {
		$query = "delete from surveyquestion where questionnaireid='$questionnaireid' and questionnumber='$qn'";
		QuickUpdate($query);
		$query = "update surveyquestion set questionnumber = questionnumber-1 where questionnaireid='$questionnaireid' and questionnumber > '$qn'";
		QuickUpdate($query);
	}
	redirect();
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


/*


Questionnaire builder:

	hasphone checkbox: if not checked, hides all phone related items
	hasweb checkbox: if not checked, hides all web/email related items
	generic message editor: disables audio whenever focus is in an email/web field

	questions:
		up/down controls
		add new question, just inserts more form stuff
		up to 2 message boxes for each of web/phone
*/





/****************** main message section ******************/

$f = "questionnaire";
$s = "main";
$reloadform = 0;


if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'add'))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//save changes

			$questionnaire = new SurveyQuestionnaire(getCurrentQuestionnaire());
			if ($questionnaire->id) {
				$questions = array_values(DBFindMany("SurveyQuestion","from surveyquestion where questionnaireid=$questionnaire->id order by questionnumber"));
			} else {
				$questions = array();
			}

			//save general stuff
			PopulateObject($f,$s,$questionnaire,array("name", "description",
							"hasphone", "hasweb", "dorandomizeorder",
							"machinemessageid","emailmessageid","intromessageid","exitmessageid"));

			$hasphone = GetFormData($f,$s,"hasphone");
			$hasweb = GetFormData($f,$s,"hasweb");

			//blank out stuff if we aren't using it
			if (!GetFormData($f,$s,"hasmachine") || !$hasphone)
				$questionnaire->machinemessageid = null;
			if (!GetFormData($f,$s,"hasintro") || !$hasphone)
				$questionnaire->intromessageid = null;
			if (!GetFormData($f,$s,"hasexit") || !$hasphone)
				$questionnaire->exitmessageid = null;
			if (!$hasweb)
				$questionnaire->emailmessageid = null;

			$questionnaire->userid = $USER->id;
			$questionnaire->update();

			setCurrentQuestionnaire($questionnaire->id);

			//sync questions
			$numquestions = min(GetFormData($f,$s,"numquestions"), 20);

			for ($i = 0; $i < $numquestions; $i++) {
				if (isset($questions[$i]))
					$question = $questions[$i];
				else
					$question = new SurveyQuestion();


				$question->questionnumber = $i;
				$question->questionnaireid = $questionnaire->id;

				$question->webmessage = trim(GetFormData($f,$s,"webmessage_$i"));
				$question->phonemessageid = GetFormData($f,$s,"phonemessageid_$i");
				$question->validresponse = GetFormData($f,$s,"validresponse_$i");

				if (!$hasphone)
					$question->phonemessageid = null;
				if (!$hasweb)
					$question->webmessage = null;

				//is this the new question? see if we should save it
				if (($question->id != null) ||
					($hasphone && $question->phonemessageid) ||
					($hasweb && strlen($question->webmessage) > 0)) {
					$question->update();
				}
			}

			//see if they are adding a new question
			if (!CheckFormSubmit($f,'add')) {
				redirect("surveys.php");
			} else {
				$reloadform = 1;
			}

			$reloadform = 1;
		}
	}
} else {
	$reloadform = 1;
}


$questionnaire = new SurveyQuestionnaire(getCurrentQuestionnaire());
if ($questionnaire->id) {
	$questions = array_values(DBFindMany("SurveyQuestion","from surveyquestion where questionnaireid=$questionnaire->id order by questionnumber"));
} else {
	$questions = array();
}

//add a new question to the end of we haven't hit 20 yet
if (count($questions) < 20) {
	$newquestion = new SurveyQuestion();
	$newquestion->questionnumber = count($questions);

	//default the response to the same as the previous question or 5
	if (count($questions) > 0)
		$newquestion->validresponse = $questions[count($questions)-1]->validresponse;
	else
		$newquestion->validresponse = 5;


	$questions[] = $newquestion;
}

if( $reloadform )
{
	ClearFormData($f);

	//populate form w/ questionnaire info

	$fields = array(
		array("name","text",1,50,true),
		array("description","text",1,50,false),
		array("hasphone","bool",0,1),
		array("hasweb","bool",0,1),
		array("dorandomizeorder","bool",0,1),
		array("machinemessageid","number","nomin","nomax",false),
		array("emailmessageid","number","nomin","nomax",false),
		array("intromessageid","number","nomin","nomax",false),
		array("exitmessageid","number","nomin","nomax",false)
		);

	PopulateForm($f,$s,$questionnaire,$fields);

	PutFormData($f,$s,"hasmachine",$questionnaire->machinemessageid > 0, "bool",0,1);
	PutFormData($f,$s,"hasintro",$questionnaire->intromessageid > 0, "bool",0,1);
	PutFormData($f,$s,"hasexit",$questionnaire->exitmessageid > 0, "bool",0,1);



	//putformdata for existing questions
	foreach ($questions as $question) {
		$qn = $question->questionnumber;
		PutFormData($f,$s,"webmessage_$qn",$question->webmessage,"text");
		PutFormData($f,$s,"phonemessageid_$qn",$question->phonemessageid,"number");
		PutFormData($f,$s,"validresponse_$qn",$question->validresponse,"text");
	}

	PutFormData($f,$s,"numquestions",count($questions));
}


//keep a copy of the original bool values since showing the form item clears form data for checkboxes!
$hasphone = GetFormData($f,$s,"hasphone");
$hasweb = GetFormData($f,$s,"hasweb");
$hasmachine = GetFormData($f,$s,"hasmachine");
$hasintro = GetFormData($f,$s,"hasintro");
$hasexit = GetFormData($f,$s,"hasexit");



$FIELDMAP = FieldMap::getAuthorizedMapNames();

$MESSAGES = array();
$MESSAGES['phone'] = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='phone' order by name");
$MESSAGES['email'] = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='email' order by name");


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function message_select($type, $form, $section, $name) {
	global $MESSAGES;

	NewFormItem($form,$section,$name, "selectstart",NULL,NULL,'id="' . $name . '"');
	NewFormItem($form,$section,$name, "selectoption", '- Select a Message -', "0");
	foreach ($MESSAGES[$type] as $message) {
		NewFormItem($form,$section,$name, "selectoption", $message->name, $message->id);
	}
	NewFormItem($form,$section,$name, "selectend");
}

function response_select($form,$section,$name) {
	$options = array(
		"2" => "1-2",
		"3" => "1-3",
		"4" => "1-4",
		"5" => "1-5",
		"6" => "1-6",
		"7" => "1-7",
		"8" => "1-8",
		"9" => "1-9"
	);

	NewFormItem($form,$section,$name, "selectstart");
	foreach ($options as $value => $response) {
		NewFormItem($form,$section,$name, "selectoption",$response,$value);
	}
	NewFormItem($form,$section,$name, "selectend");
}


//fmt_q* used for editing questions

function fmt_qnum($obj,$name) {
	return $obj->$name + 1;
}

function fmt_qphone($obj,$name) {
	global $f,$s;
	message_select("phone",$f,$s,"phonemessageid_" . $obj->questionnumber);



	echo button('play', "var audio = new getObj('phonemessageid_" . $obj->questionnumber . "').obj; if(audio.selectedIndex >= 1) popup('previewmessage.php?id=' + audio.options[audio.selectedIndex].value, 400, 400);");

}

function fmt_qweb($obj,$name) {
	global $f,$s;
	NewFormItem($f, $s,"webmessage_" . $obj->questionnumber,"textarea",40,4);
}

function fmt_qresponse($obj,$name) {
	global $f,$s;
	response_select($f,$s,"validresponse_" . $obj->questionnumber);
}

function fmt_actions($obj,$name) {
	global $f,$s;

	if (!$obj->id)
		return submit($f, 'add', 'add', 'add');
	else
		return '<a href="?delete=' . $obj->questionnumber . '">Delete</a>';
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:survey";
$TITLE = "Questionnaire Editor";

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'save','save'));

startWindow('Questionnaire Information',NULL,true, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td width="30%">Name</td>
					<td><? NewFormItem($f,$s,"name","text", 30,50); ?></td>
				</tr>
				<tr>
					<td>Description</td>
					<td><? NewFormItem($f,$s,"description","text", 30,50); ?></td>
				</tr>
				<tr>
					<td>Randomize Question Order</td>
					<td><? NewFormItem($f,$s,"description","checkbox"); ?></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
			<th align="right" class="windowRowHeader bottomBorder">Phone:</th>
			<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td width="30%">Phone Survey</td>
					<td><? NewFormItem($f,$s,"hasphone","checkbox",NULL,NULL,"onclick=\"setColVisability(questionstable,1,this.checked);\""); ?></td>
				</tr>
				<tr>
					<td>Leave message on answering machines</td>
					<td>
						<table border=0 cellpadding=0 cellspacing=0>
						<tr>
							<td><? NewFormItem($f,$s,"hasmachine","checkbox",NULL,NULL,"onclick=\"setVisibleIfChecked(this, 'machinemessage');\""); ?></td>
							<td id="machinemessage" <?= ($hasmachine ? "" : 'style="display:none;"')?>>&nbsp;<? message_select("phone",$f,$s,"machinemessageid"); ?></td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>Play introductory message</td>
					<td>
						<table border=0 cellpadding=0 cellspacing=0>
						<tr>
							<td><? NewFormItem($f,$s,"hasintro","checkbox",NULL,NULL,"onclick=\"setVisibleIfChecked(this, 'intromessage');\""); ?></td>
							<td id="intromessage" <?= ($hasintro ? "" : 'style="display:none;"')?>>&nbsp;<? message_select("phone",$f,$s,"intromessageid"); ?></td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>Play goodbye message</td>
					<td>
						<table border=0 cellpadding=0 cellspacing=0>
						<tr>
							<td><? NewFormItem($f,$s,"hasexit","checkbox",NULL,NULL,"onclick=\"setVisibleIfChecked(this, 'exitmessage');\""); ?></td>
							<td id="exitmessage" <?= ($hasexit ? "" : 'style="display:none;"')?>>&nbsp;<? message_select("phone",$f,$s,"exitmessageid"); ?></td>
						</tr>
						</table>
					</td>
				</tr>

			</table>
		</td>
	</tr>
	<tr valign="top">
			<th align="right" class="windowRowHeader bottomBorder">Web:</th>
			<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td width="30%">Web Survey</td>
					<td><? NewFormItem($f,$s,"hasweb","checkbox",NULL,NULL,"onclick=\"setColVisability(questionstable,2,this.checked);\""); ?></td>
				</tr>
				<tr>
					<td>Email Message</td>
					<td><? message_select("email",$f,$s,"emailmessageid"); ?></td>
				</tr>

			</table>
		</td>
	</tr>

</table>
<?
endWindow();
?>
<br>
<?
startWindow('Questions');

$titles = array("questionnumber" => "Question Number",
				"phonemessageid" => "Phone",
				"webmessage" => "Web",
				"validresponse" => "Response",
				"Actions" => "Actions");

$formatters = array("questionnumber" => "fmt_qnum",
				"phonemessageid" => "fmt_qphone",
				"webmessage" => "fmt_qweb",
				"validresponse" => "fmt_qresponse",
				"Actions" => "fmt_actions");

$questiondtableid = showObjects($questions,$titles,$formatters);

endWindow();

buttons();
EndForm();
?>

<script>

var questionstable = new getObj('<?= $questiondtableid ?>').obj;

<? if (!$hasphone) { ?>
	setColVisability(questionstable,1,false)
<? } ?>
<? if (!$hasweb) { ?>
	setColVisability(questionstable,2,false)
<? } ?>
</script>

<?
include_once("navbottom.inc.php");
?>