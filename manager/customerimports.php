<?
include_once("common.inc.php");
include_once("../inc/ftpfile.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/form.inc.php");



function fmt_alert_timestamp($timestamp) {
	if ($timestamp === false) {
		return "<div style='background-color: #ffcccc'>- Never -</div>";
	} else {
		if ($timestamp < time() - 60 * 60 * 24 * 3)
			return "<div style='background-color: #ffcccc'>" . date("M j, g:i a", $timestamp) . "</div>";
		else
			return date("M j, g:i a", $lastrun);
	}
}




$f = "form";
$s = "imports";
$reloadform = 0;
$types = array("automatic" => "automatic", "manual" => "manual", "list" => "list", "addressbook" => "addressbook");
$selected = array("automatic", "manual");
if(isset($_GET['customer'])){
	$customerID = $_GET['customer']+0;
	$queryextra = "AND customerID='$customerID'";
} else {
	$queryextra="";
}
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
		$querytypes = $querytypes . " $many import.type = '$select'";
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

$query = "SELECT  import.customerID, customer.name, customer.hostname, import.id,
			import.name, import.status, import.type, import.updatemethod, import.lastrun, customer.timezone
			FROM import, customer
			WHERE import.customerID=customer.id
			$queryextra
			$querytypes
			order by import.customerID";
$list = Query($query);

while($row = DBGetRow($list)){
	date_default_timezone_set($row[9]);

	$importfile = getImportFileURL($row[0],$row[3]);
	if (is_readable($importfile) && is_file($importfile)) {
		$filetime = filemtime($importfile);
		$row[10]=fmt_alert_timestamp($filetime);
		$row[11]=filesize($importfile);
	} else {
		$row[10]="<div style='background-color: #ffcccc'>Not Found</div>";
		$row[11]="-";
	}
?>
	<tr>
		<td><?=$row[0]?></td>
		<td><?=$row[1]?></td>
		<td><a href="https://asp.schoolmessenger.com/<?=$row[2]?>" target="_blank"><?=$row[2]?></a></td>
		<td><?=$row[3]?></td>
		<td><?=$row[4]?></td>
		<td <?= $row[5] == "error" ? 'style="background-color: red;"' : "" ?>><?=$row[5]?></td>
		<td><?=$row[6]?></td>
		<td><?=$row[7]?></td>
		<td><?=$row[9]?></td>
		<td><?=fmt_alert_timestamp(strtotime($row[8]))?></td>
		<td><?=$row[10]?></td>
		<td <?= $row[11] < 10 ? 'style="background-color: #ffcccc;"' : "" ?>><?=$row[11]?></td>
	</tr>
<?
}
date_default_timezone_set("US/Pacific");
?>
</table>
<br> Automatic jobs have the "Import when uploaded" checkbox checked, manual jobs do not.  Both are from imports page.
<div style="color:FF0000"> All time stamps are in customer time. </div>

<?
include("navbottom.inc.php");
?>