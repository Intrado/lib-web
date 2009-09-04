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
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/auth.inc.php");

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

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


if (!isset($_GET['notpopup'])) {
	$TITLE = escapehtml($job->name);
	include_once("popup.inc.php");

	button_bar(button('Done', 'window.close()'),
		button("Refresh","window.location='jobmonitor.php?jobid={$job->id}&noupdate={$noupdate}';"));
}

if (!$noupdate)
	print('<div id="jobmonitor">');

displayJobSummary($job->id,readonlyDBConnect());

if (!in_array($job->status, array("new", "scheduled"))) {
	if ($job->sendphone) { ?>
		<div>
			<img src="graph_job.png.php?type=phone&jobid=<?=$_GET['jobid']?>&junk=<?= rand() ?>" />
		</div>
	<? }
	if ($job->sendemail) { ?>
		<div>
			<img src="graph_job.png.php?type=email&jobid=<?=$_GET['jobid']?>&junk=<?= rand() ?>" />
		</div>
	<? }
	if ($job->sendsms) { ?>
		<div>
			<img src="graph_job.png.php?type=sms&jobid=<?=$_GET['jobid']?>&junk=<?= rand() ?>" />
		</div>
	<? }
}

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
