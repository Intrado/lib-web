<?
//shortcut for messagelinks
if(isset($_GET['s'])){
	include("messagelink.php");
	exit();
}

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
	
	//FIXME fix session_destroy then we don't need to write empty string
	putSessionData(session_id(), ""); // write empty data to flush the user

	@session_destroy();
}

// force ssl
if ($SETTINGS['feature']['has_ssl'] && $SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
	$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/$CUSTOMERURL/index.php";
	// forward all params
	if (count($_GET) > 0)
		$secureurl .= "?" . http_build_query($_GET);
	redirect($secureurl);
}

//check various ways to log in
$badlogin = false;
$softlock = false;
$userid = false;
$updatelogin = false;
$sessionstarted = false;
if (isset($_GET['login'])) {
	$login = trim(get_magic_quotes_gpc() ? stripslashes($_GET['login']) : $_GET['login']);
	if($_GET['login'] == 'schoolmessenger'){
		@session_destroy();
		$badlogin = true;
	} else {
		doStartSession(); // we must start the session to obtain the user information before trying to perform the following IF conditions
		$sessionstarted = true;
		if (isset($_SESSION['user']) && is_object($_SESSION['user']) && $_SESSION['user']->authorize('manageaccount')) {
			$userid = forceLogin($login, $CUSTOMERURL);
		} else {
			$badlogin = true;
			error_log("FORCE login failed");
		}

	}

} else if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$f_login = trim(get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login']);
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
		$redirpage = isset($_GET['last']) ? $_GET['last'] : 'start.php';
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


?>
	<form action="index.php" method="POST">

<? if ($custname) { ?>

	<div style="margin-top: 25px; margin-left: 25px;">
		<div class="indexdisplayname"><?=escapehtml($custname)?></div>
		<noscript><p><?=_L("It looks like you don't have JavaScript enabled! You must have JavaScript enabled for full use of this system. Please enable JavaScript in your browser or contact your system administrator for assistance.")?></p></noscript>

<? if ($badlogin) { ?>
		<div style="font-size: 12px; font-weight: bold; color: red;"><?=_L("Incorrect username/password. Please try again.")?>
		</div>
<? } else if ($softlock) { ?>
		<div style="font-size: 12px; font-weight: bold; color: red;"><?=_L("You are temporarily locked out of the system.  Please contact your System Administrator if you have forgotten your password and try again later.")?></div>
<? }  ?>
		
		<label class="indexform"><?=_L("Login:")?><br>
		<input type="text" name="login" size="20" maxlength="20" id="logintext">
		</label>
		
		<br>


		<label class="indexform"><?=_L("Password:")?><br>
		<input type="password" name="password" size="20" onkeypress="capslockCheck(event)">
		</label>
		<div id="capslockwarning"  style="padding-left:3px; display:none; color:red;"><?=_L("Warning! Your Caps Lock key is on.")?><br></div>

		<br>
		
		<input type="submit" name="Submit" value="Sign In">



		<div style="margin-left: 50px; font-size: 9px; font-style: italic;"><?=_L("Passwords are case-sensitive.")?></div>

		<br><div style="font-size: 10pt;"><a href="forgotpassword.php"><?=_L("Forgot your password? Click Here")?></a><br></div>
	</div>

<? } else { ?>
		<div width="100%" style="font-size: 16px; font-weight: bold; color: red;">&nbsp;&nbsp;<?=_L("Invalid customer URL. Please check the web address and try again.")?></div>
<? }?>
	</form>
<?

if (!($custname === false)) { 
?>
	<script type="text/javascript">

	new getObj('logintext').obj.focus();

	</script>
<?
}

include_once("loginbottom.inc.php");
