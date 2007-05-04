<?
$isindexpage = true;
require_once("inc/common.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");

if (isset($_GET['logout']))
	@session_destroy();

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


$badlogin = false;
if (isset($_GET['login'])) {
	doStartSession();
}
if (isset($_GET['login']) && is_object($_SESSION['user']) && $_SESSION['user']->authorize('manageaccount')) {
	/*CSDELETEMARKER_START*/
	if($_GET['login'] == 'schoolmessenger'){
		redirect("unauthorized.php");
	}
	/*CSDELETEMARKER_END*/
	$id = forceLogin(get_magic_quotes_gpc() ? stripslashes($_GET['login']) : $_GET['login'], $CUSTOMERURL);

	if ($id) {
		$USER = $_SESSION['user'] = new User($id);
		$_SESSION['access'] = new Access($USER->accessid);
		$_SESSION['custname'] = getSystemSetting("displayname");
		$_SESSION['timezone'] = getSystemSetting("timezone");
		redirect("start.php");
	} else {
		$badlogin = true;
	}
} elseif (isset($_SESSION['user'])) {
	$redirpage = isset($_SESSION['lasturi']) ? $_SESSION['lasturi'] : 'start.php';
	unset($_SESSION['lasturi']);
	redirect($redirpage);
} elseif ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ||
		  (isset($_GET['asptoken']))) {

	if (isset($_GET['asptoken'])) {
		$id = asptokenLogin($_GET['asptoken'], $CUSTOMERURL);
	} else {
		$id = doLogin(get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'], get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'],$CUSTOMERURL);
	}

	if ($id) {
		$newuser = new User($id);
		$newaccess = new Access($newuser->accessid);
		if($newuser->enabled && $newaccess->getValue('loginweb')) {
			$USER = $_SESSION['user'] = $newuser;
			$ACCESS = $_SESSION['access'] = $newaccess;
			$_SESSION['custname'] = getSystemSetting("displayname");
			$_SESSION['timezone'] = getSystemSetting("timezone");
			QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
			$USER->lastlogin = QuickQuery("select now()");
			$USER->update(array("lastlogin"));
			redirect("start.php");
		} else {
			$badlogin = true;
		}
	} else {
		$badlogin = true;
	}
}

$custname = getCustomerName($CUSTOMERURL); // also found by getSystemSetting("displayname") but we may not be logged in yet


