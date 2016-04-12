<?
require_once("common.inc.php");
if (!$MANAGERUSER->authorized("logcollector")) {
	exit("Not Authorized");
}
$aspdb = SetupASPDB();
if (is_null($aspdb)) {
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

<h1><a href="?run" title="Run now"><img src="img/largeicons/checkedgreen.jpg" alt="Run now">Run now</a></h1>

<ul>
<?

foreach ($data as $row) { //should only be 1!

	echo "<li>Status: " . $row[0] . "</li>";
	echo "<li>Last Run: " . $row[1] . "</li>";
	
}
?>
</ul>

</body>
</html>
