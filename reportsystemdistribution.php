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
$TITLE = "Call Distribution";

include_once("nav.inc.php");

?>

<? startWindow("Average Systemwide Daily and Hourly Call Distribution", "padding: 3px;"); ?>


<div style="float: left;">
<img src="graph_weekly.png.php" onclick="popup('graph_weekly.png.php?big',790,500);" />
</div>

<div>
<img src="graph_hourly.png.php" onclick="popup('graph_hourly.png.php?big',790,500);" />
</div>

<img src="img/bug_lightbulb.gif" > Click a graph to enlarge.

<? endWindow();

include_once("navbottom.inc.php");
?>
