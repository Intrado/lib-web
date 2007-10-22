<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");


if (isset($_GET['logout'])) {
	doStartSession(); // start the session to get the id
	portalputSessionData(session_id(), ""); // write empty data to flush the user

	@session_destroy();
}
/*
	TODO:unsure if needed
if ($SETTINGS['feature']['has_ssl']) {
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])){
		redirect("https://" . $_SERVER["SERVER_NAME"] . "/junk/parentportal/index.php");
	}
}
*/
$badlogin=false;
$id = false;
$sessionstarted = false;

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {

	$login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];

	$result = portalLogin($login, $password);
	if($result['result'] == "")
		$id = $result['userID'];
	else
		$badlogin = true;
		
} else if (!isset($_GET['logout'])){
	doStartSession(); // we must start the session to obtain the user information before trying to perform the following IF conditions
	$sessionstarted = true;

	if (isset($_SESSION['portaluserid'])) {
		$redirpage = isset($_SESSION['lasturi']) ? $_SESSION['lasturi'] : 'choosecustomer.php';
		unset($_SESSION['lasturi']);
		redirect($redirpage);
    }
}
if($id){
	if (!$sessionstarted)
		doStartSession();
	$_SESSION['portaluserid'] = $id;
	redirect("choosecustomer.php");
}
if ($badlogin) {
?>
	<div style="color: red;">Incorrect username/password. Please try again.</div>
<?
}
$PAGE = ":";
$TITLE = "Parent Portal Login";
$hidenav = 1;
include_once("nav.inc.php");
?>

	<form method="POST" action="index.php">
		<p>Email: <input type="text" name="login" /> </p>
		<p>Password: <input type="password" name="password" /> </p>
		<p><input type="submit" name="submit" value="Login"/></p>
	</form>
	<p><a href="newportaluser.php"> Create a new account</a></p>
	<p><a href="forgotpassword.php">I forgot my password</a></p>
<?
include_once("navbottom.inc.php");
?>