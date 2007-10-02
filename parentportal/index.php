<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("PortalUser.obj.php");


if(isset($_GET["logout"])) {
	@session_destroy();
}

if (!isset($_SERVER["HTTPS"])){
	redirect("https://" . $_SERVER["SERVER_NAME"] . "/junk/parentportal/index.php");
}
$badlogin=false;
$id = 0;

if(isset($_SESSION["portaluserid"]))
	redirect("parentportal.php");

if(isset($_POST["submit"])) {

	$login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];

	$id = portalLogin($login, $password);
	if(!$id){
		$badlogin = true;
	}
}
if($id){
	$_SESSION['portaluserid'] = $id;
	redirect("choosecustomer.php");
}
if ($badlogin) {
?>
	<div style="color: red;">Incorrect username/password. Please try again.</div>
<?
}
?>

	<form method="POST" action="index.php">
		<p>Email: <input type="text" name="login" /> </p>
		<p>Password: <input type="password" name="password" /> </p>
		<p><input type="submit" name="submit" /></p>
	</form>
	<p><a href="newportaluser.php"> Create a new account</a></p>
	<p><a href="forgotpassword.php">I forgot my password</a></p>