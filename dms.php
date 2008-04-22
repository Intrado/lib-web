<?
include_once("inc/common.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");


if (!$USER->authorize('managesystem') || !getSystemSetting('_hasremotedm', false)) {
	redirect('unauthorized.php');
}

if(isset($_GET['resetdm'])){
	$resetid = $_GET['resetdm']+0;
	if(auth_resetDM($resetid)){
		$dmname = QuickQuery("select name from custdm where dmid = " . $resetid);
		QuickUpdate("update custdm set routechange = null where dmid = " . $resetid);
?>
<script>
		window.alert('Reset command initiated for DM: <?=$dmname?>');
		window.location="dms.php";
</script>
<?
	} else {
		error("Something happened when trying to reset a DM", "Please try again later");
	}
}

$result = Query("select dmid, name, routechange from custdm where enablestate = 'active' order by dmid");
$data = array();
$resetrequired = array();
while($row = DBGetRow($result)){
	$data[] = $row;
	if($row[2]){
		$resetrequired[] = $row[1];
	}
}

if(count($resetrequired)){
	error("The following DM's have had their route plans changed without being reset", $resetrequired);
}

// index 0 is dmid
function fmt_editDMRoute($row, $index){
	$url = '<a href="dmsettings.php?dmid=' . $row[0] . '">Edit Route Plan</a>&nbsp;|&nbsp;<a href="dms.php?resetdm=' . $row[0] . '" onclick="return confirm(\'Are you sure you want to reset this DM\')">Reset</a>';
	return $url;
}

$titles = array(1 => "Name",
				"actions" => "Actions");

$formatters = array("actions" => "fmt_editDMRoute");

$PAGE="admin:settings";
$TITLE="DM's";
include_once("nav.inc.php");
buttons(button("Back", null, "settings.php"));
startWindow("DM's");
?>
<table border="1" width="100%" cellpadding="3" cellspacing="1" class="list" >
<?
	showTable($data, $titles, $formatters);
?>
</table>
<?
endWindow();
buttons();
include_once("navbottom.inc.php");
?>