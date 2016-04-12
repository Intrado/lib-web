<?
require_once("common.inc.php");  
if (! $MANAGERUSER->authorized("aspcallgraphs")) {
	exit("Not Authorized");
}
if (! isset($SETTINGS['aspcalls'])) {
	exit('aspcalls is not configured');
}
?>
<html>
<body onload="seturl();">


<form>
<a href="#" onclick="return prevurl();"> &lt;--</a>
<select id="datesel" onchange="seturl();">
<?
	$x = 0;
	while ( time() - (($x) * 60*60*24) > strtotime("2006-09-12")) {
		$sd = date("Y-m-d",time() - (($x) * 60*60*24));
		$ed = date("Y-m-d",time() - (($x-1) * 60*60*24));
		
		echo "<option value='aspcallsbytime.php?startdate=$sd&enddate=$ed";
		
		if (isset($_GET['dm']) && $_GET['dm']) {
			echo "&dm=" . urlencode($_GET['dm']);
		}
		
		echo "'>$sd</option>";
		$x++;
	}

?>
</select>
<a href="#" onclick="return nexturl();"> --&gt;</a>
</form>

<script>

function seturl () {
	var datesel = document.getElementById('datesel');
	var graph = document.getElementById("graph");
	graph.innerHTML = '<img src="' + datesel.value  + '" alt="Loading..."/>';
}

function nexturl () {
	var datesel = document.getElementById('datesel');
	datesel.selectedIndex = datesel.selectedIndex +1 >= datesel.length ? 0 : datesel.selectedIndex +1;
	seturl();
	return false;
}

function prevurl () {
	var datesel = document.getElementById('datesel');
	datesel.selectedIndex = datesel.selectedIndex <= 0 ? datesel.length -1 : datesel.selectedIndex -1;
	seturl();
	return false;
}

</script>

<br>
<div id="graph" />

</body>
</html>
