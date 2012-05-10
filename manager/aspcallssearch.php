<?
require_once("common.inc.php");
if(!$MANAGERUSER->authorized("aspcallgraphs"))
	exit("Not Authorized");
?>
<form method="GET" >


Phone:<br><input name="phone" type="text" ><br>

Date:<br><select name="date" >
<?
	$x = 0;
	while ( time() - (($x) * 60*60*24) > strtotime("2006-09-12")) {
		$sd = date("Y-m-d",time() - (($x) * 60*60*24));
		echo "<option value='$sd'>$sd</option>";
		$x++;
	}

?>
</select><br>

<input type="submit" value="Search">

</form>
<?

$phone = preg_replace("/[^0-9]*/","",$_GET['phone']);

if (strlen($phone) == 10) {
	
	$sd = date("Y-m-d", strtotime($_GET['date']));
	$ed = date("Y-m-d", strtotime($_GET['date']) + 60*60*24); //next day
        $table = $SETTINGS[aspcalls][callstable];
	$query = "select startdate, ringtime, detecttime, messagetime, billtime, result, dmstart, dmend, userhungup, callerid, 
			d.dm, d.carrier, d.state 
			from $table
			inner join dms d on 
				(d.id=dmid) 
			where startdate between '$sd' and '$ed' 
			and phone = '$phone'
	";
	$conn = SetupASPDB();
	$data = QueryAll($query, $conn);
	
?>
	<style>
	table {
		border-collapse: collapse;
	}
	table th {
		border: 1px solid black;
	}
	</style>
	<table cellspacing=0 cellpadding=3 border=1>
	<tr style="background: #CCCCCC;">
		<th>Start Date</th>
		<th>Ring Time</th>
		<th>Detect Time</th>
		<th>Message Time</th>
		<th>Bill Time</th>
		<th>Result</th>
		<th>DM Start</th>
		<th>DM End</th>
		<th>User Hung Up</th>
		<th>Caller ID</th>
		<th>DM</th>
		<th>Carrier</th>
		<th>State</th>
	</tr>
<?
	$count = 0;
	foreach ($data as $row) {
		
		if (++$count % 2 == 0)
			echo '<tr style="background: #DDDDDD;">';
		else
			echo "<tr>";
		foreach ($row as $col) {
			echo "<td>" . htmlentities($col) . "</td>";
		}
		echo "</tr>\n";
	}
?>
	</table>
<?
}
