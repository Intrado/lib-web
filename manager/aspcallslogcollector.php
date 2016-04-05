<?
require_once("common.inc.php");
if (!$MANAGERUSER->authorized("logcollector")) {
	exit("Not Authorized");
}
if (is_null($aspdb = SetupASPDB())) {
	exit('aspcalls is not configured');
}

if (isset($_GET['run'])) {
	$query = "UPDATE logcollector SET status='runnow'";
	QuickUpdate($query, $aspdb);
	redirect();
}

$query = "SELECT status, lastrun FROM logcollector";
$data = QuickQueryMultiRow($query, false, $aspdb);

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
