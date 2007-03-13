<?
include_once("common.inc.php");
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");

$customerID = $_GET["customer"] + 0;


//Finds how many users in customer.
$users = DBFindMany("User","from user where customerid='$customerID' AND enabled=1 AND deleted=0");
$customerresult = Query("select id, name, hostname from customer where id = '$customerID'");
$customerinfo = DBGetRow($customerresult);
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
//iterates through each user and finds needed information.
foreach ($users as $user) {
	$lastlogin = strtotime($user->lastlogin);
	if ($lastlogin !== -1 && $lastlogin !== false && $user->lastlogin != "0000-00-00 00:00:00")
		$lastlogin = date("M j, g:i a",$lastlogin);
	else
		$lastlogin = "- Never -";


	$jobcount = QuickQuery("SELECT COUNT(*) FROM job WHERE job.userid = $user->id
						AND job.status = 'active'");

	$accessid=new Access($user->accessid);

?>
	<tr>
		<td><?= $customerinfo[0] ?></td>
		<td><?= $customerinfo[1] ?></td>
		<td><a href="https://asp.schoolmessenger.com/<?=$customerinfo[2]?>"><?=$customerinfo[2]?></a></td>
		<td><?= $user->id ?></td>
		<td><?= $user->login ?></td>
		<td><?= $user->lastname ?></td>
		<td><?= $user->firstname ?></td>
		<td><?= $lastlogin ?></td>
		<td><a href="customeractivejobs.php?user=<?=$user->id?>"><?= $jobcount ?></a></td>
		<td><?= $accessid->name ?></td>
		<td><?= $user->phone ?></td>
		<td><?= $user->email ?></td>

	</tr>
<?
}
?>
</table>
<?
include_once("navbottom.inc.php");
?>