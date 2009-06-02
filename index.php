<?
$isindexpage = true;
require_once("inc/common.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");

if(isset($_GET['f'])){
	include("resetpassword.php");
	exit();
}

if (isset($_GET['logout'])) {
	doStartSession(); // start the session to get the id
	putSessionData(session_id(), ""); // write empty data to flush the user

	@session_destroy();
}

if ($SETTINGS['feature']['has_ssl']) {
	if ($IS_COMMSUITE)
		$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/index.php";
	/*CSDELETEMARKER_START*/
	else
		$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/$CUSTOMERURL/index.php";
	/*CSDELETEMARKER_END*/


	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect($secureurl);
	}
}

//check various ways to log in
$badlogin = false;
$softlock = false;
$userid = false;
$updatelogin = false;
$sessionstarted = false;
if (isset($_GET['login'])) {
	$login = get_magic_quotes_gpc() ? stripslashes($_GET['login']) : $_GET['login'];
	/*CSDELETEMARKER_START*/
	if(!$IS_COMMSUITE && $_GET['login'] == 'schoolmessenger'){
		@session_destroy();
		$badlogin = true;
	} else {
	/*CSDELETEMARKER_END*/
		doStartSession(); // we must start the session to obtain the user information before trying to perform the following IF conditions
		$sessionstarted = true;
		if (isset($_SESSION['user']) && is_object($_SESSION['user']) && $_SESSION['user']->authorize('manageaccount')) {
			$userid = forceLogin($login, $CUSTOMERURL);
		} else {
			$badlogin = true;
			error_log("FORCE login failed");
		}

	/*CSDELETEMARKER_START*/
	}
	/*CSDELETEMARKER_END*/

} else if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$f_login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$f_pass = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];
	$userid = doLogin($f_login, $f_pass, $CUSTOMERURL, $_SERVER['REMOTE_ADDR']);
	if ($userid == -1) {
		$softlock = true;
	} else if(!$userid){
		$badlogin = true;
		error_log("User trying to log in but has bad user/pass/url");
	}
	if($userid)
		$updatelogin = true;
} else if (isset($_GET['asptoken'])) {
	if (!$userid = asptokenLogin($_GET['asptoken'], $CUSTOMERURL)) {
		$badlogin = true;
		error_log("ASPTOKEN login failure");
	}
	if($userid)
		$updatelogin = true;
} else if (!isset($_GET['logout'])){
	doStartSession(); // we must start the session to obtain the user information before trying to perform the following IF conditions
	$sessionstarted = true;

	if (isset($_SESSION['user'])) {
		$redirpage = isset($_SESSION['lasturi']) ? $_SESSION['lasturi'] : 'start.php';
		unset($_SESSION['lasturi']);
		redirect($redirpage);
	}
}

//if we got a valid userid from above, log in for that user.
if ($userid && $userid != -1) {
	if (!$sessionstarted)
		doStartSession();
	loadCredentials($userid);
	if (!$USER->enabled || $USER->deleted | !$ACCESS->getValue('loginweb')) {
		@session_destroy();
		$badlogin = true;
		error_log("User trying to log in but is disabled or doesnt have access");
	} else {
		if ($updatelogin) {
			$USER->lastlogin = QuickQuery("select now()");
			$USER->update(array("lastlogin"));
		}
		loadDisplaySettings();
		redirect("start.php");
	}
}

$custname = getCustomerName($CUSTOMERURL); // also found by getSystemSetting("displayname") but we may not be logged in yet

