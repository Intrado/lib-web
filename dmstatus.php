<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");


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

$dmname = QuickQuery("select name from custdm where dmid=?",false,false,array($dmid));


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE="admin:settings";
$TITLE="SmartCall Appliance: ".escapehtml($dmname);

include_once("nav.inc.php");
buttons(icon_button("Back","fugue/arrow_180",null,"dms.php"));

?>
<script type="text/javascript">
	var dmid = <?=$dmid?>;
</script>
<script type='text/javascript' src='script/dmstatus.js'></script>

<table width="100%"><tr><td valign="top">
<?
startWindow("System Statistics");
include("dmstatusdata.inc.php");
endWindow();
?>
</td><td valign="top">
<?
startWindow("Active Resources");
?>
	<div id='activeresources'></div>
<?
endWindow();

startWindow("Completed Resources");
?>
	<div id='completedresources'></div>
<?
endWindow();
?>
</td></tr></table>
<?
buttons();
include_once("navbottom.inc.php");
?>
