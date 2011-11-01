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
if ($job->hasPhone()) {
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
if ($job->hasEmail()) {
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
if ($job->hasSMS()) {
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

$notice = "";
switch ($job->status) {
	case 'new':
		$notice = _L("This job is not submitted, no data available.");
		break;
	case 'scheduled':
		$scheduledtime = strtotime($job->startdate . " " . $job->starttime);
		$difftimestamp = $scheduledtime - time();
		$notice = _L("This job is scheduled to run in ");
		if ($difftimestamp >= 60) {
			$diffdays = floor($difftimestamp / (60*60*24));
			$diffhours = floor(($difftimestamp - ($diffdays*60*60*24)) / (60*60));
			$diffminutes = floor(($difftimestamp - ($diffdays*60*60*24 + $diffhours*60*60)) / 60);
			if ($diffdays > 0)
				$notice .= "$diffdays days ";
			if ($diffhours > 0)
				$notice .= "$diffhours hours ";
			if ($diffminutes > 0)
				$notice .= "$diffminutes " . ($diffminutes == 1 ? 'minute' : 'minutes');
			$notice .= ".";
		} else {
			$notice = _L("This job will run shortly.");
		}
		break;
	case 'processing':
		$notice = _L("Please wait while this job is processed: <b>") . $job->percentprocessed . "%</b>";
		break;
	case 'procactive':
		$notice = _L("Some data is available. This job is <b>%s%%</b>  processed.", $job->percentprocessed);
		break;
	case 'active':
		$notice = _L("This job is active.");
		break;
	case 'cancelling':
		$notice = _L("Please wait while this job is cancelled.");
		break;
	case 'cancelled':
	case 'complete':
		$notice = _L("This job finished on %s.", fmt_job_enddate($job, null));
		break;
	default:
		$notice = _L('ERROR: Unknown job status.');
		break;
}

$destinationresults = array();
if ($job->hasPhone()) {
	// find jobstats for job
	global $JOB_STATS;
	$JOB_STATS = array();
	$query = "select jobid, name, value from jobstats where jobid = ? and name = 'complete-seconds-phone-attempt-0-sequence-0'";
	$jobstats_objects = QuickQueryMultiRow($query, false, null, array($job->id));
	foreach ($jobstats_objects as $obj) {
		$JOB_STATS[$obj[0]][$obj[1]] = $obj[2];
	}
	
		$phoneinfo = JobSummaryReport::getPhoneInfo($job->id, $readonlyconn);
		$destinationresults['phone'] = array(
			'recipients' => $phoneinfo[0]+0,
			'completed' => $phoneinfo[1]+0,
			'remaining' => $phoneinfo[2]+0,
			'attempts' => $phoneinfo[6]+0,
			'firstpass' => fmt_obj_job_first_pass($job, 'activedate'),
			'percentcontacted' => sprintf("%0.2f", isset($phoneinfo[8]) ? $phoneinfo[8] : "") . '%'
		);
}
if ($job->hasEmail()) {
		$destinationresults['email'] = JobSummaryReport::getEmailInfo($job->id, $readonlyconn);
		$destinationresults['email'] = array(
			'recipients' => $destinationresults['email'][0]+0,
			'completed' => $destinationresults['email'][1]+0,
			'remaining' => $destinationresults['email'][2]+0,
			'percentcontacted' => sprintf("%0.2f", isset($destinationresults['email'][6]) ? $destinationresults['email'][6] : "") . '%'
		);
}
if ($job->hasSMS()) {
		$destinationresults['sms'] = JobSummaryReport::getSmsInfo($job->id, $readonlyconn);
		$destinationresults['sms'] = array(
			'recipients' => $destinationresults['sms'][0]+0,
			'completed' => $destinationresults['sms'][1]+0,
			'remaining' => $destinationresults['sms'][2]+0,
			'percentcontacted' => sprintf("%0.2f", isset($destinationresults['sms'][7]) ? $destinationresults['sms'][7] : "") . '%'
		);
}
$windowtitle = _L("Monitoring job, %1s, last updated %2s", escapehtml($job->name), date("g:i:s a",$jobstats['validstamp']));
if (!in_array($job->status, array('complete', 'cancelled')))
	$windowtitle .= " <img src='img/ajax-loader.gif'/>";

$imageurl = "graph_detail_callprogress.png.php?jobid={$job->id}&valid={$jobstats['validstamp']}&scaley=0.75";

////////////////////////////////////////////////////////////////////////////////
// AJAX
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['ajax'])) {
	header('Content-Type: application/json');
	$data = array(
		'jobstatus' => $job->status,
		'windowtitle' => $windowtitle,
		'imageurl' => $imageurl,
		'destinationresults' => $destinationresults,
		'notice' => $notice
	);
	exit(json_encode(!empty($data) ? $data : false));
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
if (!isset($_GET['notpopup'])) {
	$TITLE = '';//escapehtml($job->name);
	include_once("popup.inc.php");

	button_bar(button('Done', 'window.close()'));
	echo '<br/>';
}

if (!$noupdate)
	print('<div id="jobmonitor">');

startWindow($windowtitle);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<td colspan=2 id='notice'>
			<?=$notice?>
		</td>
	</tr>
<?
if ($job->hasPhone()) { ?>
		<tr class='destination'>
			<th align="right" class="windowRowHeader bottomBorder">Phone:</th>
			<td class="bottomBorder">
					<table  border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
						<tr class="listHeader" align="left" valign="bottom">
							<th># of Phones</th>
							<th>Completed</th>
							<th>Remaining</th>
							<th>Total Attempts</th>
							<th>First Pass</th>
							<th>% Contacted</th>
						</tr>
						<tr>
							<td id='recipientsphone'><?=$destinationresults['phone']['recipients']?></td>
							<td id='completedphone'><?=$destinationresults['phone']['completed']?></td>
							<td id='remainingphone'><?=$destinationresults['phone']['remaining']?></td>
							<td id='attemptsphone'><?=$destinationresults['phone']['attempts']?></td>
							<td id='firstpassphone'><?=$destinationresults['phone']['firstpass']?></td>
							<td id='percentcontactedphone'><?=$destinationresults['phone']['percentcontacted']?></td>
						</tr>
					</table>
				<img style='width:500px;height:300px' src='<?=$imageurl?>&type=phone' id='phonegraph'/>
			</td>
		</tr>
<? }
if ($job->hasEmail()) { ?>
		<tr class='destination'>
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
							<td id='recipientsemail'><?=$destinationresults['email']['recipients']?></td>
							<td id='completedemail'><?=$destinationresults['email']['completed']?></td>
							<td id='remainingemail'><?=$destinationresults['email']['remaining']?></td>
							<td id='percentcontactedemail'><?=$destinationresults['email']['percentcontacted']?></td>
						</tr>
				</table>
				<img style='width:500px;height:300px' src='<?=$imageurl?>&type=email' id='emailgraph'/>
			</td>
		</tr>
<? }
if ($job->hasSMS()) { ?>
		<tr class='destination'>
			<th align="right" class="windowRowHeader bottomBorder">SMS:</th>
			<td class="bottomBorder">
				<table  border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
						<tr class="listHeader" align="left" valign="bottom">
							<th># of SMS</th>
							<th>Completed</th>
							<th>Remaining</th>
							<th>% Contacted</th>
						</tr>
						<tr>
							<td id='recipientssms'><?=$destinationresults['sms']['recipients']?></td>
							<td id='completedsms'><?=$destinationresults['sms']['completed']?></td>
							<td id='remainingsms'><?=$destinationresults['sms']['remaining']?></td>
							<td id='percentcontactedsms'><?=$destinationresults['sms']['percentcontacted']?></td>
						</tr>
				</table>
				<img style='width:500px;height:300px' src='<?=$imageurl?>&type=sms' id='smsgraph'/>
			</td>
		</tr>
<? }
?>
</table>

<script type='text/javascript'>
displayDestinations = function(jobstatus) {
	if (jobstatus == 'active' || jobstatus == 'procactive' || jobstatus == 'cancelled' || jobstatus == 'complete')
		$$('tr.destination').invoke('show');
	else
		$$('tr.destination').invoke('hide');
};
displayDestinations('<?=$job->status?>');
</script>

<?
endWindow();

if (!$noupdate) {
	print('</div><br>'); ?>

	<script type='text/javascript'>
		refreshPage = function() {
			new Ajax.Request('jobmonitor.php', {
				evalScripts: false,
				method: 'get',
				parameters: {
					ajax: true,
					notpopup: true,
					noupdate: true,
					jobid: <?=$job->id?>
				},
				onSuccess: function(transport) {
					var data = transport.responseJSON;
					if (!data) {
						return; // Silent error.
					}

					$('notice').update(data.notice);
					$('jobmonitor').down('div.windowtitle').update(data.windowtitle);

					displayDestinations(data.jobstatus);

					if ($('phonegraph'))
						updateDestination(data,'phone');
					if ($('emailgraph'))
						updateDestination(data,'email');
					if ($('smsgraph'))
						updateDestination(data,'sms');

					if (data.jobstatus != 'complete' && data.jobstatus != 'cancelled')
						setTimeout("refreshPage();", 10000);
				}
			});
		};

		setTimeout("refreshPage();", 10000);

		updateDestination = function(data, type) {
			var img = new Element('img', {'src':data.imageurl+'&type='+type});
			img.observe('load', function(event, type) {
				$(type+'graph').src = this.src;
			}.bindAsEventListener(img, type));

			var results = data.destinationresults[type];
			for (var result in results) {
				$(result + type).update(results[result]);
			}
		};
	</script>
<? }


if (!isset($_GET['notpopup'])) {
	include_once("popupbottom.inc.php");
}
?>