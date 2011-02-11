<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");

if (!$MANAGERUSER->authorizedAny(array("billablecalls","bouncedemailsearch","superuser")))
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

<? if ($MANAGERUSER->authorized("runqueries")) { ?>
<li><a href="querylist.php">Run Queries</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("editqueries")) { ?>
<li><a href="queryedit.php">Edit/Add Queries</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("superuser")) { ?>
<li><a href="editroles.php">Edit Manager User Roles</a></li>
<? } ?>



</ul>
<?
include_once("navbottom.inc.php");
?>