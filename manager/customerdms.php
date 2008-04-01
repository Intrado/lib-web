<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("AspAdminUser.obj.php");

$dms = array();
$query = "select customerid, dmuuid, name, authorizedip, lastip, enablestate, lastseen from dm order by customerid, name";
$result = Query($query);
while($row = DBGetRow($result)){
	$dm = array();
	$dm['customerid'] = $row[0];
	$dm['dmuuid'] = $row[1];
	$dm['name'] = $row[2];
	$dm['authorizedip'] = $row[3];
	$dm['lastip'] = $row[4];
	$dm['enablestate'] = $row[5];
	$dm['lastseen'] = $row[6];
	$dms[$row[1]] = $dm;
}

include_once("nav.inc.php");

?>
<table border="1">
	<th>Customer ID</th>
	<th>DM ID</th>
	<th>Name</th>
	<th>Authorized IP</th>
	<th>Last IP</th>
	<th>Last Seen</th>
	<th>State</th>
	<th>Actions</th>

<?
	foreach($dms as $dm){
?>
		<tr>
			<td><?=$dm['customerid']?></td>
			<td><?=$dm['dmuuid']?></td>
			<td><?=$dm['name']?></td>
			<td><?=$dm['authorizedip']?></td>
			<td><?=$dm['lastip']?></td>
			<td><?=date('M d, Y h:i:s', $dm['lastseen']/1000)?></td>
			<td><?=$dm['enablestate']?></td>
			<td><a href="editdm.php?dmid=<?=$dm['dmuuid']?>"/>Edit</a></td>
		</tr>
<?
	}
?>
</table>
<?
include_once("navbottom.inc.php");
?>