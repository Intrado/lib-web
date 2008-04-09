<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/formatters.inc.php");
include_once("AspAdminUser.obj.php");

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
					"actions" => "fmt_editLink",
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
function fmt_editLink($row, $index){
	$url = '<a href="editdm.php?dmid=' . $row[0] . '"/>Edit</a>';
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