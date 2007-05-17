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
<td>Customer URL (link)</td>
<td>Toll Free Number</td>
<td>Timezone</td>
<td>Status</td>
<td>Active Users</td>
<td>Active Jobs</td>
<td>Actions</td>
<td>NOTES:&nbsp;</td>
</tr>

<?
//Finds the necessary fields for each customer account
foreach($customers as $cust) {

	if($custdb = DBConnect($cust[1], $cust[2], $cust[3], "c_" . $cust[0])){

		$custname = QuickQuery("select value from setting where name = 'displayname'", $custdb);
		$hostname = $cust[4];
		$inboundnumber = QuickQuery("select value from setting where name = 'inboundnumber'", $custdb);
		$timezone = QuickQuery("select value from setting where name = 'timezone'", $custdb);
		$maxusers = QuickQuery("select value from setting where name = '_maxusers'", $custdb);
		$notes = QuickQuery("select value from setting where name = '_managernote'", $custdb);
		$status = QuickQuery("select value from setting where name = 'disablerepeat'", $custdb);

		$maxusers = $maxusers ? $maxusers : 1;
		$usercount = QuickQuery("SELECT COUNT(*) FROM user where enabled = '1' and login != 'schoolmessenger'", $custdb);
		$jobcount = QuickQuery("SELECT COUNT(*) FROM job INNER JOIN user ON(job.userid = user.id)
								WHERE job.status = 'active'", $custdb);
		$userstyle = "";
		if($usercount > $maxusers){
			$userstyle = 'style="background-color: #ff0000;"';
		} else if($usercount == 0){
			$userstyle = 'style="background-color: #ffcccc;"';
		}
	?>
		<td><?= $cust[0] ?></td>
		<td><?= $custname ?></td>
		<td><a href="customerlink.php?id=<?=$cust[0] ?>"><?=$hostname?></a></td>
		<td><?= $inboundnumber ? $inboundnumber : "&nbsp;" ?></td>
		<td><?= $timezone ?></td>
		<td><?= $status ? "Repeating Jobs Disabled" : "OK" ?></td>
		<td <?=$userstyle?>><?= $usercount ?></td>
		<td <?= $jobcount > 0 ? 'style="background-color: #ccffcc;"' : "" ?>><?= $jobcount ?></td>
		<td><a href="customeredit.php?id=<?=$cust[0] ?>">Edit</a>&nbsp;|&nbsp;<a href="userlist.php?customer=<?= $cust[0] ?>">Users</a>&nbsp;|&nbsp;<a href="customerimports.php?customer=<?=$cust[0]?>">Imports</a>&nbsp;|&nbsp;<a href="customeractivejobs.php?customer=<?=$cust[0]?>">Jobs</a>&nbsp;|&nbsp;<a href="customerpriorities.php?id=<?=$cust[0]?>">Priorities</a></td>
		<td><?=$notes ? $notes : "&nbsp" ?></td>
		</tr>

	<?
	}
}
?>
</table>

<div>Pink cells indicate that only the system user account has been created</div>
<div>Red cells indicate that the customer has more users than they should</div>
<div>Green cells indicate customers with active jobs</div>
<?
include_once("navbottom.inc.php");
?>