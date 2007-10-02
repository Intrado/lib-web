<?
$isasplogin=1;

require_once("common.inc.php");
include_once("AspAdminUser.obj.php");

if(isset($_GET["logout"])) {
	@session_destroy();
}

if (!isset($_SERVER["HTTPS"])){
	redirect("https://" . $_SERVER["SERVER_NAME"] . "/junk/manager/index.php");
}

$badlogin=false;

if(isset($_SESSION["aspadminuserid"]))
	redirect("customers.php");

if(isset($_POST["submit"])) {

	$login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];

	$id = AspAdminUser::doLogin($login, $password);
	if($id){
		error_log("Manager login by $login");
		$_SESSION['aspadminuserid'] = $id;
		redirect("customers.php");
	} else {
		$badlogin = true;
	}
}
if ($badlogin) {
?>
	<div style="color: red;">Incorrect username/password. Please try again.</div>
<?
}
?>

	<form method="POST" action="index.php">
		<p>Login: <input type="text" name="login" /> </p>
		<p>Password: <input type="password" name="password" /> </p>
		<p><input type="submit" name="submit" /></p>
	</form>