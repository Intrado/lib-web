<?
include_once("common.inc.php");
include_once("../inc/ftpfile.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/form.inc.php");

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
		<td>Last Run</td>
		<td>File Date</td>
		<td>File Size in Bytes</td>
	</tr>
<?

$query = "SELECT  import.customerID, customer.name, customer.hostname, import.id, 
			import.name, import.status, import.type, import.lastrun
			FROM import, customer
			WHERE import.customerID=customer.id
			$queryextra
			$querytypes
			order by import.customerID";
$list = Query($query);

while($row = DBGetRow($list)){
	$importfile = getImportFileURL($row[0],$row[3]);

	if (is_readable($importfile) && is_file($importfile)) {
		$row[8]=date("M j, g:i a",filemtime($importfile));
		$row[9]=filesize($importfile);
	} else {
		$row[8]="Not Found";
		$row[9]="-";
	}
	$row[7] = fmt_date($row, 7);
?>
	<tr>
		<td><?=$row[0]?></td>
		<td><?=$row[1]?></td>
		<td><a href="https://asp.schoolmessenger.com/<?=$row[2]?>"><?=$row[2]?></a></td>
		<td><?=$row[3]?></td>
		<td><?=$row[4]?></td>
		<td><?=$row[5]?></td>
		<td><?=$row[6]?></td>
		<td><?=$row[7]?></td>
		<td><?=$row[8]?></td>
		<td><?=$row[9]?></td>
	</tr>
<?
}
?>
</table>
<br> Automatic jobs have the "Import when uploaded" checkbox checked, manual jobs do not.  Both are from imports page.

<?
include("navbottom.inc.php");
?>