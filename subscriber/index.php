<?
$isNotLoggedIn = 1;

require_once("authsubscriber.inc.php");
require_once("common.inc.php");
require_once("subscriberutils.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/FieldMap.obj.php");


$changeuser = false;
$forgot = false;

if (isset($_GET['c'])) { // activate change username
	$changeuser = true;
	require_once("activate.php");
	exit();
} else if (isset($_GET['f'])) { // activate reset password
	$forgot = true;
	require_once("activate.php");
	exit();
} else if (isset($_GET['n'])) { // activate new account
	require_once("activate.php");
	exit();
}

if(isset($_GET['embedded'])){
	setcookie('embeddedpage', "1");
	redirect();
}
if(isset($_GET['deleteembedded'])){
	setcookie('embeddedpage');
	redirect();
}

if (isset($_GET['logout'])) {
	doStartSession(); // start the session to get the id
	subscriberPutSessionData(session_id(), ""); // write empty data to flush the user

	@session_destroy();
}

if ($SETTINGS['feature']['has_ssl']) {
	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])){
		redirect("https://" . $_SERVER["SERVER_NAME"] . "/index.php");
	}
}



$login="";
$badlogin=false;
$id = false;
$sessionstarted = false;


if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {

	$login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];

	$result = subscriberLogin($CUSTOMERURL, $login, $password);
	if ($result['result'] == "")
		$id = $result['userID'];
	else
		$badlogin = true;

} else if (!isset($_GET['logout'])){
	doStartSession(); // we must start the session to obtain the user information before trying to perform the following IF conditions
	$sessionstarted = true;
	if (isset($_SESSION['subscriberid'])) {
		$redirpage = isset($_SESSION['lasturi']) ? $_SESSION['lasturi'] : 'start.php';
		unset($_SESSION['lasturi']);
		redirect($redirpage);
    }
}



if ($id) {
	if (!$sessionstarted)
		doStartSession();
	$_SESSION['subscriberid'] = $id;
	loadSubscriberDisplaySettings();
	redirect("start.php");
}


$TITLE= "Sign In";

require_once("logintop.inc.php");

?>
<form method="POST" action="index.php" name="login">
	<table style="color: #<?=$primary?>;" >
<?	if (!$custname) {
?>
		<tr>
			<td colspan="3">
				<div width="100%" style="font-size: 16px; font-weight: bold; color: red;">&nbsp;&nbsp;<?=_L("Invalid customer URL. Please check the web address and try again.")?></div>
			</td>
		</tr>
<?	} else {
?>
		<tr>
			<td colspan="3">
				<div style="font-size: 20px; font-weight: bold;">Phone, Email, and Text Messages</div>
				<br>
				<div style="font-size: 15px; font-weight: bold;">Get the latest communication from <?=$custname?>.</div>
				<br>
				<br>
			</td>
		</tr>
		<tr>
			<td colspan="3">
<?			if ($badlogin) {
?>
				<div style="color: red;"><?=_L("Incorrect account email or password. Please try again.")?></div><br>
<?			}
?>
			</td>
		<tr>
			<td>Email:</td>
			<td><input type="text" id="logintext" name="login" size="50" maxlength="255" value="<?=escapehtml($login)?>"/></td>
			<td>&nbsp;</td>

		</tr>
		<tr>
			<td>Password&nbsp;(case&nbsp;sensitive):</td>
			<td><input type="password" name="password" size = "50" maxlength="50" onkeypress="capslockCheck(event)"/></td>
			<td align="left"><a href="forgotpassword.php">Forgot your password? Click Here</a></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><br><div id="capslockwarning"  style="padding-left:3px; float:left; display:none; color:red;">Warning! Your Caps Lock key is on.</div></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align="right"><?= submit_button("Sign In","save","tick") ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="3">First time accessing the SchoolMessenger Self Signup?</td>
		</tr>
		<tr>
			<td colspan="3"><a href="newsubscribersession.php"><b>Sign up now</b></a></td>
		</tr>
<?	}
?>
	</table>
</form>
<br>
<br>
<br>
<br>
<br>
<?


require_once("loginbottom.inc.php");
?>
<script langauge="javascript">
document.getElementById('logintext').focus();

function capslockCheck(e){
		var keypressed;
		var shiftkey;

		if(e.keyCode)
			keypressed = e.keyCode;
		else
			keypressed = e.which;

		if(e.shiftKey) {
			shiftkey = true;
		} else {
			if(keypressed == 16) {
				shiftkey = true;
			} else {
				shiftkey = false;
			}
		}
		if(((keypressed >= 65 && keypressed <= 90) && !shiftkey) || ((keypressed >= 97 && keypressed <= 122) && shiftkey)){
			new getObj('capslockwarning').style.display = 'block';
		} else
			new getObj('capslockwarning').style.display = 'none';
	}

</script>