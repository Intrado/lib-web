<?
include_once("inc/common.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");


if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

$result = Query("select dmid, name from custdm where enablestate = 'active' order by dmid");
$data = array();
while($row = DBGetRow($result)){
	$data[] = $row;
}


// index 0 is dmid
function editDMRoute($row, $index){
	$url = '<a href="dmsettings.php?dmid=' . $row[0] . '">Edit Route Plan</a>';
	return $url;
}

$titles = array(1 => "Name",
				"actions" => "Actions");

$formatters = array("actions" => "editDMRoute");

$PAGE="admin:settings";
$TITLE="DM's";
include_once("nav.inc.php");
startWindow("DM's");
?>
<table border="1" width="100%" cellpadding="3" cellspacing="1" class="list" >
<?
	showTable($data, $titles, $formatters);
?>
</table>
<?
endWindow();
include_once("navbottom.inc.php");
?>