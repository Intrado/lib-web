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
	$_SESSION = array(); //remove any previous session data
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
		
		$redirpage = isset($_GET['last']) ? $_GET['last'] : 'start.php';
		redirect($redirpage);
	}
}

$custname = getCustomerName($CUSTOMERURL); // also found by getSystemSetting("displayname") but we may not be logged in yet

$TITLE=_L("Login");
//primary colors are pulled in login top
include_once("logintop.inc.php");


?>
	<form action="index.php<?= (isset($_GET['last']) ? "?last=" . $_GET['last'] : '') ?>" method="POST">

<? if ($custname) { ?>

		<noscript><p><?=_L("It looks like you don't have JavaScript enabled! You must have JavaScript enabled for full use of this system. Please enable JavaScript in your browser or contact your system administrator for assistance.")?></p></noscript>

<? if ($badlogin) { ?>
		<p class="error"><?=_L("Incorrect username/password. Please try again.")?></p>
<? } else if ($softlock) { ?>
		<p class="error"><?=_L("You are temporarily locked out of the system.  Please contact your System Administrator if you have forgotten your password and try again later.")?></p>
<? }  ?>
		
		<fieldset>
		<label class="indexform" for="form_login"><?=_L("Login:")?></label>
		<input type="text" name="login" id="form_login" size="20" maxlength="20" />
		</fieldset>

		<fieldset>
		<label class="indexform" for="form_password"><?=_L("Password:")?></label>
		<input type="password" name="password" id="form_password" size="20" onkeypress="capslockCheck(event)" />
		<em><?=_L("Passwords are case-sensitive.")?></em>
		</fieldset>
		
		<div id="capslockwarning" style="display:none;"><?=_L("Warning! Your Caps Lock key is on.")?></div>

		<fieldset>
		<input type="submit" name="Submit" value="Sign In">
		</fieldset>

		<p class="right"><a href="forgotpassword.php"><?=_L("Forgot your password? Click Here")?></a></p>


<? } else { ?>
		<p>&nbsp;&nbsp;<?=_L("Invalid customer URL. Please check the web address and try again.")?></p>
<? }?>
	</form>
<?

if (!($custname === false)) { 
?>
	<script type="text/javascript">

	new getObj('form_login').obj.focus();

	</script>
<?
}

include_once("loginbottom.inc.php");
