<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(!$MANAGERUSER->authorizedAny(array("aspcallgraphs", "logcollector")))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "template:template";
$TITLE = _L('View ASP Graphs / Log Collector');

include_once("nav.inc.php");

startWindow(_L('View ASP Graphs / Log Collector'));
?>

<? if($MANAGERUSER->authorized("aspcallgraphs")) { ?>
<a href="aspcallsbyday.php">by day for 2 years</a>
<br>
<? } ?>

<? if($MANAGERUSER->authorized("aspcallgraphs")) { ?>
<a href="aspcallsbydm.php">by dm avg for a month</a> or <a href="aspcallsbydmtime.php">by dm one day</a> or <a href="aspcallsalldmtime.php">by dm every day (multiple graphs 30 days)</a>
<br>
<? } ?>

<? if($MANAGERUSER->authorized("aspcallgraphs")) { ?>
<a href="aspcallsbyhour.php">by hour avg for a month</a>
<br>
<? } ?>

<? if($MANAGERUSER->authorized("aspcallgraphs")) { ?>
<a href="aspcallsbytime.php">by time avg for a month</a> or <a href="aspcallsbytimebyday.php">by time one day</a> or <a href="aspcallsalldaysbytime.php">by time every day (multiple graphs 30 days)</a>
<br>
<? } ?>

<? if($MANAGERUSER->authorized("aspcallgraphs")) { ?>
<a href="aspcallsdmday.php">graph per dm per day</a>
<br>
<? } ?>

<? if($MANAGERUSER->authorized("logcollector")) { ?>
<a href="aspcallslogcollector.php">log collector info/controls</a>
<br>
<? } ?>

<? if($MANAGERUSER->authorized("aspcallgraphs")) { ?>
<a href="aspcallssearch.php">Call search</a>
<? } ?>

<?
endWindow();
include_once("navbottom.inc.php");
?>
