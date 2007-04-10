<?
include_once("common.inc.php");
include_once("../obj/Customer.obj.php");

$customers = DBFindMany("Customer", "FROM customer");

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

	$custfields = QuickQueryRow("SELECT hostname, inboundnumber, timezone FROM customer WHERE customer.id = $cust->id");
	$usercount = QuickQuery("SELECT COUNT(*) FROM user WHERE user.customerid = $cust->id
	                         AND user.enabled = '1'");
	$jobcount = QuickQuery("SELECT COUNT(*) FROM job INNER JOIN user ON(job.userid = user.id)
							WHERE user.customerid = $cust->id
							AND job.status = 'active'");
?>
	<tr><td><?= $cust->id ?></td>
	<td><?= $cust->name ?></td>
	<td><a href="https://asp.schoolmessenger.com/<?=$custfields[0]?>" target="_blank"><?=$custfields[0]?></a></td>
	<td><?= $custfields[1] ?></td>
	<td><?= $custfields[2] ?></td>
	<td <?= $usercount == 1 ? 'style="background-color: #ffcccc;"' : "" ?>><?= $usercount ?></td>
	<td <?= $jobcount > 0 ? 'style="background-color: #ccffcc;"' : "" ?>><?= $jobcount ?></td>
	<td><a href="customeredit.php?id=<?=$cust->id ?>">Edit</a>&nbsp;|&nbsp;<a href="userlist.php?customer=<?= $cust->id ?>">Show&nbsp;Users</a>&nbsp;|&nbsp;<a href="customerimports.php?customer=<?=$cust->id?>">Customer&nbsp;Imports</a>&nbsp;|&nbsp;<a href="customeractivejobs.php?customer=<?=$cust->id?>">Active&nbsp;Jobs</a></td>
	</tr>

<?
}
?>
</table>

<div>Red cells indicate that only the system user account has been created</div>
<div>Green cells indicate customers with active jobs</div>
<?
include_once("navbottom.inc.php");
?>