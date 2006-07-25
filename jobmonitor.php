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


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

/*
if (!$USER->authorize("sendphone")) {
	redirect("unauthorized.php");
}
*/

$jobid = DBSafe($_GET['jobid']);
if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports') && customerOwns("job",$jobid))) {
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

$TITLE = htmlentities($job->name);

include_once("popup.inc.php");

button_bar(button('done', 'window.close()'),
		button("refresh","new getObj('realtime').obj.src = 'graph_job.png.php?jobid=$jobid&foo=' + new Date();"));

//startWindow('Job Status', 'padding: 3px;');

?><img id="realtime" src="graph_job.png.php?jobid=<?=$_GET['jobid']?>" /><?

//endWindow();
print('<br>');

include_once("popupbottom.inc.php");
if (!$noupdate) {
?>
<script language="JavaScript">
<!--
function UpdateTimer() {
	document.realtime.src = 'graph_job.png.php?jobid=<?=$_GET['jobid']?>&foo=' + new Date();
	setTimeout('UpdateTimer()', 15000);
}
-->
</script>
<? } ?>