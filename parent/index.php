<?
$isparentlogin=1;

require_once("common.inc.php");
require_once("../inc/form.inc.php");

if(isset($_GET["logout"])) {
	@session_destroy();
	redirect();
}

$badlogin=false;

if(isset($_SESSION["parentloginid"]))
	redirect("parentportal.php");

if(isset($_POST["submit"])) {

	$login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];
	$login = dbsafe($login);
	$password = dbsafe($password);

	$id = ParentUser::doLogin($login, $password, $CUSTOMERURL);
	if($id){
		$_SESSION['parentloginid'] = new ParentUser($id);
		$PARENTUSER = $_SESSION['parentloginid'];
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
		<p>Login: <input type="text" name="login" /> </p>
		<p>Password: <input type="password" name="password" /> </p>
		<input type="submit" value="Submit" name="submit" />
	</form>
	<p>Or Create a new account</p> <a href="newparent.php">New Parent Account</a>
