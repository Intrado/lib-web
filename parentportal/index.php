<?
if (!isset($_SESSION['_locale']))
	$_SESSION['_locale'] = isset($_COOKIE['locale'])?$_COOKIE['locale']:"en_US";

$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/form.inc.php");

$appendcustomerurl = getAppendCustomerUrl();

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
	portalputSessionData(session_id(), ""); // write empty data to flush the user

	@session_destroy();
}

if (isset($_GET['locale'])) {
	setcookie('locale', $_GET['locale']);
	redirect();
}

if (isset($_GET['deletelocale'])) {
	setcookie('locale', '');
	redirect();
}

// force ssl
if ($SETTINGS['feature']['has_ssl'] && $SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
	$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/index.php".$appendcustomerurl;
	// forward all params
	if (count($_GET) > 0) {
		if ($appendcustomerurl == "")
			$secureurl .= "?" . http_build_query($_GET);
		else
			$secureurl .= "&" . http_build_query($_GET);
	}
	redirect($secureurl);
}

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
		$redirpage = isset($_SESSION['lasturi']) ? $_SESSION['lasturi'] : 'choosecustomer.php'.$appendcustomerurl;
		unset($_SESSION['lasturi']);
		redirect($redirpage);
    }
}
if($id){
	if (!$sessionstarted)
		doStartSession();
	$_SESSION['portaluserid'] = $id;
	$_SESSION['colorscheme']['_brandtheme'] = "3dblue";
	$_SESSION['colorscheme']['_brandprimary'] = "26477D";
	$_SESSION['colorscheme']['_brandtheme1'] = "89A3CE";
	$_SESSION['colorscheme']['_brandtheme2'] = "89A3CE";
	$_SESSION['colorscheme']['_brandratio'] = ".3";
	
	redirect("choosecustomer.php".$appendcustomerurl);
}

PutFormData("login", "main", "_locale", isset($LOCALE)?$LOCALE:"en_US", "text", "nomin", "nomax");

$TITLE= _L("Sign In");

include_once("cmlogintop.inc.php");
?>
<form method="POST" action="index.php<?echo $appendcustomerurl;?>" name="login">

				<span class="language"> 
				<?
					// if no customerurl, need to include the ?, otherwise append with &
					$urlparams = (strlen($appendcustomerurl) == 0) ? "?locale=" : $appendcustomerurl . "&locale=";
					NewFormItem("login", "main", '_locale', 'selectstart', null, null, "id='locale' onchange='window.location.href=\"index.php" . $urlparams . "\"+this.options[this.selectedIndex].value'");
					foreach($LOCALES as $loc => $lang){
						NewFormItem("login", "main", '_locale', 'selectoption', $lang, $loc);
					}
					NewFormItem("login", "main", '_locale', 'selectend');
				?>
				</span>


				<noscript><p><?=_L("It looks like you don't have JavaScript enabled! You must have JavaScript enabled for full use of this system. Please enable JavaScript in your browser or contact your system administrator for assistance.")?></p></noscript>

<?
				if ($badlogin) {
				?>
					<p style="color: red;"><?=_L("Incorrect username/password. Please try again.")?></p>
				<?
				}
?>

			<fieldset>
				<label for="form_email"><?=_L("Email")?>:</label>
				<input type="text" id="form_email" name="login" size="30" maxlength="255" value="<?=escapehtml($login)?>"/>
			</fieldset>

			<fieldset>
				<label for="form_pass"><?=str_replace(" ", "&nbsp;", _L("Password"))?>:</label>
				<input type="password" id="form_pass" name="password" size = "30" maxlength="50" onkeypress="capslockCheck(event)"/>
				<em>Passwords are case-sensitive.</em>
			</fieldset>
			
			<div id="capslockwarning"  style="padding-left:3px; float:left; display:none; color:red;"><?=_L("Warning! Your Caps Lock key is on.")?></div>

			<fieldset>
				<input type="submit" name="signin" value="<?=_L("Sign In")?>">
			</fieldset>
			
			<p class="right"><a href="forgotpassword.php<?echo $appendcustomerurl;?>"><?=_L("Forgot your password? Click Here")?></a></p>


			<p><?=_L("First time accessing the SchoolMessenger Contact Manager?")?>	
			<a href="newportaluser.php<?echo $appendcustomerurl;?>"><b><?=_L("Sign up now")?></b></a></p>


</form>

<?
include("cmloginbottom.inc.php");
?>
<script type="text/javascript">
document.getElementById('form_email').focus();

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
			$('capslockwarning').style.display = 'block';
		} else
			$('capslockwarning').style.display = 'none';
	}

</script>
<noscript>
	<?= escapehtml(_L("It looks like you don't have JavaScript enabled! You must have JavaScript enabled for full use of this system. Please enable JavaScript in your browser or contact your system administrator for assistance.")) ?>
</noscript>