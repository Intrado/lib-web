<?
require_once("common.inc.php");
if(!$MANAGERUSER->authorized("logcollector"))
	exit("Not Authorized");
$conn = SetupASPDB();

if (isset($_GET['run'])) {
	
	mysql_query("update logcollector set status='runnow'", $conn);
	redirect();
}


$data = QueryAll("select status,lastrun from logcollector", $conn);

?>
<html>
<body>

<a href="?run">Run now</a><br>

<?

foreach ($data as $row) { //should only be 1!

	echo "Status:" . $row[0] . "<br>";
	echo "Last Run:" . $row[1] . "<br>";
	
}
?>

</body>
</html>
