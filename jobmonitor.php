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

$jobid = DBSafe($_GET['jobid']);
if (!userOwns("job",$jobid) && !$USER->authorize('viewsystemreports')) {
	redirect("unauthorized.php");
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$job = new Job($jobid);

$noupdate = isset($_GET['noupdate']) ? "noupdate" : false;

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE = escapehtml($job->name);

include_once("popup.inc.php");

button_bar(button('Done', 'window.close()'),
		button("Refresh","UpdateTimer();"));

//startWindow('Job Status', 'padding: 3px;');
displayJobSummary($jobid,readonlyDBConnect());

?>

<?startWindow(_L("Phone"));?>
	<img id="realtimePhone" src="graph_job.png.php?type=phone&jobid=<?=$_GET['jobid']?>&junk=<?= rand() ?>" />
<?endWindow();?>

<?startWindow(_L("Email"));?>
	<img id="realtimeEmail" src="graph_job.png.php?type=email&jobid=<?=$_GET['jobid']?>&junk=<?= rand() ?>" />
<?endWindow();?>

<?startWindow(_L("SMS"));?>
	<img id="realtimeSMS" src="graph_job.png.php?type=sms&jobid=<?=$_GET['jobid']?>&junk=<?= rand() ?>" />
<?endWindow();?>
<?

//endWindow();
print('<br>');

include_once("popupbottom.inc.php");
if (!$noupdate) {
?>
<script type='text/javascript'>
<!--
function UpdateTimer() {
	$('realtimePhone').src = 'graph_job.png.php?type=phone&jobid=<?=$_GET['jobid']?>&foo=' + new Date();
	$('realtimeEmail').src = 'graph_job.png.php?type=email&jobid=<?=$_GET['jobid']?>&foo=' + new Date();
	$('realtimeSMS').src = 'graph_job.png.php?type=sms&jobid=<?=$_GET['jobid']?>&foo=' + new Date();
	setTimeout('UpdateTimer()', 15000);
}
-->
</script>
<? } ?>
