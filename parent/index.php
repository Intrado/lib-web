<?
$parentloginbypass=1;

require_once("common.inc.php");
require_once("../inc/form.inc.php");

if(isset($_GET["logout"])) {
	@session_destroy();
	redirect();
}

$badlogin=false;

if(isset($_SESSION["parentuser"]))
	redirect("parentportal.php");

if(isset($_POST["submit"])) {

	$login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];
	$login = $login;
	$password = $password;

	$id = ParentUser::doLogin($login, $password, $CUSTOMERURL);
	if($id){
		$_SESSION['parentuser'] = new ParentUser($id);
		$PARENTUSER = $_SESSION['parentuser'];
		redirect("parentportal.php");
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
	<p> Please log in </p>
	<form method="POST" action="index.php">
		<p>Email: <input type="text" name="login" /> </p>
		<p>Password: <input type="password" name="password" /> </p>
		<input type="submit" value="Submit" name="submit" />
	</form>
	<p>Or Create a new account</p> <a href="newparent.php">New Parent Account</a>
