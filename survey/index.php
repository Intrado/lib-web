<?
$code = $_GET['s'];
if (strlen($code) > 50 || strlen($CUSTOMERURL) > 50)
	exit();

header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache");
header("Cache-Control: post-check=0,pre-check=0");
header("Cache-Control: max-age=0");
header("Pragma: no-cache");

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
include_once("../inc/utils.inc.php");
include_once("../inc/table.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/SurveyQuestionnaire.obj.php");
include_once("../obj/SurveyQuestion.obj.php");


$custname = QuickQuery("select name from customer where hostname='" . DBSafe($CUSTOMERURL) . "'");

//see if this code exists and has not already been used
$query = "select jobworkitemid, isused from surveyemailcode where code='" . DBSafe($code) . "'";
if ($row = QuickQueryRow($query)) {
	$exists = true;
	list($workitemid, $isused) = $row;

	//find and load the questionnaire
	$query = "select j.questionnaireid, j.id from job j
				inner join jobworkitem wi on (wi.jobid=j.id)
				where wi.id = $workitemid";
	list($questionnaireid,$jobid) = QuickQueryRow($query);
	$job = new Job($jobid);
	$questionnaire = new SurveyQuestionnaire($questionnaireid);

	if (!$isused) {
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
} else {
	$exists = false;
}

if ($_POST['submit'] && $exists && !$isused && $job->status == "active") {

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
		$query = "update surveyemailcode set isused=1, dateused=now(), loggedip='" . DBSafe($_SERVER["REMOTE_ADDR"]) . "'"
				. ", resultdata='" . DBSafe(http_build_query($results,'','&')) . "'"
				. " where code='" . DBSafe($code) . "'";
		QuickUpdate($query);

		//we should also try to cancel the phone call in case it has not already been sent. Just fail it out if it is queued.
		QuickUpdate("update jobworkitem wi_phone "
					. "inner join jobworkitem wi_email on (wi_phone.personid=wi_email.personid "
											."and wi_phone.jobid=wi_email.jobid and wi_email.type='email') "
					."inner join surveyemailcode sec on (sec.jobworkitemid = wi_email.id) "
					."set wi_phone.status='fail' "
					."where sec.code='" . DBSafe($code) . "' and wi_phone.type='phone' and "
					."wi_phone.status in ('queued','scheduled','waiting')");
	}

	header("Location: " . $_SERVER["REQUEST_URI"] . "&thanks");
}


?>
<html>
<head>
	<title><?= isset($questionnaire->webpagetitle) ? htmlentities($questionnaire->webpagetitle) : "Survey" ?></title>
	<link href='../css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='../css/style.css' type='text/css' rel='stylesheet' media='screen'>
</head>
<body>
<div id='container'>
<div id='mainscreen'>
<div id='orgtitle'><?= htmlentities($custname) ?></div>
			<img id='brand' src='../img/school_messenger.gif' /><img src='img/spacer.gif' width="1" height="40" />

<div id="contentbody">
<div id="navtitle"><?= htmlentities($questionnaire->webpagetitle) ?></div>

<div id='shadowblock'>
	<table width='100%' border='0' cellpadding='0' cellspacing='0'>
		<tr><td id='shadowcontent'>
<? /* ----------------------------------------------------- */ ?>
<?
//if no survey found, display error page
if (!$exists) {
?>
	<h3>Sorry, the survey link has expired or is invalid</h3>
<?
} else if ($isused) {
	//if already taken, show thanks page
	if ($questionnaire->webexitmessage && isset($_GET['thanks']))
		if ($questionnaire->usehtml)
			echo $questionnaire->webexitmessage;
		else
			echo nl2br(htmlentities($questionnaire->webexitmessage));
	else if (isset($_GET['thanks'])) {
?>
	<h3>Your response has been recorded. Thank you for participating in this survey.</h3>
<?
	} else {
?>
	<h3>Your responses to this survey were previously noted. Thank you for your particiaption.</h3>
<?
	}
} else if ($job->status != "active") {
?>
	<h3>Sorry, the survey has expired.</h3>
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
	<input style="margin: 5px;" alt="Submit" type="image" name="submit[]" src="../img/b1_submit.gif" onMouseOver="this.src='../img/b2_submit.gif';" onMouseOut="this.src='../img/b1_submit.gif';">
	</form>
<?
	endWindow();
}
?>
<? /* ----------------------------------------------------- */ ?>
		</td>
			<td id='shadowright' valign="top" align="left"><img class="noprint" src="../img/shadow_top_right.gif"></td>
		</tr>
		<tr>
			<td id='shadowbottom' valign="top" align="left"><img class="noprint" src="../img/shadow_bottom_left.gif"></td>
			<td valign="top" align="left"><img class="noprint" src="../img/shadow_bottom_right.gif"></td>
		</tr>
	</table>
</div>

</div>

</div>
</div>
</body>
</html>