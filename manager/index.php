<?
$isasplogin=1;

require_once("common.inc.php");

if(isset($_GET["logout"])) {
	@session_destroy();
	if (isset($_GET['reason']))
		redirect("index.php?reason=" . urlencode($_GET['reason']));
	else
		redirect();
}

if ($SETTINGS['feature']['has_ssl']) {
	$location = substr($_SERVER["SCRIPT_NAME"],1);
	$location = strtolower(substr($location,0,strrpos($location,"/")));
	$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/" . $location . "/index.php";

	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect($secureurl);
	}
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
		
		$autologoutminutes = isset($SETTINGS['feature']['autologoutminutes']) ? $SETTINGS['feature']['autologoutminutes'] : 30;
		$_SESSION['expiretime'] = time() + 60*$autologoutminutes; //30 minutes
	
		redirect("/$login/manager/customers.php");
	} else {
		$badlogin = true;
	}
}
?>
<html>
<head>
	<title>Manager Login</title>
	<link rel="SHORTCUT ICON" href="img/manager_favicon.ico" />
</head>
<body onload="
var l = document.forms[0].login;
l.focus();">

<?
if ($badlogin) {
?>
	<div style="color: red;">Incorrect username/password. Please try again.</div>
<?
} else if (isset($_GET['reason'])) {
	switch ($_GET['reason']) {
		case "nosession" : echo "<em>Logout due to invalid session data</em>"; break;
		case "timeout" : echo "<em>Logout due to inactivity timeout</em>"; break;
		case "badurl" : echo "<em>Url/name mismatch</em>"; break;
		case "request" : echo "<em>Logout by user request</em>"; break;		
	}
}
?>

	<form method="POST" action="index.php">
		<p>Login: <input type="text" name="login" /> </p>
		<p>Password: <input type="password" name="password" /> </p>
		<p><input type="submit" name="submit" /></p>
	</form>
</body>
</html>
