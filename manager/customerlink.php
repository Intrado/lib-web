<?
include_once("common.inc.php");
include_once("AspAdminUser.obj.php");
include_once("../inc/html.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


if(isset($_GET['id'])){
	$custid = $_GET['id'] +0;
}

$manager = new AspAdminUser($_SESSION['aspadminuserid']);

if(isset($_POST["password"])){
	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];
	if($manager->runCheck($password)){
		$customerurl = QuickQuery("select urlcomponent from customer where id = '$custid'");
		$string = md5(genpassword(32) . $manager->login . microtime() . $customerurl);
		// TODO we may want to set the expiration interval in the properties file
		QuickUpdate("update customer set logintoken = '$string', logintokenexpiretime = now() + interval 10 minute where id = '$custid'");
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
		<a href="<?=$SETTINGS['feature']['customer_url_prefix'] ."/". $customerurl?>/?asptoken=<?=$string?>" target="_blank"><?=$customerurl?></a>
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

include_once("navbottom.inc.php");
?>