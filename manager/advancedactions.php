<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");

if (!$MANAGERUSER->authorizedAny(array("billablecalls","bouncedemailsearch","passwordcheck", "emergencyjobs", "runqueries", "tollfreenumbers", "manageserver", "systemdm", "superuser","aspcallgraphs", "logcollector")))
	exit("Not Authorized");

include_once("nav.inc.php");
?>

<ul>

<? if ($MANAGERUSER->authorized("billablecalls")) { ?>
<li><a href="billablecalls.php">Billable Calls</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("bouncedemailsearch")) { ?>
<li><a href="bouncedemailsearch.php">Bounced Email Search</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("passwordcheck")) { ?>
<li><a href="passwordcheck.php">Check for bad/similar passwords</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("emergencyjobs")) { ?>
<li><a href="emergencyjobs.php">List of Recent Jobs</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("runqueries") || $MANAGERUSER->authorized("editqueries")) { ?>
<li><a href="querylist.php">Customer Queries</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("tollfreenumbers")) { ?>
<li><a href="tollfreenumbers.php">Add Toll Free Numbers</a></li>
<? } ?>

<? if (isset($SETTINGS['servermanagement']['manageservers']) && $SETTINGS['servermanagement']['manageservers'] && $MANAGERUSER->authorized("manageserver")) { ?>
<li><a href="serverlist.php">Manage Servers</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("systemdm")) { ?>
<li><a href="dmgroupblock.php">DM Group Pattern Blocking</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("superuser")) { ?>
<li><a href="users.php">Edit Users</a></li>
<? } ?>

<? if ($MANAGERUSER->authorizedAny(array("logcollector","aspcallgraphs"))) { ?>
<li><a href="aspcallsindex.php">View ASP Graphs / Log Collector</a></li>
<? } ?>


</ul>
<?
include_once("navbottom.inc.php");
?>