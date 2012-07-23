<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/memcache.inc.php");


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

list($dmname,$dmuuid) = QuickQueryRow("select name, dmuuid from custdm where dmid=?",false,false,array($dmid));

init_memcache();
global $mcache;
$jsonstats = $mcache->get("dmpoststatus/".$dmuuid);

include_once("dmstatusdata.inc.php");



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE="admin:settings";
$TITLE="SmartCall Appliance: ".escapehtml($dmname);

include_once("nav.inc.php");
buttons(icon_button("Back","fugue/arrow_180",null,"dms.php"));

?>
<script type='text/javascript' src='script/dmstatus.js'></script>
<?

if ($status == NULL) {
	echo "There is no status available at this time.";
} else {
?>
<table width="100%"><tr><td valign="top">
<?
startWindow("System Statistics");
echo "<div id='systemstats'>";
	foreach($statusgroups as $groupname => $groupdata) {
		showStatusGroup($groupname, $groupdata);
	}
echo "</div>";
endWindow();
?>
</td><td valign="top">
<?
startWindow("Active Resources");
echo "<div id='activeresources'></div>";
endWindow();

startWindow("Completed Resources");
echo "<div id='completedresources'></div>";
endWindow();
?>
</td></tr></table>
<?
} // end else status is not null
buttons();
include_once("navbottom.inc.php");
?>