$TITLE=_L("Login");
//primary colors are pulled in login top
include_once("logintop.inc.php");
if ($IS_COMMSUITE) {
?>
	<form action="index.php" method="POST">
		<table border="0" cellpadding="10" cellspacing="0" style="width: 500px; color: #9B9B9B; font-size: 14px; color: #<?=$primary?>;" align="center">
			<tr>
				<td colspan="2">
<?if ($badlogin) { ?>
					<div style="color: red;"><?=_L("Incorrect username/password. Please try again.")?></div>
<? } else if ($softlock) { ?>
					<div style="color: red;"><?=_L("You are temporarily locked out of the system.  Please contact your System Administrator if you have forgotten your password and try again later.")?></div>
<? } else if (!$custname) { ?>
					<div style="color: red;"><?=_L("Invalid customer URL. Please check the web address and try again.")?></div>
<? } else { ?>
					<?=_L("Please log in here.")?>
<? } ?>
				</td>
			</tr>
			<tr><td align="right" style="padding: 2px;" width="165"><?=_L("Login:")?></td><td><input type="text" name="login" size="35" id="logintext"></td></tr>
			<tr><td align="right" style="padding: 2px;"><?=_L("Password:")?></td><td><input type="password" name="password" size="35" onkeypress="capslockCheck(event)"></td></tr>
			<tr><td align="right" style="padding: 2px;">&nbsp;</td><td style="font-size: 12px;"><div id="capslockwarning"  style="padding-left:3px; display:none; color:red;"><?=_L("Warning! Your Caps Lock key is on.")?></div></td></tr>
			<tr><td align="right" style="padding: 2px;">&nbsp;</td><td style="font-size: 12px;" align="left"><a href="forgotpassword.php"><?=_L("Forgot your password? Click Here")?></a></td></tr>
			<tr><td colspan="2"><div style="text-align: right;"><input type="image" src="img/themes/<?=$scheme['_brandtheme']?>/b1_signin_dark.gif" onmouseover="this.src='img/themes/<?=$scheme['_brandtheme']?>/b2_signin_dark.gif';" onmouseout="this.src='img/themes/<?=$scheme['_brandtheme']?>/b1_signin_dark.gif';"></div></td></tr>
<? if ($SETTINGS['feature']['has_ssl'] && !isset($_SERVER["HTTPS"])) { ?>
			<tr><td colspan="2" align="right"><a href="<?= $secureurl?>" style="font-size: x-small;"><img src="img/padlock.gif" style="border: 0px;"> <?=_L("Switch to Secure Login")?></a></td></tr>
<? } ?>
			<tr>
				<td colspan="2" style="font-size: x-small; font-weight: normal;"><?=_L("Usernames and passwords are case-sensitive.")?></td>
			</tr>
		</table>
	</form>
<?

} /*CSDELETEMARKER_START*/ else {
?>

	<form action="index.php" method="POST">

<? if ($custname) { ?>
	
		<table width="100%" style="color: #<?=$primary?>; text-align: right; ">
			<tr>

<? if ($badlogin) { ?>
				<td width="100%" style="font-size: 12px; font-weight: bold; color: red;"><?=_L("Incorrect username/password. Please try again.")?></td>
<? } else if ($softlock) { ?>
				<td width="100%" style="font-size: 12px; font-weight: bold; color: red;"><?=_L("You are temporarily locked out of the system.  Please contact your System Administrator if you have forgotten your password and try again later.")?></td>
<? } else { ?>
				<td width="100%">&nbsp;</td>
<? } ?>
				<td><img src="img/spacer.gif" width="25"></td>
			</tr>
		</table>

		<div><table width="100%" style="color: #<?=$primary?>;" >
			<tr>
				<td width="20%">&nbsp;</td>
				<td style="font-size: 12px;"><div style="margin-left: 50px;"><?=_L("Login:")?><br><input type="text" name="login" size="35" id="logintext"></div></td>
				<td width="80%">&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td style="font-size: 12px;"><div style="margin-left: 50px;"><?=_L("Password:")?><br><input type="password" name="password" size="35" onkeypress="capslockCheck(event)"></div></td>
				<td style="font-size: 12px;" align="left"><a href="forgotpassword.php"><?=_L("Forgot your password? Click Here")?></a></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td style="font-size: 12px;"><br><div id="capslockwarning"  style="padding-left:3px; float:right; display:none; color:red;"><?=_L("Warning! Your Caps Lock key is on.")?></div></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><div style="text-align: right;"><input type="image" src="img/themes/<?=$scheme['_brandtheme']?>/b1_signin_dark.gif" onmouseover="this.src='img/themes/<?=$scheme['_brandtheme']?>/b2_signin_dark.gif';" onmouseout="this.src='img/themes/<?=$scheme['_brandtheme']?>/b1_signin_dark.gif';"></div></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" style="font-size: 9px; font-style: italic;"><div style="margin-left: 50px;"><?=_L("Usernames and passwords are case-sensitive.")?></div></td>
			</tr>
		</table>
<? } else { ?>
		<div width="100%" style="font-size: 16px; font-weight: bold; color: red;">&nbsp;&nbsp;<?=_L("Invalid customer URL. Please check the web address and try again.")?></div>
<? }?>
	</form>
<?
} /*CSDELETEMARKER_END*/
include_once("loginbottom.inc.php");

if (!($custname === false)) { ?>
<script langauge="javascript">

new getObj('logintext').obj.focus();

function capslockCheck(e){
	var keypressed;
	var shiftkey;

	if(e.keyCode)
		keypressed = e.keyCode;
	else
		keypressed = e.which;

	if(e.shiftKey) {
		shiftkey = e.shiftKey;
	} else {
		if(keypressed == 16) {
			shiftkey = true;
		} else {
			shiftkey = false;
		}
	}
	if(((keypressed >= 65 && keypressed <= 90) && !shiftkey) || ((keypressed >= 97 && keypressed <= 122) && shiftkey)){
		new getObj('capslockwarning').style.display = 'block';
	} else {
		new getObj('capslockwarning').style.display = 'none';
	}
}

function getObj(name)
{
  if (document.getElementById)
  {
	this.obj = document.getElementById(name);
  }
  else if (document.all)
  {
	this.obj = document.all[name];
  }
  else if (document.layers)
  {
	this.obj = document.layers[name];
  }
  if(this.obj)
	this.style = this.obj.style;
}

</script>
<?}?>