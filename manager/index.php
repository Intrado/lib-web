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
	<link rel="SHORTCUT ICON" href="mimg/manager_favicon.ico" />
	
	<style>
	em { position: absolute; top: 170px; left: 50%; width: 330px; margin-left: -165px; text-align: center; }
	.incorrect { position: absolute; top: 170px; left: 50%; width: 330px; margin-left: -165px; text-align: center; color: red; }
	form { position: absolute; top: 200px; left: 50%; background: #f1f1f1; width: 300px; margin-left: -165px; padding: 15px 15px 5px 15px; 
	border-radius: 8px; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.5); }
	form fieldset { border: none; margin: 0 0 8px 0; padding: 0; }
	form label { display: inline; float: left; width: 80px; margin: 0 10px 0 0; padding: 6px 0; text-align: right; font-size: 14px; }
	form input { display: inline; float: right; width: 200px; padding: 5px 4px; border: 1px solid #ccc; font-size: 14px; border-radius: 4px; }
	form input[type="submit"] { background: #7da7d9; width: 100px; border: 1px solid #628bbc; color: #fff; 
	-webkit-box-shadow: 0px 1px 2px 0px #fff;
     -moz-box-shadow: 0px 1px 2px 0px #fff;
          box-shadow: 0px 1px 2px 0px #fff;}
	form input[type="submit"]:hover { background: #89b3e5; 
  -webkit-box-shadow: 0px 1px 1px -1px #999, inset 0px 2px 2px 0px rgba(255,255,255,0.5);
     -moz-box-shadow: 0px 1px 1px -1px #999, inset 0px 2px 2px 0px rgba(255,255,255,0.5);
          box-shadow: 0px 1px 1px -1px #999, inset 0px 2px 2px 0px rgba(255,255,255,0.5); }
  form input[type="submit"]:active { background: #6d98cc; border: 1px solid #527aa9; color: #f9f9f9; 
  -webkit-box-shadow: inset 0px 2px 4px 0px rgba(0,0,0,0.2);
     -moz-box-shadow: inset 0px 2px 4px 0px rgba(0,0,0,0.2);
          box-shadow: inset 0px 2px 4px 0px rgba(0,0,0,0.2); }
	</style>
</head>
<body onload="
var l = document.forms[0].login;
l.focus();">

<?
if ($badlogin) {
?>
	<div class="incorrect">Incorrect username/password. Please try again.</div>
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
	<fieldset>
		<label for="login">Login:</label><input type="text" name="login" id="login"/>
	</fieldset>
	<fieldset>
		<label for="pass">Password:</label> <input type="password" name="password" id="pass"/>
	</fieldset>
	<fieldset>
		<input type="submit" name="submit" value="Sign In"/>
	</fieldset>
	</form>
</body>
</html>
