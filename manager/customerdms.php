<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/formatters.inc.php");
include_once("AspAdminUser.obj.php");

if(isset($_GET['authorizeDM'])){
	$dmid = $_GET['authorizeDM'] + 0;
	$cid = QuickQuery("select customerid from dm where id = " . $dmid);
	if($cid){
		$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id)
									where c.id = " . $cid);
		$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $cid);
		QuickUpdate("update custdm set enablestate = 'active'
					where dmid = " . $dmid, $custdb);
	}
	QuickUpdate("update dm set enablestate = 'active', authorizedip = lastip where id = " . $dmid);

	redirect();
}

if(isset($_GET['resetDM'])){
	$dmid = $_GET['resetDM'] + 0;
	$dmname = QuickQuery("select name from dm where id = " . $dmid);
	QuickUpdate("update dm set command = 'reset' where id = " . $dmid);
?>
	<script>
		window.alert('Reset command initiated for DM: <?=$dmname?>');
		window.location="customerdms.php";
	</script>
<?
}

$dms = array();
$query = "select dm.id, dm.customerid, c.urlcomponent, dm.name, dm.authorizedip, dm.lastip,
			dm.enablestate, dm.lastseen
			from dm dm
			left join customer c on (c.id = dm.customerid)
			where dm.type = 'customer'
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
				"actions" => "Actions");

$formatters = array(2 => "fmt_customerUrl",
					"actions" => "fmt_DMActions",
					7 => "fmt_ms_timestamp");


//index 2 is customer id
//index 1 is customer url
function fmt_customerUrl($row, $index){
	$url = "";
	if($row[2])
		$url = "<a href=\"customerlink.php?id=" . $row[1] ."\" >" . $row[2] . "</a>";
	return $url;
}

// index 1 is dmid
function fmt_DMActions($row, $index){
	$url = '<a href="editdm.php?dmid=' . $row[0] . '"/>Edit</a>&nbsp;|&nbsp;<a href="customerdms.php?authorizeDM=' . $row[0] . '">Authorize</a>&nbsp;|&nbsp;<a href="customerdms.php?resetDM=' . $row[0] . '">Reset</a>';
	return $url;
}

include_once("nav.inc.php");

?>
<table border="1">
<?
	showTable($data, $titles, $formatters);
?>
</table>
<?
include_once("navbottom.inc.php");
?>