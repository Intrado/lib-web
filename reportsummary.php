<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

if (!isset($_GET['jobid'])) {
	$jobid = false;
} else {
	$jobid = $_GET['jobid'] + 0;
	//check userowns or customerowns and viewsystemreports
	if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports'))) {
		redirect('unauthorized.php');
	}
}
////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
if ($jobid) {
	$job = new Job($jobid);


	//TODO check if there are new workitems, then display a pie chart with % queued
	if (QuickQuery("select personid from reportperson where status='new' and jobid='$jobid' limit 1")) {
		$isprocessing = true;
	} else {
		$isprocessing = false;

		$validstamp = time();
		$jobstats = array ("validstamp" => $validstamp);

		$jobtypes = explode(",",$job->type);

		//--------------- SURVEY ---------------
		if(in_array("survey",$jobtypes) !== false) {

			$questionnaire = new SurveyQuestionnaire($job->questionnaireid);
			if ($questionnaire->hasphone)
				$jobtypes[] = "phone";
			if ($questionnaire->hasweb)
				$jobtypes[] = "email";


			//stats:

			//% people participated (subset of contacted)
			$query = "select count(*) as cnt
						from reportperson rp
						left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
						and rp.status='success' and rc.result='A' and rc.participated=1";
			$phoneparticipants = QuickQuery($query);

			//TODO: fix jobworkitem related code
			$query = "select count(*) from surveyemailcode sec
					inner join jobworkitem wi on (sec.jobworkitemid = wi.id)
					where sec.isused=1 and wi.jobid=$jobid";

			$emailparticipants = QuickQuery($query);

			$query = "select sr.questionnumber, sr.answer, sr.tally, sq.reportlabel from surveyresponse sr left join surveyquestion sq on (sr.questionnumber = sq.questionnumber and sq.questionnaireid=$questionnaire->id) where sr.jobid=$jobid order by questionnumber, answer";
			$res = Query($query);
			echo mysql_error();

			$questions = array();
			while ($row = DBGetRow($res)) {
				$questions[$row[0]]['answers'][$row[1]] = $row[2];
				$questions[$row[0]]['label'] = $row[3] != NULL ? $row[3] : ("Question " . ($row[0] + 1));
			}

			$jobstats['survey'] = array();
			$jobstats['survey']['phoneparticipants'] = $phoneparticipants;
			$jobstats['survey']['emailparticipants'] = $emailparticipants;
			$jobstats['survey']['questions'] = $questions;
		}
		//-------------------------------------


		//--------------- PHONE ---------------
		if(in_array("phone",$jobtypes) !== false) {
			//people, dupes, contacted, notcontacted, %complete (actually from phone)

			$query = "select count(*) as cnt, rp.status
						from reportperson rp
						where rp.jobid='$jobid' and rp.type='phone' group by rp.status";
			//then need to stitch the results back together by summing them.

			$totalpeople = 0;
			$duplicates = 0;
			$contacted = 0;
			$notcontacted = 0;
			$result = Query($query);
			while ($row = DBGetRow($result)) {
				$totalpeople += $row[0];

				if ($row[1] == "success")
					$contacted += $row[0];
				else if ($row[1] == "duplicate")
					$duplicates += $row[0];
				else
					$notcontacted += $row[0];
			}

			//phones by cp
			$query = "select count(*) as cnt, rc.result, sum(rp.status not in ('success','fail') and rc.numattempts < $job->maxcallattempts) as remaining
						from reportperson rp
						left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
			where rp.jobid = '$jobid'
			and rp.status != 'duplicate' and rp.type='phone'
			group by rc.result";
			//may need to clean up, null means not called yet
			//do math for the % completed
			$cpstats = array (
				"C" => 0,
				"A" => 0,
				"M" => 0,
				"N" => 0,
				"B" => 0,
				"X" => 0,
				"F" => 0,
				"nullcp" => 0
			);
			$remainingcalls = 0;
			$totalcalls = 0;
			$result = Query($query);
			while ($row = DBGetRow($result)) {
				$totalcalls += $row[0];
				$index = $row[1] !== NULL ? $row[1] : "nullcp";
				$cpstats[$index] += $row[0];
				if ($row[1] != "A" && $row[1] != "M") {
					$remainingcalls += $row[2];
				}
			}

			$jobstats["phone"] = $cpstats; //start with the cp codes
			//add people stats
			$jobstats["phone"]["totalpeople"] = $totalpeople;
			$jobstats["phone"]["duplicates"] = $duplicates;
			$jobstats["phone"]["contacted"] = $contacted;
			$jobstats["phone"]["notcontacted"] = $notcontacted;

			$jobstats["phone"]["remainingcalls"] = $remainingcalls;
			$jobstats["phone"]["totalcalls"] = $totalcalls;
			$jobstats["phone"]["percentcomplete"] = $totalcalls ? ($totalcalls - $remainingcalls)/$totalcalls : 0;

		}
		//-------------------------------------

		//--------------- EMAIL ---------------
		if(in_array("email",$jobtypes) !== false) {
			//email people, emails, % sent
			$query = "select count(*)
						from reportperson rp
						where rp.jobid='$jobid' and rp.type='email'";

			$emailpeople = QuickQuery($query);

			$query = "select count(*) totalemails, sum(rc.numattempts>0) as sent
						from reportperson rp
						left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
						where rp.jobid='$jobid' and rp.type='email'";
			list($totalemails, $sentemails) = QuickQueryRow($query);

			$jobstats["email"] = array();
			$jobstats["email"]["emailpeople"] = $emailpeople;
			$jobstats["email"]["totalemails"] = $totalemails;
			$jobstats["email"]["sentemails"] = $sentemails;
			$jobstats["email"]["percentsent"] = $sentemails/$totalemails;


		}
		//-------------------------------------

		//--------------- PRINT ---------------
		if(in_array("print",$jobtypes) !== false) {
			//print people %sent
			$query = "select count(*) as totoal, sum(rp.status='success') as printed
						from reportperson rp
						where rp.jobid='$jobid' and rp.type='print'";
			list($totalprint, $printed) = QuickQueryRow($query);

			$jobstats["print"] = array();
			$jobstats["print"]["totalprint"] = $totalprint;
			$jobstats["print"]["printed"] = $printed;
		}
		//-------------------------------------

		//save all these stats to the session with a jobid and timestamp so we can use them in the pie charts
		$_SESSION['jobstats'][$jobid] = $jobstats;
		$urloptions = "jobid=$jobid&valid=$validstamp";
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:jobsummary";
$TITLE = "Standard Job Report" . ($jobid ? " - " . $job->name : "");

include_once("nav.inc.php");
//TODO buttons for notification log: download csv, view call details
if ($jobid)
	echo buttons(button('Refresh', 'window.location.reload()'), button('Done', 'window.history.go(-1)'));
else
	buttons();


//--------------- Select window ---------------
startWindow("Select", NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
<tr>
	<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Job:</th>
	<td width="1%"><select name="jobid" id="jobid" onchange="location.href='?jobid=' + this.value">
			<option value='0'>-- Select a Job --</option>
<?
$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') order by id desc");
foreach ($jobs as $job) {
echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
}
?>
	</select><select id="jobid_archived" style="display: none" onchange="location.href='?jobid=' + this.value">
			<option value='0'>-- Select a Job --</option>
<?
$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 2 and status!='repeating' order by id desc");
foreach ($jobs as $job) {
echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
}
?>
	</select></td>
	<td aligh="left"><input id="check_archived" type="checkbox" name="check_archived" value="true" onclick = "setHiddenIfChecked(this, 'jobid'); setVisibleIfChecked(this, 'jobid_archived'); ">
	Show archived jobs</td>
	</tr>
	</table>

<?
endWindow();

echo "<br>";
//--------------- Processing message ---------------
if ($jobid && $isprocessing) {
	startWindow("Report Summary - Processing Job", NULL, false);
?>
	<div style="padding: 10px;">Please wait while your job is processed...</div>
	<img src="graph_processing.png.php?jobid=<?= $jobid ?>" >
	<meta http-equiv="refresh" content="10;url=reportsummary.php?jobid=<?= $jobid ?>&t=<?= rand() ?>">

<?
	endWindow();
} else if ($jobid) {

	//--------------- Summary ---------------
	startWindow("Report Summary", NULL, false);
?>

	<table border="0" cellpadding="3" cellspacing="0" width="100%">
<? if (isset($jobstats["phone"])) { ?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone:</th>
			<td class="bottomBorder"><img src="graph_summary_contacted.png.php?<?= $urloptions ?>"><img src="graph_summary_completed.png.php?<?= $urloptions ?>"></td>
		</tr>
<? } ?>

<? if (isset($jobstats["email"])) { ?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Email:</th>
			<td class="bottomBorder"><img src="graph_summary_email.png.php?<?= $urloptions ?>"></td>
		</tr>
<? } ?>


	</table>

<?
	endWindow();

if (isset($jobstats['survey'])) {
	echo "<br>";
//--------------- Survey ---------------
	startWindow("Survey Results", NULL, false);
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Participation</th>
			<td class="bottomBorder">
<? if ($jobstats['survey']['phoneparticipants'] || $jobstats['survey']['emailparticipants']) { ?>
				<img src="graph_survey_participation.png.php?<?= $urloptions ?>">
<? } else { ?>
				No one has yet participated in this survey.
<? } ?>
			</td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Responses</th>
			<td class="bottomBorder">
<? if (count($jobstats['survey']['questions'])) { ?>

			<div id="surveyquestion_graph"><a href="#" onclick="hide('surveyquestion_graph'); show('surveyquestion_table');">Show Table</a><br><img src="graph_survey_questions.png.php?<?= $urloptions ?>"></div>


			<div id="surveyquestion_table" style="display: none;"><a href="#" onclick="hide('surveyquestion_table'); show('surveyquestion_graph');">Show Graphs</a>
			<table width="100%" cellpadding="3" cellspacing="1" class="list">
<?
			$titles = array("Question");
			for ($x = 1; $x <= 9; $x++)
				$titles[] = " #$x";
			$titles[10] = "Total";

			$data = array();
			foreach ($jobstats['survey']['questions'] as $question) {
				$line = array_fill(1,9,"");
				$line[0] = $question['label'];
				foreach ($question['answers'] as $answer => $tally) {
					$line[$answer] = $tally;
				}
				$line[10] = array_sum($line);
				$data[] = $line;
			}

			showtable($data,$titles,array());


?>
			</table>
			</div>


<? } ?>
			</td>
		</tr>
	</table>
<?
	endWindow();
}
	echo "<br>";
	//--------------- Detail ---------------
	startWindow("Report Detail", NULL, false);
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
<? if (isset($jobstats["phone"])) { ?>

	<!--
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone<br>(by People)</th>
			<td class="bottomBorder">
				<div class="floatingreportdata"><u>People</u><br><?= number_format($jobstats["phone"]["totalpeople"]) ?></div>
				<div class="floatingreportdata"><u>Duplicates</u><br><?= number_format($jobstats["phone"]["duplicates"]) ?></div>
				<div class="floatingreportdata"><u>Contacted</u><br><?= number_format($jobstats["phone"]["contacted"]) ?></div>
				<div class="floatingreportdata"><u>Not Contacted</u><br><?= number_format($jobstats["phone"]["notcontacted"]) ?></div>
				<div class="floatingreportdata"><u>Complete</u><br><?= sprintf("%0.2f%%",100 * $jobstats["phone"]["percentcomplete"]) ?></div>
			</td>
			<td class="bottomBorder" align="left"><img src="graph_summary_contacted.png.php?<?= $urloptions ?>"></td>
		</tr>
	-->
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone:</th>
			<td class="bottomBorder" >
				<div class="floatingreportdata"><u>Phone #s</u><br><?= number_format($jobstats["phone"]["totalcalls"]) ?></div>

				<div class="floatingreportdata"><u>Answered</u><br><?= number_format($jobstats["phone"]["A"]) ?></div>
				<div class="floatingreportdata"><u>Machine</u><br><?= number_format($jobstats["phone"]["M"]) ?></div>
				<div class="floatingreportdata"><u>Calling</u><br><?= number_format($jobstats["phone"]["C"]) ?></div>
				<div class="floatingreportdata"><u>No Answer</u><br><?= number_format($jobstats["phone"]["N"]) ?></div>
				<div class="floatingreportdata"><u>Busy</u><br><?= number_format($jobstats["phone"]["B"]) ?></div>
				<div class="floatingreportdata"><u>Disconnect</u><br><?= number_format($jobstats["phone"]["X"]) ?></div>
				<div class="floatingreportdata"><u>Fail</u><br><?= number_format($jobstats["phone"]["F"]) ?></div>
				<div class="floatingreportdata"><u>Not Attempted</u><br><?= number_format($jobstats["phone"]["nullcp"]) ?></div>

			</td>
			<td class="bottomBorder" align="left"><img src="graph_detail_callprogress.png.php?<?= $urloptions ?>"></td>
		</tr>

<? } ?>

<? if (isset($jobstats["email"])) { ?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Email:</th>
			<td class="bottomBorder" >
				<div class="floatingreportdata"><u>People</u><br><?= number_format($jobstats["email"]["emailpeople"]) ?></div>

				<div class="floatingreportdata"><u>Email Addresses</u><br><?= number_format($jobstats["email"]["totalemails"]) ?></div>
				<div class="floatingreportdata"><u>% Sent</u><br><?= sprintf("%0.2f%%",100 * $jobstats["email"]["percentsent"]) ?></div>
			</td>
			<td class="bottomBorder" align="left"><img src="graph_summary_email.png.php?<?= $urloptions ?>"></td>
		</tr>
<? } ?>

		<tr>
			<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Contact Log:</th>
			<td >&nbsp;<a href="report.php?jobid=<?= $jobid?>">View</a>&nbsp;|&nbsp;<a href="report.php/report.csv?jobid=<?= $jobid?>&csv=true">Download CSV File</a>
			</td>
		</tr>

	</table>

<?
	endWindow();
}

echo buttons();

include_once("navbottom.inc.php");
?>