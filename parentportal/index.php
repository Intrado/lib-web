<?

$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");


$changeuser = false;
$forgot = false;
if(isset($_GET['c'])){
	$changeuser = true;
	include("activate.php");
	exit();
} else if(isset($_GET['f'])){
	$forgot = true;
	include("activate.php");
	exit();
} else if(isset($_GET['n'])){
	include("activate.php");
	exit();
}


if (isset($_GET['logout'])) {
	doStartSession(); // start the session to get the id
	portalputSessionData(session_id(), ""); // write empty data to flush the user

	@session_destroy();
}
/*
	TODO:unsure if needed
if ($SETTINGS['feature']['has_ssl']) {
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])){
		redirect("https://" . $_SERVER["SERVER_NAME"] . "/index.php");
	}
}
*/
$login="";
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

$PAGE = ":";
$TITLE = "Contact Manager Login";
$hidenav = 1;
include_once("nav.inc.php");
startWindow("Login");
if ($badlogin) {
?>
	<div style="color: red;">Incorrect username/password. Please try again.</div>
<?
}
?>
<table>
	<form method="POST" action="index.php" name="login">
		<tr><td>Email: </td><td><input type="text" name="login" size="30" value="<?=$login?>"/> </td></tr>
		<tr><td>Password: </td><td><input type="password" name="password" /> </td></tr>
		<tr><td>&nbsp;</td><td><?=submit("login", "main", "Login") ?></td></tr>
	</form>

<tr><td>&nbsp;</td><td><a href="newportaluser.php"> Create a new account</a></td></tr>
<tr><td>&nbsp;</td><td><a href="forgotpassword.php">I forgot my password</a></td></tr>
</table>
<?
endWindow();
include_once("navbottom.inc.php");
?>