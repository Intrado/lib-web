<?

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

apache_note("CS_APP","survey"); //for logging
apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

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
include_once("../inc/utils.inc.php");
require_once("../inc/memcache.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
include_once("../inc/html.inc.php");
include_once("../inc/table.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/SurveyQuestionnaire.obj.php");
include_once("../obj/SurveyQuestion.obj.php");


$custdisplayname = getCustomerName($CUSTOMERURL);

$reason = authorizeSurveyWeb($code, $CUSTOMERURL);

$scheme = getCustomerData($CUSTOMERURL);

if ($reason == 'ok') {
	// find the jobid
	$query = "select jobid from surveyweb where code=?";
	list($jobid) = QuickQueryRow($query, false, false, array($code));

	//find and load the questionnaire
	$query = "select questionnaireid from job where id = ?";
	list($questionnaireid) = QuickQueryRow($query, false, false, array($jobid));
	$job = new Job($jobid);
	$questionnaire = new SurveyQuestionnaire($questionnaireid);

		$questions = array_values(DBFindMany("SurveyQuestion","from surveyquestion where questionnaireid=$questionnaire->id order by questionnumber"));

		//if we need to randomize question order, we should do it in a determinable way so that if the user returns to the form multiple times or reloads the page the questions dont move around.
		if ($questionnaire->dorandomizeorder) {
			mt_srand(abs(crc32($code))); //seed random generator w/ determinable data
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
if (isset($_POST['Submit']) && $reason == 'ok') {

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
		$query = "update surveyweb set status='web', dateused=now(), loggedip=?"
				. ", resultdata=?"
				. " where code=?";
		QuickUpdate($query, false, array($_SERVER["REMOTE_ADDR"], http_build_query($results,'','&'), $code));

		//TODO  we should also try to cancel the phone call in case it has not already been sent. Just fail it out if it is queued.
	}

	$reason = 'prevresponse';
	$exitmsg = true; // display the exit message
}

$TITLE= isset($questionnaire->webpagetitle) ? $questionnaire->usehtml ? $questionnaire->webpagetitle : escapehtml($questionnaire->webpagetitle) : "";


function doLogo () {
	$logohash = crc32("cid".getSystemSetting("_logocontentid"));
	$clickurl = getSystemSetting("_logoclickurl");
	if($clickurl != "" && $clickurl != "http://")
	echo '<a href="' . $clickurl . '" target="_blank"><img src="../logo.img.php?hash=' . $logohash .'" alt="Logo"></a>';
	else
	echo '<img src="../logo.img.php?hash=' . $logohash .'" alt="">';
}

//Do inpage CSS
$theme = $scheme['_brandtheme'];
$primary = $scheme['colors']['_brandprimary'];
$theme1 = "#" . $scheme['colors']['_brandtheme1'];
$theme2 = "#" . $scheme['colors']['_brandtheme2'];
$globalratio = $scheme['colors']['_brandratio'];

$fade1 = "E5E5E5";
$fade2 = "999999";
$fade3 = "595959";

$newfade1 = fadecolor($primary, $fade1, $globalratio);
$newfade2 = fadecolor($primary, $fade2, $globalratio);
$newfade3 = fadecolor($primary, $fade3, $globalratio);

$primary = "#" . $primary;


//Takes 2 hex color strings and 1 ratio to apply to to the primary:original
function fadecolor($primary, $fade, $ratio){
	$primaryarray = array(substr($primary, 0, 2), substr($primary, 2, 2), substr($primary, 4, 2));
	$fadearray = array(substr($fade, 0, 2), substr($fade, 2, 2), substr($fade, 4, 2));
	$newcolorarray = array();
	for($i = 0; $i<3; $i++){
		$newcolorarray[$i] = dechex(round(hexdec($primaryarray[$i]) * $ratio + hexdec($fadearray[$i])*(1-$ratio)));
	}
	$newcolor = "#" . implode("", $newcolorarray);
	return $newcolor;
}

header('Content-type: text/html; charset=UTF-8') ;
?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<title><?= isset($questionnaire->webpagetitle) ? escapehtml($questionnaire->webpagetitle) : "Survey" ?></title>
	<link href='../css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='../css/css.inc.php' type="text/css" rel="stylesheet" media="screen, print">
</head>


<body>

<div class="survey_banner cf">
	
	<div class="survey_logo"><? doLogo() ?></div>
	<h2 class="survey_custname"><?= escapehtml($custdisplayname) ?></h2>
	
</div><!-- .survey_banner -->

<div class="survey_wrap cf">

<h3 class="survey_title"><?= $TITLE ?></h3>
				



<?
//if no survey found, display error page
if ($reason != 'ok' && $reason != 'prevresponse' && $reason != 'expired') {
?>
	<br><br><h3 style="margin-left: 15px;">Sorry, the survey link has expired or is invalid</h3><br><br>
<?
} else if ($reason == 'prevresponse') {

	//if already taken, show thanks page
	if ($exitmsg && $questionnaire->webexitmessage) {
		if ($questionnaire->usehtml) {
			echo "<br><br>";
			echo $questionnaire->webexitmessage;
			echo "<br><br>";
		} else {
			echo '<br><br><h3>';
			echo nl2br(escapehtml($questionnaire->webexitmessage));
			echo "</h3><br><br>";
		}

	}
	else if ($exitmsg) {
?>
	<br><br><h3 style="margin-left: 15px;">Your response has been recorded. Thank you for participating in this survey.</h3><br><br>
<?
	} else {
?>
	<br><br><h3 style="margin-left: 15px;">Your responses to this survey were previously noted. Thank you for your participation.</h3><br><br>
<?
	}
} else if ($reason == 'expired') {
?>
	<br><br><h3 style="margin-left: 15px;">Sorry, the survey has expired.</h3><br><br>
<?
} else {
	//otherwise, show survey questions form
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


<?
	$displaynumber = 1;
	foreach ($questions as $question) {
?>
		<div class="survey_question cf">
			
			<div class="question_num">Question <?= $displaynumber ?></div>
			
			<div class="question_choice">
			<p><?= $questionnaire->usehtml ? $question->webmessage : nl2br(escapehtml($question->webmessage)) ?></p>
			
			<?
		for ($x = 1; $x <= $question->validresponse; $x++)
			{ ?><span onclick="this.firstChild.checked = true;"><input type="radio" name="q<?= $question->questionnumber ?>" value="<?= $x ?>"><b><?= $x ?></b>&nbsp;</span><? }
?>
			</div>
			
		</div>
		
<?
		$displaynumber++;
	}
?>


	<input type="submit" name="Submit" value="Submit" class="submit_survey">
	</form>



<?
}
?>



</div><!-- .survey_wrap -->

</body>
</html>
