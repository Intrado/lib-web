<?
require_once("common.inc.php");
if(!$MANAGERUSER->authorized("aspcallgraphs"))
	exit("Not Authorized");
?>
<html>
<body>

<?
$offset = $_GET['daysAgo'];

$x = ($offset > 0 ? $offset : 1);
while ( time() - (($x) * 60*60*24*7) > strtotime("30 days ago")) {
	$sd = date("Y-m-d",time() - (($x) * 60*60*24*7));
	$ed = date("Y-m-d",time() - (($x-1) * 60*60*24*7));

	echo "<img src='aspcallsbydm.php?startdate=$sd&enddate=$ed'><br>";
	$x = $x + 7;
}

?>

</body>
</html>
