<?
include_once("inc/common.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");


if (!$USER->authorize('managesystem') || getSystemSetting('_dmmethod', 'asp')=='asp') {
	redirect('unauthorized.php');
}

if(isset($_GET['resetdm'])){
	$resetid = $_GET['resetdm']+0;
	if(auth_resetDM($resetid)){
		$dmname = QuickQuery("select name from custdm where dmid = " . $resetid);
		QuickUpdate("update custdm set routechange = null where dmid = " . $resetid);
?>
<script>
		window.alert('Reset command initiated for Flex Appliance: <?=$dmname?>');
		window.location="dms.php";
</script>
<?
	} else {
		error("An error occured when trying to reset a Flex Appliance", "Please try again later");
	}
}

$result = Query("select dmid, name, routechange, telco_type from custdm where enablestate = 'active' order by dmid");
$data = array();
$resetrequired = array();
while($row = DBGetRow($result)){
	$data[] = $row;

}

// index 0 is dmid
// index 3 is telco type
function fmt_dm_actions($row, $index){
	$url = '<a href="dmstatus.php?dmid=' . $row[0] . '">Status</a>';
	$url .= '&nbsp;|&nbsp;<a href="dmschedule.php?dmid=' . $row[0] . '">Resource&nbsp;Schedule</a>';
	$url .= '&nbsp;|&nbsp;<a href="dmsettings.php?dmid=' . $row[0] . '">Route&nbsp;Plan</a>';
	$url .= '&nbsp;|&nbsp;<a href="dms.php?resetdm=' . $row[0] . '" onclick="return confirm(\'Are you sure you want to reset this Flex Appliance?\')">Reset</a>';
	return $url;
}

function fmt_dm_comment($row, $index){
	if($row[2]){
		return "Unsubmitted changes detected, requires reset";
	} else {
		return "";
	}
}

$titles = array(1 => "Name",
				2 => "Comment",
				"actions" => "Actions");

$formatters = array("actions" => "fmt_dm_actions",
					2 => "fmt_dm_comment");

$PAGE="admin:settings";
$TITLE="Flex Appliance Manager";
include_once("nav.inc.php");
buttons(button("Back", null, "settings.php"));
startWindow("Authorized Appliances" . help('Settings_DMs'));
?>
<table border="0" width="100%" cellpadding="3" cellspacing="1" class="list" >
<?
	showTable($data, $titles, $formatters);
?>
</table>
<?
endWindow();
buttons();
include_once("navbottom.inc.php");
?>