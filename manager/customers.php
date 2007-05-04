<?
include_once("common.inc.php");
include_once("../obj/Customer.obj.php");

$customerquery = Query("select id, dbhost, dbusername, dbpassword, hostname from customer order by id");
$customers = array();
while($row = mysql_fetch_row($customerquery)){
	$customers[] = $row;
}


include_once("nav.inc.php");
?>

<table border=1>
<tr>
<td>Customer ID</td>
<td>Customer Name</td>
<td>Customer URL</td>
<td>Toll Free Number</td>
<td>Timezone</td>
<td>Active Users</td>
<td>Active Jobs</td>
</tr>

<?
//Finds the necessary fields for each customer account
foreach($customers as $cust) {

	if($custdb = DBConnect($cust[1], $cust[2], $cust[3], "c_" . $cust[0])){
	
		$custname = QuickQuery("select value from setting where name = 'displayname'", $custdb);
		$hostname = $cust[4];
		$inboundnumber = QuickQuery("select value from setting where name = 'inboundnumber'", $custdb);
		$timezone = QuickQuery("select value from setting where name = 'timezone'", $custdb);
		$usercount = QuickQuery("SELECT COUNT(*) FROM user where enabled = '1'", $custdb);
		$jobcount = QuickQuery("SELECT COUNT(*) FROM job INNER JOIN user ON(job.userid = user.id)
								WHERE job.status = 'active'", $custdb);
	?>
		<td><?= $cust[0] ?></td>
		<td><?= $custname ?></td>
		<td><a href="customerlink.php?id=<?=$cust[0] ?>"><?=$hostname?></a></td>
		<td><?= $inboundnumber ?></td>
		<td><?= $timezone ?></td>
		<td <?= $usercount == 1 ? 'style="background-color: #ffcccc;"' : "" ?>><?= $usercount ?></td>
		<td <?= $jobcount > 0 ? 'style="background-color: #ccffcc;"' : "" ?>><?= $jobcount ?></td>
		<td><a href="customeredit.php?id=<?=$cust[0] ?>">Edit</a>&nbsp;|&nbsp;<a href="userlist.php?customer=<?= $cust[0] ?>">Show&nbsp;Users</a>&nbsp;|&nbsp;<a href="customerimports.php?customer=<?=$cust[0]?>">Customer&nbsp;Imports</a>&nbsp;|&nbsp;<a href="customeractivejobs.php?customer=<?=$cust[0]?>">Active&nbsp;Jobs</a></td>
		</tr>
	
	<?
	}
}
?>
</table>

<div>Red cells indicate that only the system user account has been created</div>
<div>Green cells indicate customers with active jobs</div>
<?
include_once("navbottom.inc.php");
?>