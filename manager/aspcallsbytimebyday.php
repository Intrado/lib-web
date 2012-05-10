<?
    require_once("common.inc.php");  
    if(!$MANAGERUSER->authorized("aspcallgraphs"))
    		exit("Not Authorized");
?>
<html>
<body onload="seturl();">


<form>
<a href="#" onclick="prevurl();"> &lt;--</a>
<select id="datesel" onchange="seturl();">
<?
	$x = 0;
	while ( time() - (($x) * 60*60*24) > strtotime("2006-09-12")) {
		$sd = date("Y-m-d",time() - (($x) * 60*60*24));
		$ed = date("Y-m-d",time() - (($x-1) * 60*60*24));
		
		echo "<option value='aspcallsbytime.php?startdate=$sd&enddate=$ed'>$sd</option>";
		$x++;
	}

?>
</select>
<a href="#" onclick="nexturl();"> --&gt;</a>
</form>

<script>

function seturl () {
	var datesel = document.getElementById('datesel');
	var img = document.getElementById("graph");
	img.src = datesel.value;
}

function nexturl () {
	var datesel = document.getElementById('datesel');
	datesel.selectedIndex = datesel.selectedIndex +1 >= datesel.length ? 0 : datesel.selectedIndex +1;
	seturl();
}

function prevurl () {
	var datesel = document.getElementById('datesel');
	datesel.selectedIndex = datesel.selectedIndex <= 0 ? datesel.length -1 : datesel.selectedIndex -1;
	seturl();
}

</script>

<br>
<img id="graph" >

</body>
</html>
