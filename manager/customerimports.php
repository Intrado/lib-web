<?
$isasplogin=1;
include_once("common.inc.php");
include_once("../inc/ftpfile.inc.php");
include_once("../inc/formatters.inc.php");

$customerID = $_GET["customer"]+0;

if(isset($_GET["customer"])){
	$queryextra = "AND customerID='$customerID'";
} else {
	$queryextra="";
}
$query = "SELECT  import.customerID, import.id, customer.name,
			import.name, import.status, import.lastrun
			FROM import, customer
			WHERE import.customerID=customer.id
			$queryextra";

$list = Query($query);

include("nav.inc.php");
?>
<table border = 1>
	<tr>
		<td>Customer ID</td>
		<td>Customer Name</td>
		<td>Import Name</td>
		<td>Status</td>
		<td>Last Run</td>
		<td>File Date</td>
	</tr>
<?
while($row = DBGetRow($list)){
	$importfile = getImportFileURL($row[0],$row[1]);

	if (is_readable($importfile) && is_file($importfile)) {
		$row[6]=date("M j, g:i a",filemtime($importfile));
	} else {
		$row[6]="Not Found";
	}
	$row[5] = fmt_date($row, 5);
?>
	<tr>
		<td><?=$row[0]?></td>
		<td><?=$row[2]?></td>
		<td><?=$row[3]?></td>
		<td><?=$row[4]?></td>
		<td><?=$row[5]?></td>
		<td><?=$row[6]?></td>
	</tr>
<?
}
?>
</table>

<?
include("navbottom.inc.php");
?>