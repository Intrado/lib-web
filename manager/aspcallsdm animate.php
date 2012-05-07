<?
    require_once("common.inc.php");  

	if(!$MANAGERUSER->authorized("aspcallgraphs"))
		exit("Not Authorized");
?>
<html>
<body onload="setTimeout(timeout,500);">


<script>

var index = 0;
var urls = [
<?
	$lasttime = strtotime("2008-08-17");
	for ($x = 10; $x >= 0; $x--) {
		$sd = date("Y-m-d",$lasttime - (($x+1) * 60*60*24));
		$ed = date("Y-m-d",$lasttime - (($x) * 60*60*24));
		echo "\"aspcallsbydm.php?startdate=$sd&enddate=$ed\",\n";
	}


?>
];


function timeout () {
	var img = document.getElementById("graph");
	
	img.src = urls[index++];
	
	if (index >= urls.length)
		index = 0;
	
	setTimeout(timeout,500);
}


</script>


<img id="graph">

</body>
</html>
