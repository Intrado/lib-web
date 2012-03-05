<?
if (!isset($_SESSION['_locale']))
	$_SESSION['_locale'] = isset($_COOKIE['locale'])?$_COOKIE['locale']:"en_US";

$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/form.inc.php");

$appendcustomerurl = getAppendCustomerUrl();

// force ssl
if ($SETTINGS['feature']['has_ssl'] && $SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
	$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/forgotpassword.php".$appendcustomerurl;
	// forward all params
	if (count($_GET) > 0) {
		if ($appendcustomerurl == "")
			$secureurl .= "?" . http_build_query($_GET);
		else
			$secureurl .= "&" . http_build_query($_GET);
	}
	redirect($secureurl);
}

if (isset($_GET['locale'])) {
	setcookie('locale', $_GET['locale']);
	redirect();
}

if (isset($_GET['deletelocale'])) {
	setcookie('locale', '');
	redirect();
}

$success = false;
$emailnotfound = false;
$generalerror = false;
$email1 = "";
$email2 = "";
if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$email1 = get_magic_quotes_gpc() ? stripslashes($_POST['email1']) : $_POST['email1'];
	$email2 = get_magic_quotes_gpc() ? stripslashes($_POST['email2']) : $_POST['email2'];
	if ($email1 !== $email2){
		error(_L("The 2 emails you have entered do not match"));
	} else if(!validEmail($email1)){
		error(_L("That is not a valid email address"));
	} else {
		$result = portalForgotPassword($email1);
		if($result['result'] == ""){
			$success = true;
		} else {
			if($result['result'] == "invalid argument"){
				$success = true;
			} else {
				$generalerror = true;
			}
		}
	}
}

PutFormData("login", "main", "_locale", isset($LOCALE)?$LOCALE:"en_US", "text", "nomin", "nomax");

$TITLE = _L("Password Assistance");
include_once("cmlogintop.inc.php");
if($generalerror){
	error(_L("There was a problem with your request.  Please try again later"));
}

if(!$success){
?>
<form method="POST" action="forgotpassword.php<?echo $appendcustomerurl;?>" name="forgotpassword">

					<span class="language"> 
					<?
						// if no customerurl, need to include the ?, otherwise append with &
						$urlparams = (strlen($appendcustomerurl) == 0) ? "?locale=" : $appendcustomerurl . "&locale=";
						NewFormItem("login", "main", '_locale', 'selectstart', null, null, "id='locale' onchange='window.location.href=\"forgotpassword.php" . $urlparams . "\"+this.options[this.selectedIndex].value'");
						foreach($LOCALES as $loc => $lang){
							NewFormItem("login", "main", '_locale', 'selectoption', $lang, $loc);
						}
						NewFormItem("login", "main", '_locale', 'selectend');
					?>
					</span>
					
			<h3><?=$TITLE?></h3>

			<p><?=_L("To begin the password reset process, enter your email address.")?></p>

			<fieldset>
				<label for="form_email">Email:</label></td>
				<input type="text" id="form_email" name="email1" size="50" maxlength="255" value="<?=escapehtml($email1)?>">
			</fieldset>

			<fieldset>
				<label for="form_confirm">Confirm Email:</label></td>
				<input type="text" id="form_confirm" name="email2" size="50" maxlength="255" value="<?=escapehtml($email2)?>">
			</fieldset>

			<fieldset>
				<input type="submit" name="submit" value="<?=_L("Submit")?>">
			</fieldset>

			<p class="right"><a href="index.php<?echo $appendcustomerurl;?>"><?=_L("Return to Sign In")?></a></p>

</form>

<?
} else {
?>
	<table  style="color: #365F8D;" >
		<tr>
			<td>&nbsp;</td>
			<td>
				<div style="margin:5px">
					<?=_L("Check your email to receive the password reset link.")?>
					<br><?=_L("You will be redirected to the Activation page in 10 seconds, or you can")?> <a href="index.php<?echo $appendcustomerurl; if ($appendcustomerurl == "") echo "?f"; else echo "&f"; ?>"><?=_L("Click Here to continue.")?></a>
				</div>
				<meta http-equiv="refresh" content="10;url=index.php<?echo $appendcustomerurl; if ($appendcustomerurl == "") echo "?f"; else echo "&f"; ?>">
			</td>
		</tr>
	</table>
<?
}
include_once("cmloginbottom.inc.php");
?>