if ($IS_COMMSUITE) {

?>

<html>
<head>
<title>SchoolMessenger Login</title>
	<script src='script/utils.js'></script>
	<script src='script/nav.js'></script>
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='css/style.css' type='text/css' rel='stylesheet' media='screen'>
</head>
<body>

<form action="index.php" method="POST">
<?
$logofilename = "img/customlogo.gif";
if (file_exists($logofilename) ) {
?>
<img style="margin: 15px; border: solid 15px  white;" src="<?= $logofilename ?>">
<? } else { ?>
<br><br><br><br>
<? } ?>
<table align="center" cellpadding="8" cellspacing="0" style="border: 7px solid #9B9B9B;">
	<tr>
		<td bgcolor="#365F8D"><img id='brand' src='img/school_messenger.gif' /></td>
		<td bgcolor="#365F8D" align="center"><div id='orgtitle'><?= htmlentities($custname) ?></div></td>
	</tr>
	<tr>
		<td colspan="2">
			<table border="0" cellpadding="10" cellspacing="0" id="login" align="center">
				<tr>
					<td colspan="2">
<? if ($badlogin) { ?>
						<div style="color: red;">Incorrect username/password. Please try again.</div>
<? } else if ($custname === false) { ?>
						<div style="color: red;">Invalid customer URL. Please check the URL and try again.</div>
<? } else { ?>
						Please log in here.
<? } ?>
					</td>
				</tr>
				<tr><td align="right" style="padding: 2px;" width="165">Login:</td><td><input type="text" name="login" size="35" id="logintext"></td></tr>
				<tr><td align="right" style="padding: 2px;">Password:</td><td><input type="password" name="password" size="35"></td></tr>
				<tr><td colspan="2" align="right"><? print submit('login', 'main', 'signin', 'signin'); ?></td></tr>
<? if ($SETTINGS['feature']['has_ssl'] && !isset($_SERVER["HTTPS"])) { ?>
				<tr><td colspan="2" align="right"><a href="<?= $secureurl?>"><img src="img/padlock.gif"> Switch to Secure Login</a></td></tr>
<? } ?>
				<tr>
					<td colspan="2" style="font-size: x-small; font-weight: normal;">Usernames and passwords are case-sensitive.</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>

<script langauge="javascript">
new getObj('logintext').obj.focus();
</script>

</body>

</html>

<?

} /*CSDELETEMARKER_START*/ else {
?>

<html>
<head>
<title>SchoolMessenger Login</title>

</head>
<body style='font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif; margin: 0px; background-color: #365F8D;'>
<form action="index.php" method="POST">

<table border=0 cellpadding=0 cellspacing=0 width="100%">
<tr style="background-color: #365F8D;">
	<td width="389"><img src="img/school_messenger_large.gif" /></td>
	<td width="100%">&nbsp;</td>
</tr>
<tr style="background-color: #666666;">
	<td colspan="2">&nbsp;</td>
</tr>
<tr>
	<td><img src="img/classroom_girl.jpg"></td>
	<td style="background-color: #D4DDE2; color: #365F8D;">

		<table width="100%" style="color: #365F8D; text-align: right;">
			<tr>
				<td width="100%" style="font-size: 18px; font-weight: bold; text-align: right;"><?= htmlentities($custname) ?></div></td>
				<td><img src="img/spacer.gif" width="25"></td>
			</tr>

			<tr>

<? if ($badlogin) { ?>
				<td style="font-size: 12px; font-weight: bold; color: red;">Incorrect username/password. Please try again.</td>
<? } else if ($custname === false) { ?>
				<td style="font-size: 12px; font-weight: bold; color: red;">Invalid customer URL. Please check the URL and try again.</td>
<? } else { ?>
				<td>&nbsp;</td>
<? } ?>

				<td>&nbsp;</td>
			</tr>
		</table>

		<div><table width="100%" style="color: #365F8D;" >
			<tr>
				<td width="20%">&nbsp;</td>
				<td style="font-size: 12px;"><div style="margin-left: 50px;">Login:<br><input type="text" name="login" size="35" id="logintext"></div></td>
				<td width="80%">&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td style="font-size: 12px;"><div style="margin-left: 50px;">Password:<br><input type="password" name="password" size="35"></div></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><div style="text-align: right;"><input type="image" src="img/b1_signin_dark.gif" onmouseover="this.src='img/b2_signin_dark.gif';" onmouseout="this.src='img/b1_signin_dark.gif';"></div></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" style="font-size: 9px; font-style: italic;"><div style="margin-left: 50px;">Usernames and passwords are case-sensitive.</div></td>
			</tr>
		</table>

	</td>
</tr>
<tr style="background-color: white;">
	<td>&nbsp;</td>
	<td>
		<div style="text-align: right; margin: 5px;">
			<script src=https://seal.verisign.com/getseal?host_name=asp.schoolmessenger.com&size=S&use_flash=NO&use_transparent=NO&lang=en></script><br />
			<a href="http://www.verisign.com/ssl-certificate/" target="_blank"  style="color:#000000; text-decoration:none; font:bold 7px verdana,sans-serif; letter-spacing:.5px; text-align:center; margin:0px; padding:0px;">About SSL Certificates</a>
		</div>
	</td>
</tr>

<tr style="background-color: #365F8D; color: white;">
	<td colspan="2"><div style="text-align:right; font-size: 12px; margin: 5px;">
		<p>If you experience difficulty logging in or require assistance, please contact us:</p>
		<p>Email:&nbsp;<a style="color: white;" href="mailto:support@schoolmessenger.com">support@schoolmessenger.com</a></p>
		<p>Phone:&nbsp;800.920.3897</p?
	</div></td>
</tr>

</form>

<script langauge="javascript">
document.getElementById('logintext').focus();
</script>

</body>
</html>


<?
} /*CSDELETEMARKER_END*/