<?

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];


if ($IS_COMMSUITE) {
	$CUSTOMERURL = "default";
} /*CSDELETEMARKER_START*/ else {
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
} /*CSDELETEMARKER_END*/


$code = (isset($_GET['s']) ? $_GET['s'] : "");
if (strlen($code) > 50 || strlen($CUSTOMERURL) > 50)
	exit();

header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache");
header("Cache-Control: post-check=0,pre-check=0");
header("Cache-Control: max-age=0");
header("Pragma: no-cache");

require_once("XML/RPC.php");
require_once("../inc/auth.inc.php");
require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
include_once("../inc/utils.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/table.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/SurveyQuestionnaire.obj.php");
include_once("../obj/SurveyQuestion.obj.php");


$custname = getCustomerName($CUSTOMERURL);

$reason = authorizeSurveyWeb($code, $CUSTOMERURL);

if ($reason == 'ok') {
	// find the jobid
	$query = "select jobid from surveyweb where code='" . DBSafe($code) . "'";
	list($jobid) = QuickQueryRow($query);

	//find and load the questionnaire
	$query = "select questionnaireid from job where id = $jobid";
	list($questionnaireid) = QuickQueryRow($query);
	$job = new Job($jobid);
	$questionnaire = new SurveyQuestionnaire($questionnaireid);

		$questions = array_values(DBFindMany("SurveyQuestion","from surveyquestion where questionnaireid=$questionnaire->id order by questionnumber"));

		//if we need to randomize question order, we should do it in a determinable way based on the workitemid or something so that if the user returns to the form multiple times or reloads the page the questions dont move around.
		if ($questionnaire->dorandomizeorder) {
			mt_srand($workitemid); //seed random generator w/ determinable data
			//go through the array and randomly swap items into each element from the rest of the array
			for ($x = 0; $x < count($questions) -1; $x++) {
				$dest = mt_rand($x,count($questions)-1);
				$tmp = $questions[$dest];
				$questions[$dest] = $questions[$x];
				$questions[$x] = $tmp;
			}
		}
}

$exitmsg = false;
if (isset($_POST['submit']) && $reason == 'ok') {

	$resultsql = array();
	$results = array();
	foreach ($questions as $question) {
		$qname = 'q' . $question->questionnumber;
		if (isset($_POST[$qname])) {
			$selection = $_POST[$qname] + 0;
			if ($selection > 0 && $selection <= $question->validresponse) {
				$resultsql[] = "($jobid,$question->questionnumber,$selection,1)";
				$results[$qname] = $selection;
			}
		}
	}

	if (count($results)) {
		$query = "insert into surveyresponse (jobid,questionnumber,answer,tally) values "
				. implode(",",$resultsql) . " on duplicate key update tally=tally + values(tally)";
		QuickUpdate($query);
		$query = "update surveyweb set status='web', dateused=now(), loggedip='" . DBSafe($_SERVER["REMOTE_ADDR"]) . "'"
				. ", resultdata='" . DBSafe(http_build_query($results,'','&')) . "'"
				. " where code='" . DBSafe($code) . "'";
		QuickUpdate($query);

		//TODO  we should also try to cancel the phone call in case it has not already been sent. Just fail it out if it is queued.
	}

	$reason = 'prevresponse';
	$exitmsg = true; // display the exit message
}


?>
<html>
<head>
	<title><?= isset($questionnaire->webpagetitle) ? htmlentities($questionnaire->webpagetitle) : "Survey" ?></title>
	<link href='../css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='../css/style.css' type='text/css' rel='stylesheet' media='screen'>
</head>
<body>

<div>
	<table width="100%" border=0 cellpadding=0 cellspacing=0 background="../img/header_bg.gif">
	<tr>
	<td><img src="../img/logo.gif"></td>
	<td><div class="custname"><?= htmlentities(isset($questionnaire->webpagetitle) ? $questionnaire->webpagetitle : "") ?></div></td>
	</tr>
	</table>
</div>

<div id='shadowblock'>
	<table width='100%' border='0' cellpadding='0' cellspacing='0'>
		<tr><td id='shadowcontent'>
