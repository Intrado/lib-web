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

if ($status == NULL) {

} else {

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
	if (strpos($key, "comreadtimeout") === 0) {
		$dispatcher = substr($key, 15);
		$dispatchers[$dispatcher]['comreadtimeout'] = $value;
	}
}

$activeresourcedata = array();
$completeresourcedata = array();

foreach ($status as $row) {
	$row = (array) $row;
	if ($row['name'] == 'system') continue;
	if ($row['rstatus'] == 'RESULT')
		$completeresourcedata[$row['name']] = $row;
	else if (!($row['rtype'] == 'INBOUND' && $row['rstatus'] == 'IDLE'))
		$activeresourcedata[] = $row;
}

$activeresourcetitles = array(	"name" => "ID",
							"starttime" => "Start Time",
							"rstatus" => "State",
							"rtype" => "Type"
						);

$completeresourcetitles = array( "name" => "ID",
							"starttime" => "Start Time",
							"result" => "Result"
						);
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE="admin:settings";
$TITLE="Flex Appliance: ".escapehtml($dmname);

include_once("nav.inc.php");
buttons(icon_button("Done","tick",null,"dms.php"));

if ($status == NULL) {
	echo "There is no status available at this time.";
} else {
?>
<table width="100%"><tr><td valign="top">
<?
startWindow("System Statistics");
include_once("dmsysstats.inc.php");
endWindow();
?>
</td><td valign="top">
<?
startWindow("Active Resources");
?>
<table>
<?

if (count($activeresourcedata) > 1) {
	showTable($activeresourcedata, $activeresourcetitles);
} else {
	echo "There are no active resources at this time.  The system is idle.<br>";
}

?>
</table>
<?
endWindow();

startWindow("Completed Resources");
?>
<table>
<?

if (count($completeresourcedata) > 1) {
	showTable($completeresourcedata, $completeresourcetitles);
}

?>
</table>
<?
endWindow();
?>
</td></tr></table>
<?
} // end else status is not null
buttons();
include_once("navbottom.inc.php");
?>