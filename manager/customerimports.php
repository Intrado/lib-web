<?
include_once("common.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/form.inc.php");

function fmt_alert_timestamp($timestamp) {
	if ($timestamp === false) {
		return "<div style='background-color: #ffcccc'>- Never -</div>";
	} else {
		if ($timestamp < time() - 60 * 60 * 24 * 3)
			return "<div style='background-color: #ffcccc'>" . date("M j, g:i a", $timestamp) . "</div>";
		else
			return date("M j, g:i a", $timestamp);
	}
}

if(isset($_GET['customer'])){
	$customerID = $_GET['customer']+0;
	$queryextra = "AND ID='$customerID'";
} else {
	$queryextra="";
}


$f = "form";
$s = "imports";
$reloadform = 0;
$types = array("automatic" => "automatic", "manual" => "manual", "list" => "list", "addressbook" => "addressbook");
$selected = array("automatic", "manual");
$querytypes = "";


if(CheckFormSubmit($f, $s)) {
	//check to see if formdata is valid
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = true;
	} else {
		MergeSectionFormData($f, $s);
		if( CheckFormSection($f, $s) ){
			?><div>An error occurred somehow </div><?
		} else {
			$selected = GetFormData($f, $s, 'importtypes');
		}
	}
} else {
	$reloadform = 1;
}
if($reloadform){
	ClearFormData($f);
	PutFormData($f, $s, 'importtypes', $selected, "array", array_keys($selected));
}

if($selected){
	$count = 0;
	$many = "";
	foreach($selected as $select){
		if($count > 0)
			$many = "OR";
		$querytypes = $querytypes . " $many import.type = '" . DBSafe($select) ."'";
		$count++;
	}
	$querytypes = "and (" . $querytypes . ")";
}

include("nav.inc.php");
NewForm($f);
?>
<table>
	<tr><td>
		<?
			NewFormItem($f, $s, 'importtypes', 'selectmultiple', "4", $types);
		?>
	</td></tr>
	<tr><td><? NewFormItem($f, $s, 'submit', 'submit'); ?></td><tr>
</table>
<?
EndForm();


$custquery = Query("select id, dbhost, dbusername, dbpassword, hostname from customer where 1 $queryextra");
$customers = array();
while($row= mysql_fetch_row($custquery)){
	$customers[] = $row;
}

?>

<table border = 1>
	<tr>
		<td>Customer ID</td>
		<td>Customer Name</td>
		<td>Customer URL</td>
		<td>Import ID </td>
		<td>Import Name</td>
		<td>Status</td>
		<td>Type </td>
		<td>Upd. Method</td>
		<td>TimeZone</td>
		<td>Last Run</td>
		<td>File Date</td>
		<td>File Size in Bytes</td>
	</tr>
<?
foreach($customers as $cust){

	$custdb = DBConnect($cust[1], $cust[2], $cust[3], "c_$cust[0]");
	if(!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
	}

	$query = "SELECT id, name, status, type, updatemethod, lastrun, datamodifiedtime, length(data)
				FROM import
				where 1
				$querytypes
				order by id";
	$list = Query($query, $custdb);
	$timezone = getCustomerSystemSetting('timezone', false, true, $custdb);
	$displayname = getCustomerSystemSetting('displayname', false, true, $custdb);
	while($row = DBGetRow($list)){
		date_default_timezone_set($timezone);

	?>
		<tr>
			<td><?=$cust[0]?></td>
			<td><?=$displayname?></td>
			<td><a href="customerlink.php?id=<?=$cust[0]?>" target="_blank"><?=$cust[4]?></a></td>
			<td><?=$row[0]?></td>
			<td><?=$row[1]?></td>
			<td <?= $row[2] == "error" ? 'style="background-color: red;"' : "" ?>><?=$row[2]?></td>
			<td><?=$row[3]?></td>
			<td><?=$row[4]?></td>
			<td><?=$timezone?></td>
			<td><?=fmt_alert_timestamp(strtotime($row[5]))?></td>
			<td><?=$row[6]?></td>
			<td <?= $row[7] < 10 ? 'style="background-color: #ffcccc;"' : "" ?>><?= number_format($row[7])?></td>
		</tr>
	<?
	}
}
date_default_timezone_set("US/Pacific");
?>
</table>
<div> Automatic jobs have the "Import when uploaded" checkbox checked, manual jobs do not.  Both are from imports page.<div>
<div > All time stamps are in customer time. </div>
<div>Red cells indicate import or file dates that are more than 3 days old</div>

<?
include("navbottom.inc.php");
?>