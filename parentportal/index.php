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


include_once("cmlogintop.inc.php");
?>



<?
if ($badlogin) {
?>
	<div style="color: red;">Incorrect username/password. Please try again.</div>
<?
}
?>
<form method="POST" action="index.php" name="login">
	<table width="100%" style="color: #365F8D;" >
		<tr>
			<td width="20%">&nbsp;</td>
			<td>
				<div style="font-size: 20px; font-weight: bold;">Contact Manager</div>
				<div style="font-size: 12px;">Manage your contact preferences...</div>
			</td>
			<td width="80%">&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><a href="newportaluser.php">Sign Up Now(this is a button)</a></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><br><div style="font-weight: bold;">Already have an account?</div></td>
			<td>&nbsp;</td>
		<tr>
			<td>&nbsp;</td>
			<td>Email: <br><input type="text" id="logintext" name="login" size="35" value="<?=$login?>"/> </td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Password&nbsp;(case sensitive):<br><input type="password" name="password" size = "35" onkeypress="checkCapsLock(event)"/>
			</td>
			<td><br><div id="capslockwarning"  style="float:left; border:1px solid red; visibility:hidden; color:black; background-color:white">*Caps&nbsp;Lock&nbsp;is&nbsp;on.*</div></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><a href="forgotpassword.php">Forgot your password? Click Here!</a></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align="right"><div style="text-align: right;"><input type="image" src="img/b1_signin_dark.gif" onmouseover="this.src='img/b2_signin_dark.gif';" onmouseout="this.src='img/b1_signin_dark.gif';"></div></td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>
<?
include("cmloginbottom.inc.php");
?>
<script langauge="javascript">
document.getElementById('logintext').focus();

function checkCapsLock(e){
	<? //taken from http://www.codeproject.com/KB/scripting/Detect_Caps_Lock.aspx ?>
	keycode = e.keyCode ? e.keyCode : e.which;
	shiftkey = e.shiftKey ? e.shiftKey : ((keycode == 16) ? true : false);
	if(((keycode >= 65 && keycode <= 90) && !shiftkey) || ((keycode >= 97 && keycode <= 122) && shiftkey))
		new getObj('capslockwarning').obj.style.visibility = 'visible';
	else
		new getObj('capslockwarning').obj.style.visibility = 'hidden';
}

</script>