<? /* ----------------------------------------------------- */ ?>
<?
//if no survey found, display error page
if ($reason != 'ok' && $reason != 'prevresponse' && $reason != 'expired') {
?>
	<br><br>Sorry, the survey link has expired or is invalid<br><br>
<?
} else if ($reason == 'prevresponse') {

	//if already taken, show thanks page
	if ($exitmsg && $questionnaire->webexitmessage) {
		echo "<br><br>";
		if ($questionnaire->usehtml)
			echo $questionnaire->webexitmessage;
		else
			echo nl2br(htmlentities($questionnaire->webexitmessage));
		echo "<br><br>";
	}
	else if ($exitmsg) {
?>
	<br><br>Your response has been recorded. Thank you for participating in this survey.<br><br>
<?
	} else {
?>
	<br><br>Your responses to this survey were previously noted. Thank you for your participation.<br><br>
<?
	}
} else if ($reason == 'expired') {
?>
	<br><br><h3>Sorry, the survey has expired.</h3><br><br>
<?
} else {
	//otherwise, show survey questions form
	startWindow('Survey',null,false,false);
?>
	<form name="surveyform" method="POST" action="<?= $_SERVER["REQUEST_URI"] ?>" onsubmit="return validate_survey();">

<script>

function validate_survey () {
	var numquestions = <?= count($questions) ?>;
	var form = document.forms[0];
	var someresponses = false;
	var allresponses = true;
	for (var i = 0; i < numquestions; i++) {
		var radiobtns = form['q' + i];
		var hasselection = false;

		for (var j = 0; j < radiobtns.length; j++) {
			if (radiobtns[j].checked)
				hasselection = true;
		}
		if (hasselection)
			someresponses = true;
		else
			allresponses = false;
	}

	if (someresponses && !allresponses) {
		return confirm('You did not select a response for all of the survey questions. You will not be able to change your selections if you choose to continue; however the responses that have been selected will be recorded.\n\nWould you like to submit these responses anyway?');
	} else if (!someresponses) {
		alert("You did not select any responses to the survey questions. Please select your responses by clicking the radio buttons next to the number of your response");
		return false;
	} else {
		return true;
	}
}
</script>

	<table border="0" cellpadding="3" cellspacing="0" width="100%">
<?
	$displaynumber = 1;
	foreach ($questions as $question) {
?>
		<tr valign="top">
			<th align="right" class="windowRowHeader bottomBorder" rowspan="2">Question <?= $displaynumber ?></th>
			<td><?= $questionnaire->usehtml ? $question->webmessage : nl2br(htmlentities($question->webmessage)) ?></td>
		</tr>
		<tr>
			<td class="bottomBorder" style="vertical-align: middle;">
<?
		for ($x = 1; $x <= $question->validresponse; $x++)
			{ ?><span onclick="this.firstChild.checked = true;"><input type="radio" name="q<?= $question->questionnumber ?>" value="<?= $x ?>"><b><?= $x ?></b>&nbsp;</span><? }
?>
			</td>
		</tr>
<?
		$displaynumber++;
	}
?>
	</table>

	<input style="margin: 5px;" alt="Submit" type="image" name="submit[]" src="../img/b1_easycall2.gif" onMouseOver="this.src='../img/b2_easycall2.gif';" onMouseOut="this.src='../img/b1_easycall2.gif';">
	</form>

<!-- copy from endWindow() to set image location "../img" -->
				</div></div>
			</div>
		</td>
		<td width="6" valign="top" background="../img/window_shadow_right.gif"><img src="../img/window_shadow_topright.gif"></td>
	</tr>
	<tr>
		<td background="../img/window_shadow_bot.gif"><img src="../img/window_shadow_botleft.gif"></td>
		<td><img src="../img/window_shadow_botright.gif"></td>
	</table>
</div>
<!-- end of copy -->

<?
}
?>
<? /* ----------------------------------------------------- */ ?>
		</td>
		</tr>
	</table>
</div>
</body>
</html>