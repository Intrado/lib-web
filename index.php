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
if (isset($_GET['login']) && is_object($_SESSION['user']) && $_SESSION['user']->authorize('manageaccount')) {
	$olduser = $_SESSION['user'];
	$_SESSION = array();
//	@session_destroy();
//	@session_name($CUSTOMERURL . "_session");
//	@session_start();
	$login = DBSafe($_GET['login']);
	$id = User::forceLogin($login,$CUSTOMERURL,$olduser->customerid);

	if ($id) {
		$USER = $_SESSION['user'] = new User($id);
		$_SESSION['access'] = new Access($USER->accessid);
		$_SESSION['custname'] = QuickQuery("select name from customer where id = $USER->customerid");
		$_SESSION['timezone'] = QuickQuery("select timezone from customer where id=$USER->customerid");
		redirect("start.php");
	} else {
		$badlogin = true;
	}
} elseif (isset($_SESSION['user'])) {
	$redirpage = isset($_SESSION['lasturi']) ? $_SESSION['lasturi'] : 'start.php';
	unset($_SESSION['lasturi']);
	redirect($redirpage);
} elseif (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {

	$id = User::doLogin(get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'], get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'],$CUSTOMERURL);

	if ($id) {
		$newuser = new User($id);
		$newaccess = new Access($newuser->accessid);
		if($newuser->enabled && $newaccess->getValue('loginweb')) {
			$USER = $_SESSION['user'] = $newuser;
			$ACCESS = $_SESSION['access'] = $newaccess;
			$_SESSION['custname'] = QuickQuery("select name from customer where id = $USER->customerid");
			$_SESSION['timezone'] = QuickQuery("select timezone from customer where id=$USER->customerid");
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

//try to find the customer's name
$custname = QuickQuery("select name from customer where hostname='" . DBSafe($CUSTOMERURL) . "'");

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
<? /*CSDELETEMARKER_START*/ if (isset($_SERVER["HTTPS"]) && !$IS_COMMSUITE) { ?>
				<tr><td colspan="2" align="right">
					<table width="135" border="0" cellpadding="2" cellspacing="0">
					<tr>
					<td width="135" align="center" valign="top"><script src=https://seal.verisign.com/getseal?host_name=asp.schoolmessenger.com&size=S&use_flash=NO&use_transparent=NO&lang=en></script><br />
					<a href="http://www.verisign.com/ssl-certificate/" target="_blank"  style="color:#000000; text-decoration:none; font:bold 7px verdana,sans-serif; letter-spacing:.5px; text-align:center; margin:0px; padding:0px;">About SSL Certificates</a></td>
					</tr>
					</table>
				</td></tr>
<? } /*CSDELETEMARKER_END*/ ?>
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

</script>
<html>