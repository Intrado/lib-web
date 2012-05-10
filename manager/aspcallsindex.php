<?
require_once("common.inc.php");
if(!$MANAGERUSER->authorizedAny(array("aspcallgraphs", "logcollector")))
	exit("Not Authorized");
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

