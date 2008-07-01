<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/formatters.inc.php");
include_once("AspAdminUser.obj.php");


if(isset($_GET['resetDM']) || isset($_GET['update'])){
	if(isset($_GET['resetDM'])){
		$dmid = $_GET['resetDM'] + 0;
		$command = "reset";
	} else if(isset($_GET['update'])){
		$dmid = $_GET['update'] + 0;
		$command = "update";
	}
	$dmrow = QuickQueryRow("select name, command from dm where id = " . $dmid);
	if($dmrow[1] != ""){
?>
	<script>
		window.alert('That DM already has a command queued.  Please try again in a few moments');
		window.location="customerdms.php";
	</script>
<?
	}
	QuickUpdate("update dm set command = '" . $command ."' where id = " . $dmid);
?>
	<script>
		window.alert('<?=ucfirst($command)?> command initiated for DM: <?=$dmname?>');
		window.location="customerdms.php";
	</script>
<?
}

$queryextra = "";

if(isset($_GET['clear'])){
	unset($_SESSION['customerid']);
	redirect();
}
if(isset($_GET['cid'])){
	$_SESSION['customerid'] = $_GET['cid'] +0;
	redirect();
}
if(isset($_SESSION['customerid'])){
	$queryextra = " and c.id = " . $_SESSION['customerid'] . " ";
}

//index 2 is customer id
//index 1 is customer url
function fmt_customerUrl($row, $index){
	$url = "";
	if($row[2])
		$url = "<a href=\"customerlink.php?id=" . $row[1] ."\" >" . $row[2] . "</a>";
	return $url;
}

// index 0 is dmid
function fmt_DMActions($row, $index){
	$url = '<a href="editdm.php?dmid=' . $row[0] . '" title="Edit"><img src="img/s-edit.png" border=0></a>&nbsp;<a href="customerdms.php?resetDM=' . $row[0] . '" title="Reset"><img src="img/s-restart.png" border=0></a>&nbsp;<a href="customerdms.php?update=' . $row[0] . '" title="Update"><img src="img/s-update.png" border=0></a>&nbsp;<a href="dmupload.php?dmid=' . $row[0] . '" title="Upload DatFile"><img src="img/s-dat.png" border=0></a>';
	return $url;
}

function fmt_state($row, $index){
	return ucfirst($row[$index]);
}

function fmt_lastseen($row, $index){
	$output = fmt_ms_timestamp($row, $index);
	if($row[$index]/1000 > strtotime("now") - (5*60) && $row[$index]/1000 < strtotime("now")-10){
		$output = "<div style=\"background-color:yellow\">" . $output . "</div>";
	} else if($row[$index]/1000 < strtotime("now") - (5*60)){
		$output = "<div style=\"background-color:red\">" . $output . "</div>";
	}
	return $output;
}


$dms = array();
$query = "select dm.id, dm.customerid, c.urlcomponent, dm.name, dm.authorizedip, dm.lastip,
			dm.enablestate, dm.lastseen, dm.version
			from dm dm
			left join customer c on (c.id = dm.customerid)
			where dm.type = 'customer'
			" . $queryextra . "
			order by dm.customerid, dm.name";
$result = Query($query);
$data = array();
while($row = DBGetRow($result)){
	$data[] = $row;
}

$titles = array(0 => "DM ID",
				1 => "Customer ID",
				2 => "Customer Name",
				3 => "Name",
				4 => "Authorized IP",
				5 => "Last IP",
				7 => "Last Seen",
				6 => "State",
				8 => "Version",
				"actions" => "Actions");

$formatters = array(2 => "fmt_customerUrl",
					"actions" => "fmt_DMActions",
					7 => "fmt_lastseen",
					6 => "fmt_state");


include_once("nav.inc.php");
if(file_exists("dmbuild.txt")){
?>
	<div>Latest Version: <?=file_get_contents("dmbuild.txt");?></div>
<?
}
?>
<table class=list>
<?
	showTable($data, $titles, $formatters);
?>
</table>
<?
include_once("navbottom.inc.php");
?>