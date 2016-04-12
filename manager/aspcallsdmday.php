<?
require_once("common.inc.php");
if (!$MANAGERUSER->authorized("aspcallgraphs")) {
	exit("Not Authorized");
}
$aspdb = SetupASPDB();
if (is_null($aspdb)) {
	exit('aspcalls is not configured');
}

$time = false;
if (isset($_GET['date'])) {
	$time = strtotime($_GET['date']);
}
if ($time == 0 || $time == false) {
	$time = time();
}

$date = date("Y-m-d", $time);
$startdate = "$date 00:00:00";
$enddate = "$date 23:59:59";
$table = $SETTINGS['aspcalls']['callstable'];
if (!preg_match('/\w+/', $table)) {
	exit("Invalid table name in aspcalls settings");
}

$query = "select distinct dmid from `$table` where startdate between ? and ?";
$activedms = QuickQueryList($query, false, $aspdb, array($startdate, $enddate));

$dms = array();
if (count($activedms)) {
	$query = "SELECT id,dm,carrier FROM dms WHERE id IN (" . implode(",", $activedms) . ") ORDER BY dm";

	$dms = QuickQueryList($query, true, $aspdb);
}

$tomorrow = date("Y-m-d",$time + 60*60*24);
$yesterday = date("Y-m-d",$time - 60*60*24);
?>
<html>

<a href="?date=<?=$yesterday?>"> &lt;--</a>
<?=$date?>
<a href="?date=<?=$tomorrow?>"> --&gt;</a>

<br>

<?

foreach ($dms as $dmid => $dmdata) {
	echo "<h1>" . $dmdata[1] ." - " . $dmdata[2] . "</h1>";
	echo '<img src="aspcallsbytime.php?startdate='.$date.'&enddate='.$tomorrow.'&dm='.$dmdata[1].'" />';	
}




?>



</body>
</html>
