<?
include_once("common.inc.php");
include_once("AspAdminUser.obj.php");
include_once("../inc/html.inc.php");


if(isset($_GET['id'])){
	$custid = $_GET['id'] +0;
}

$manager = new AspAdminUser($_SESSION['aspadminuserid']);

if($_REQUEST['submit']){
	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];
	if($manager->runCheck($password)){
		$string = md5(microtime());
		// TODO we may want to set the expiration interval in the properties file
		QuickQuery("update customer set asptoken = '$string', aspexpiration = now() + interval 10 minute where id = '$custid'");
		$customerurl = QuickQuery("select hostname from customer where id = '$custid'");
	} else {
		error("That was an invalid password");
	}
}

include_once("nav.inc.php");
?>
<br>
Link:
<?
	if(isset($string)){
?>
		<a href="http://asp.schoolmessenger.com/<?=$customerurl?>/?asptoken=<?=$string?>" target="_blank"><?=$customerurl?></a>
<?
	}
?>
<br>
<br>
<form method="POST" action="customerlink.php?id=<?=$custid?>">
Enter Manager Password: <input type="password" name="password" />
<input type="submit" name="submit" />
</form>

<?
if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}

include_once("navbottom.inc.php");
?>