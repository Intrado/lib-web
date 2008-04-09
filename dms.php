<?
include_once("inc/common.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");


if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

if(isset($_GET['resetdm'])){
	$resetid = $_GET['resetdm']+0;
	if(auth_resetDM($resetid)){
		redirect();
	} else {
		error("Something happened when trying to reset a DM", "Please try again later");
	}
}

$result = Query("select dmid, name from custdm where enablestate = 'active' order by dmid");
$data = array();
while($row = DBGetRow($result)){
	$data[] = $row;
}


// index 0 is dmid
function fmt_editDMRoute($row, $index){
	$url = '<a href="dmsettings.php?dmid=' . $row[0] . '">Edit Route Plan</a>&nbsp;|&nbsp;<a href="dms.php?resetdm=' . $row[0] . '">Reset</a>';
	return $url;
}

$titles = array(1 => "Name",
				"actions" => "Actions");

$formatters = array("actions" => "fmt_editDMRoute");

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