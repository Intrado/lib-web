<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/text.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

if (isset($_GET['dmid'])) {
	$_SESSION['dmid'] = $_GET['dmid'] +0;
	redirect();
} else {
	$dmid = $_SESSION['dmid'];
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$dmname = QuickQuery("select name from custdm where dmid=?", false, array($dmid));

$status = json_decode(QuickQuery("select poststatus from custdm where dmid=?", false, array($dmid)));
//var_dump($status);

$systemstats = (array) $status[0];
$dispatchers = array();

foreach ($systemstats as $key => $value) {
	if (strpos($key, "comerr") === 0) {
		$dispatcher = substr($key, 7);
		$dispatchers[$dispatcher]['comerr'] = $value;
	}
	if (strpos($key, "comtimeout") === 0) {
		$dispatcher = substr($key, 11);
		$dispatchers[$dispatcher]['comtimeout'] = $value;
	}
}

$resourcedata = array();
foreach ($status as $row) {
	$row = (array) $row;
	if ($row['name'] == 'system') continue;
	$resourcedata[] = $row;
}
$resourcetitles = array(	"name" => "Name",
							"rtype" => "Type",
							"rstatus" => "State",
							"starttime" => "Start Time",
							"result" => "Result"
						);



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE="admin:settings";
$TITLE="Flex Appliance: ".escapehtml($dmname);

include_once("nav.inc.php");
buttons(submit("dmstatus", "section", 'Done'));

/*
startWindow("Raw System Stats");
?>
<table>
<?
foreach ($systemstats as $key => $value) {
?>
	<tr><td><?= $key ?></td><td><?= $value ?></td></tr>
<?
}
?>
</table>
<?
endWindow();
*/

?>
<table width="100%"><tr><td>
<?
startWindow("System Statistics");
include_once("dmsysstats.inc.php");
endWindow();

startWindow("Completed Tasks");
?>
<table width="100%">
	<tr><td width="20%">Answered: </td><td><?= "24" ?></td></tr>
	<tr><td>Machine: </td><td><?= "11" ?></td></tr>
	<tr><td>Busy: </td><td><?= "2" ?></td></tr>
	<tr><td>No Answer: </td><td><?= "5" ?></td></tr>
	<tr><td>Bad Number: </td><td><?= "1" ?></td></tr>
	<tr><td>Failed: </td><td><?= "0" ?></td></tr>
</table>
<?
endWindow();
?>
</td><td valign="top">
<?
startWindow("Active Resources", 'padding: 3px;');
?>
<table>
<?

if (count($status) > 1) {
	showTable($resourcedata, $resourcetitles);
} else {
	echo "There are no active resources at this time.  The system is idle.<br>";
}

?>
</table>
<?
endWindow();
?>
</td></tr></table>
<?
buttons();
include_once("navbottom.inc.php");
?>