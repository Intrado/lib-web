<?
$isindexpage = true;
require_once("inc/common.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");

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

$scheme = getCustomerData($CUSTOMERURL);
if($scheme == false){
	$scheme = array("_brandtheme" => "3dblue",
					"_supportemail" => "support@schoolmessenger.com",
					"_supportphone" => "800.920.3897",
					"colors" => array("_brandprimary" => "26477D"));
}
$CustomBrand = isset($scheme['productname']) ? $scheme['productname'] : "" ;
$primary = $scheme['colors']['_brandprimary'];

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
		if (!isset($_SESSION['etagstring'])){
				$_SESSION['etagstring'] = mt_rand();
		}
		$userprefs = array();
		$userprefs['_brandprimary'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandprimary'");
		$userprefs['_brandtheme1'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandtheme1'");
		$userprefs['_brandtheme2'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandtheme2'");
		$userprefs['_brandratio'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandratio'");
		$userprefs['_brandtheme'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandtheme'");

		if($userprefs['_brandprimary']){
			$_SESSION['colorscheme'] = $userprefs;
		} else {
			$_SESSION['colorscheme'] = array("_brandtheme" => $scheme['_brandtheme'],
										"_brandprimary" => $scheme['colors']['_brandprimary'],
										"_brandtheme1" => $scheme['colors']['_brandtheme1'],
										"_brandtheme2" => $scheme['colors']['_brandtheme2'],
										"_brandratio" => $scheme['colors']['_brandratio']);
		}

		$_SESSION['productname'] = isset($scheme['productname']) ? $scheme['productname'] : "" ;
		$_SESSION['_supportphone'] = $scheme['_supportphone'];
		$_SESSION['_supportemail'] = $scheme['_supportemail'];
		redirect("start.php");
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
		<td bgcolor="#365F8D" align="center"><div id='orgtitle' style='margin-top: 3px; margin-right: 10px; font-size: large; display: inline; float: right; color: white;'><?= htmlentities($custname) ?></div></td>
	</tr>
	<tr>
		<td colspan="2">
			<table border="0" cellpadding="10" cellspacing="0" id="login" align="center">
				<tr>
					<td colspan="2">
<?if ($badlogin) { ?>
						<div style="color: red;">Incorrect username/password. Please try again.</div>
<? } else if ($softlock) { ?>
						<div style="color: red;">You are temporarily locked out of the system.  Please contact your System Administrator if you have forgotten your password and try again later.</div>
<? } else if ($custname === false) { ?>
						<div style="color: red;">Invalid customer URL. Please check the URL and try again.</div>
<? } else { ?>
						Please log in here.
<? } ?>
					</td>
				</tr>
				<tr><td align="right" style="padding: 2px;" width="165">Login:</td><td><input type="text" name="login" size="35" id="logintext"></td></tr>
				<tr><td align="right" style="padding: 2px;">Password:</td><td><input type="password" name="password" size="35"></td></tr>
				<tr><td><div style="text-align: right;"><input type="image" src="img/themes/<?=$scheme['_brandtheme']?>/b1_signin_dark.gif" onmouseover="this.src='img/themes/<?=$scheme['_brandtheme']?>/b2_signin_dark.gif';" onmouseout="this.src='img/themes/<?=$scheme['_brandtheme']?>/b1_signin_dark.gif';"></div></td></tr>
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
<title><?=$CustomBrand?> Login</title>

</head>
<body style='font-family: "Lucida Grande", verdana, arial, helvetica, sans-serif; margin: 0px; background-color: #<?=$primary?>;'>
<form action="index.php" method="POST">

<table border=0 cellpadding=0 cellspacing=0 width="100%">
<tr style="background-color: #FFFFFF;">
	<td width="389"><div style="padding-left:5px; padding-bottom:5px;"><img src="logo.img.php" /></div></td>
	<td width="100%">&nbsp;</td>
</tr>
<tr style="background-color: #666666;">
	<td colspan="2">&nbsp;</td>
</tr>
<tr>
	<? // img/classroom_girl.jpg ?>
	<td style="background-color: #D4DDE2;"><img src="loginpicture.img.php"></td>
	<td style="background-color: #D4DDE2; color: #<?=$primary?>;">

		<table width="100%" style="color: #<?=$primary?>; text-align: right;">
			<tr>
				<td width="100%" style="font-size: 18px; font-weight: bold; text-align: right;"><?= htmlentities($custname) ?></div></td>
				<td><img src="img/spacer.gif" width="25"></td>
			</tr>

			<tr>

<? if ($badlogin) { ?>
				<td style="font-size: 12px; font-weight: bold; color: red;">Incorrect username/password. Please try again.</td>
<? } else if ($softlock) { ?>
				<td style="font-size: 12px; font-weight: bold; color: red;">You are temporarily locked out of the system.  Please contact your System Administrator if you have forgotten your password and try again later.</td>
<? } else if ($custname === false) { ?>
				<td style="font-size: 12px; font-weight: bold; color: red;">Invalid customer URL. Please check the URL and try again.</td>
<? } else { ?>
				<td>&nbsp;</td>
<? } ?>

				<td>&nbsp;</td>
			</tr>
		</table>

		<div><table width="100%" style="color: #<?=$primary?>;" >
			<tr>
				<td width="20%">&nbsp;</td>
				<td style="font-size: 12px;"><div style="margin-left: 50px;">Login:<br><input type="text" name="login" size="35" id="logintext"></div></td>
				<td width="80%">&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td style="font-size: 12px;"><div style="margin-left: 50px;">Password:<br><input type="password" name="password" size="35" onkeypress="capslockCheck(event)"></div></td>
				<td style="font-size: 12px;"><br><div id="capslockwarning"  style="padding-left:3px; float:left; display:none; color:red;">Warning! Your Caps Lock key is on.</div></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><div style="text-align: right;"><input type="image" src="img/themes/<?=$scheme['_brandtheme']?>/b1_signin_dark.gif" onmouseover="this.src='img/themes/<?=$scheme['_brandtheme']?>/b2_signin_dark.gif';" onmouseout="this.src='img/themes/<?=$scheme['_brandtheme']?>/b1_signin_dark.gif';"></div></td>
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

<tr style="background-color: #<?=$primary?>; color: white;">
	<td colspan="2"><div style="text-align:right; font-size: 12px; margin: 5px;">
		<p>Service & Support:</p>
		<p><a style="color: white;" href="mailto:<?=$scheme['_supportemail']?>"><?=$scheme['_supportemail']?></a>&nbsp;|&nbsp;<?=substr($scheme['_supportphone'],0,3) . "." . substr($scheme['_supportphone'],3,3) . "." . substr($scheme['_supportphone'],6,4);?></p>
	</div></td>
</tr>

</form>

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

</body>
</html>


<?
} /*CSDELETEMARKER_END*/