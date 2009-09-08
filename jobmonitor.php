<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/JobType.obj.php");
require_once("inc/auth.inc.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/JobSummaryReport.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

/*
if (!$USER->authorize("sendphone")) {
	redirect("unauthorized.php");
}
*/

$jobid = $_GET['jobid'] + 0;
if (!userOwns("job",$jobid) && !$USER->authorize('viewsystemreports')) {
	redirect("unauthorized.php");
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$noupdate = isset($_GET['noupdate']) && $_GET['noupdate'] == true;
$job = new Job($jobid);

// Prepare $_SESSION['jobstats'][$job->id], needed by graph_detail_callprogress.php
$readonlyconn = readonlyDBConnect();
$jobstats = array ("validstamp" => time());
if ($job->sendphone) {
	$jobstats['phone'] = array(
			"A" => 0,
			"M" => 0,
			"N" => 0,
			"B" => 0,
			"X" => 0,
			"F" => 0,
			"notattempted" => 0,
			"blocked" => 0,
			"duplicate" => 0,
			"nocontacts" => 0,
			"declined" => 0
	);
	$result = Query(JobSummaryReport::getDestinationResultQuery("and rp.jobid=?", "and rp.type=?"), $readonlyconn, array($job->id, 'phone'));
	while ($row = DBGetRow($result)) {
		$jobstats["phone"][$row[1]] += $row[0];
	}
}
if ($job->sendemail) {
	$jobstats['email'] = array(
			"sent" => 0,
			"unsent" => 0,
			"notattempted" => 0,
			"blocked" => 0,
			"duplicate" => 0,
			"nocontacts" => 0,
			"declined" => 0
	);
	$result = Query(JobSummaryReport::getDestinationResultQuery("and rp.jobid=?", "and rp.type=?"), $readonlyconn, array($job->id, 'email'));
	while ($row = DBGetRow($result)) {
		$jobstats["email"][$row[1]] += $row[0];
	}
}
if ($job->sendsms) {
	$jobstats['sms'] = array(
			"sent" => 0,
			"unsent" => 0,
			"notattempted" => 0,
			"blocked" => 0,
			"duplicate" => 0,
			"nocontacts" => 0,
			"declined" => 0
	);
	$result = Query(JobSummaryReport::getDestinationResultQuery("and rp.jobid=?", "and rp.type=?"), $readonlyconn, array($job->id, 'sms'));
	while ($row = DBGetRow($result)) {
		$jobstats["sms"][$row[1]] += $row[0];
	}
}
$_SESSION['jobstats'][$job->id] = $jobstats;

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


if (!isset($_GET['notpopup'])) {
	$TITLE = '';//escapehtml($job->name);
	include_once("popup.inc.php");

	button_bar(button('Done', 'window.close()'),
		button("Refresh","window.location='jobmonitor.php?jobid={$job->id}&noupdate={$noupdate}';"));
	echo '<br/>';
}

if (!$noupdate)
	print('<div id="jobmonitor">');

$jobtype = new JobType($job->jobtypeid);
$showdestinations = true;
$notice = "";

switch ($job->status) {
	case 'new':
		$notice = _L("This <b>%s</b> job is not submitted, no data available", $jobtype->name);
		$showdestinations = false;
		break;
	case 'scheduled':
		$scheduledtime = strtotime($job->startdate . " " . $job->starttime);
		$difftimestamp = $scheduledtime - time();
		$notice = _L("This <b>%s</b> job is scheduled to run in ", $jobtype->name);
		if ($difftimestamp >= 60) {
			$diffdays = floor($difftimestamp / (60*60*24));
			$diffhours = floor(($difftimestamp - ($diffdays*60*60*24)) / (60*60));
			$diffminutes = floor(($difftimestamp - ($diffdays*60*60*24 + $diffhours*60*60)) / 60);
			if ($diffdays > 0)
				$notice .= "$diffdays days ";
			if ($diffhours > 0)
				$notice .= "$diffhours hours ";
			if ($diffminutes > 0)
				$notice .= "$diffminutes minutes ";
		} else {
			$notice = _L("This <b>%s</b> job will run shortly", $jobtype->name);
		}
		$showdestinations = false;
		break;
	case 'processing':
		$notice = _L("Please wait while this <b>%s</b> job is processed: ", $jobtype->name) . $job->percentprocessed . "%";
		$showdestinations = false;
		break;
	case 'procactive':
	case 'active':
		$notice = _L("This <b>%s</b> job is active", $jobtype->name);
		break;
	case 'cancelling':
		$notice = _L("Please wait while this <b>%s</b> job is cancelled", $jobtype->name);
		$showdestinations = false;
		break;
	case 'cancelled':
	case 'complete':
		$notice = _L("This <b>%1s</b> job finished on %2s", $jobtype->name, fmt_job_enddate($job, null));
		break;
	default:
		echo '<h1>ERROR: Unknown status</h1>';
		break;
}

if ($showdestinations) {
	if ($job->sendphone)
		$phoneinfo = JobSummaryReport::getPhoneInfo($job->id, $readonlyconn);
	if ($job->sendemail)
		$emailinfo = JobSummaryReport::getEmailInfo($job->id, $readonlyconn);
	if ($job->sendsms)
		$smsinfo = JobSummaryReport::getSmsInfo($job->id, $readonlyconn);
}

$urloptions = "graph_detail_callprogress.png.php?jobid={$job->id}&valid={$jobstats['validstamp']}&scaley=0.5";
startWindow(escapehtml($job->name));
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<td class="bottomBorder" colspan=2>
			<?=$notice?>
		</td>
	</tr>
<?
if ($showdestinations && $job->sendphone) { ?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Phone:</th>
			<td class="bottomBorder">
					<table  border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
						<tr class="listHeader" align="left" valign="bottom">
							<th># of Phones</th>
							<th>Completed</th>
							<th>Remaining</th>
							<th>Total Attempts</th>
							<th>% Contacted</th>
						</tr>
						<tr>
							<td><?=$phoneinfo[0]+0?></td>
							<td><?=$phoneinfo[1]+0?></td>
							<td><?=$phoneinfo[2]+0?></td>
							<td><?=$phoneinfo[6]+0?></td>
							<td><?=sprintf("%0.2f", isset($phoneinfo[8]) ? $phoneinfo[8] : "") . "%" ?></td>
						</tr>
					</table>
				<img src='<?=$urloptions?>&type=phone'/>
			</td>
		</tr>
<? }
if ($showdestinations && $job->sendemail) { ?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Email:</th>
			<td class="bottomBorder">
				<table  border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
						<tr class="listHeader" align="left" valign="bottom">
							<th># of Emails</th>
							<th>Completed</th>
							<th>Remaining</th>
							<th>% Contacted</th>
						</tr>
						<tr>
							<td><?=$emailinfo[0]+0?></td>
							<td><?=$emailinfo[1]+0?></td>
							<td><?=$emailinfo[2]+0?></td>
							<td><?=sprintf("%0.2f", isset($emailinfo[6]) ? $emailinfo[6] : "") . "%" ?></td>
						</tr>
				</table>
				<img src='<?=$urloptions?>&type=email'/>
			</td>
		</tr>
<? }
if ($showdestinations && $job->sendsms) { ?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">SMS:<th>
			<td class="bottomBorder">
				<table  border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
						<tr class="listHeader" align="left" valign="bottom">
							<th># of SMS</th>
							<th>Completed</th>
							<th>Remaining</th>
							<th>% Contacted</th>
						</tr>
						<tr>
							<td><?=$smsinfo[0]+0?></td>
							<td><?=$smsinfo[1]+0?></td>
							<td><?=$smsinfo[2]+0?></td>
							<td><?=sprintf("%0.2f", isset($smsinfo[7]) ? $smsinfo[7] : "") . "%" ?></td>
						</tr>
				</table>
				<img src='<?=$urloptions?>&type=sms'/>
			</td>
		</tr>
<? }
?>
</table>
<?
endWindow();

if (in_array($job->status, array("cancelled", "complete"))) {?>
	<script type='text/javascript'>
		jobmonitorstop = true;
	</script>
<? }

if (!$noupdate) {
	print('</div><br>'); ?>

	<script type='text/javascript'>
		jobmonitorupdater = new Ajax.PeriodicalUpdater('jobmonitor', 'jobmonitor.php', {
			evalScripts: true,
			method: 'get',
			parameters: {
				notpopup: true,
				noupdate: true, // Ajax.PeriodicalUpdater will take care of updating from now on.
				jobid: <?=$job->id?>
			},
			onSuccess: function() {
				if (jobmonitorstop && jobmonitorupdater) {
					jobmonitorupdater.stop();
				}
			},
			frequency: 15,
			decay: 1 // Decay value of 1 means no decay.
		});
	</script>
<? }

if (!isset($_GET['notpopup'])) {
	include_once("popupbottom.inc.php");
}
?>