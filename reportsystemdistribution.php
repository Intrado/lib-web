<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:distribution";
$TITLE = "Call Distribution for " . getSystemSetting("displayname");

include_once("nav.inc.php");

startWindow("Average Systemwide Daily and Hourly Call Distribution (Last 4 Weeks)", "padding: 3px;"); 
?>

	<div style="float: left;">
	<img src="graph_weekly.png.php" onclick="popup('graph_weekly.png.php?big',790,500);" />
	</div>

	<div>
	<img src="graph_hourly.png.php" onclick="popup('graph_hourly.png.php?big',790,500);" />
	</div>
<?
endWindow();

?><br><?

startWindow("Total Systemwide Call Distribution (Last 4 Weeks)", "padding: 3px;");
?>
	<div>
	<img src="graph_daily.png.php" onclick="popup('graph_daily.png.php?big',790,500);" />
	</div>
<?
endWindow();
?><br><?

startWindow("Total Systemwide Call Distribution (Last 12 Months)", "padding: 3px;");
?>
	<div>
	<img src="graph_monthly.png.php" onclick="popup('graph_monthly.png.php?big',790,500);" />
	</div>
<? 
endWindow();
?>
	<img src="img/bug_lightbulb.gif" > Click a graph to enlarge.
<?
include_once("navbottom.inc.php");
?>
