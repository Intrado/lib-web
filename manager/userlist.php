<?
include_once("common.inc.php");
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");

$customerid = $_GET["customer"] + 0;

$custquery = Query("select dbhost, dbusername, dbpassword, hostname from customer where id = '$customerid'");
$cust = mysql_fetch_row($custquery);

include_once("nav.inc.php");

?>

<table border=1>
<tr>
	<td>Customer ID</td>
	<td>Customer Name</td>
	<td>Customer URL</td>
	<td>User ID</td>
	<td>User Name</td>
	<td>Last Name</td>
	<td>First Name</td>
	<td>Last Login</td>
	<td>Active Jobs</td>
	<td>Access Profile</td>
	<td>Phone</td>
	<td>Email</td>

</tr>

<?
if($custdb = DBConnect($cust[0], $cust[1], $cust[2], "c_$customerid")){
	$displayname = QuickQuery("select value from setting where name = 'displayname'", $custdb);
	$result = Query("select * from user where enabled=1 AND deleted=0", $custdb);
	$users = array();
	while($row = DBGetRow($result, true)){
		$users[] = $row;
	}
	//iterates through each user and find needed information.
	foreach ($users as $user) {
		$lastlogin = strtotime($user['lastlogin']);
		if ($lastlogin !== -1 && $lastlogin !== false && $lastlogin != "0000-00-00 00:00:00")
			$lastlogin = date("M j, g:i a",$lastlogin);
		else
			$lastlogin = "- Never -";
	
	
		$jobcount = QuickQuery("SELECT COUNT(*) FROM job WHERE job.userid = '" . $user['id'] . "'
							AND job.status = 'active'", $custdb);
	
		//$accessid=new Access($user->accessid);
		$access = QuickQueryRow("select * from access where id = '" . $user['accessid'] ."'", true, $custdb);
?>
		<tr>
			<td><?= $customerid ?></td>
			<td><?= $displayname ?></td>
			<td><a href="customerlink.php?id=<?=$customerid?>" target="_blank"><?=$cust[3]?></a></td>
			<td><?= $user['id'] ?></td>
			<td><?= $user['login'] ?></td>
			<td><?= $user['lastname'] ?></td>
			<td><?= $user['firstname'] ?></td>
			<td><?= $lastlogin ?></td>
			<td><a href="customeractivejobs.php?user=<?=$user['id']?>"><?= $jobcount ?></a></td>
			<td><?= $access['name'] ?></td>
			<td><?= $user['phone'] ?></td>
			<td><?= $user['email'] ?></td>
	
		</tr>
<?
	}
}
?>
</table>
<?
include_once("navbottom.inc.php");

?>