<?
require_once("common.inc.php");

if(!$MANAGERUSER->authorized("aspcallgraphs"))
	exit("Not Authorized");

$time = strtotime($_GET['date']);
if ($time == 0 || $time == false)
	$time = time();

$date = date("Y-m-d",$time);

$table = $SETTINGS['aspcalls']['callstable']; 
$query = "select distinct dmid from $table where startdate between '$date 00:00:00' and '$date 23:59:59'";
$link = SetupASPDB();

$res = mysql_query($query, $link);

$res = mysql_query($query) or die(mysql_error());
$activedms = array();
while ($row = mysql_fetch_row($res)) {
	$activedms[] = $row[0];
}

if (count($activedms)) {
	$query = "select id,dm,carrier from dms where id in (" . implode(",",$activedms) . ") order by dm";
	
	$dms = array();
	$res = mysql_query($query) or die(mysql_error());
	while ($row = mysql_fetch_row($res)) {
		$dms[$row[0]] = $row;
	}
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
