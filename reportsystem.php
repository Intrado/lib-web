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

$PAGE = "reports:system";
$TITLE = "Usage Statistics";

include_once("nav.inc.php");

?>

<? startWindow("Total Messages Delivered (top 15 results)", "padding: 3px;"); ?>


<div style="float: left;">
<img src="graph_users.png.php" onclick="popup('graph_users.png.php?big',790,500);" />
</div>

<div>
<img src="graph_jobs.png.php" onclick="popup('graph_jobs.png.php?big',790,500);" />
</div>

<img src="img/bug_lightbulb.gif" > Click a graph to enlarge.

<? endWindow();

include_once("navbottom.inc.php");
?>
