<?
    require_once("common.inc.php");
	if(!$MANAGERUSER->authorized("aspcallgraphs"))
		exit("Not Authorized");
?>
<html>
<body>

<?
	$x = 0;
	while ( time() - (($x) * 60*60*24) > strtotime("30 days ago")) {
		$sd = date("Y-m-d",time() - (($x) * 60*60*24));
		$ed = date("Y-m-d",time() - (($x-1) * 60*60*24));
		
		echo "<img src='aspcallsbydm.php?startdate=$sd&enddate=$ed'><br>";
		$x++;
	}

?>

</body>
</html>