<?
if (!isset($_SESSION['_locale']))
	$_SESSION['_locale'] = isset($_COOKIE['locale'])?$_COOKIE['locale']:"en_US";

$ppNotLoggedIn = 1;

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/form.inc.php");
require_once("../obj/Phone.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$appendcustomerurl = getAppendCustomerUrl();

if (isset($_GET['locale'])) {
	setcookie('locale', $_GET['locale']);
	redirect();
}

if (isset($_GET['deletelocale'])) {
	setcookie('locale', '');
	redirect();
}

$login = "";
$confirmlogin="";
$firstname = "";
$lastname = "";
$zipcode = "";
$notify = 0;
$notifysms = 0;
$sms = "";
$success = false;
$tos = file_get_contents(isset($LOCALE)?"./locale/$LOCALE/terms.html":"./locale/en_US/terms.html");

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$login = trim(get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login']);
	$confirmlogin = trim(get_magic_quotes_gpc() ? stripslashes($_POST['confirmlogin']) : $_POST['confirmlogin']);
	$firstname = get_magic_quotes_gpc() ? stripslashes($_POST['firstname']) : $_POST['firstname'];
	$lastname = get_magic_quotes_gpc() ? stripslashes($_POST['lastname']) : $_POST['lastname'];
	$zipcode = get_magic_quotes_gpc() ? stripslashes($_POST['zipcode']) : $_POST['zipcode'];
	$password1 = get_magic_quotes_gpc() ? stripslashes($_POST['password1']) : $_POST['password1'];
	$password2 = get_magic_quotes_gpc() ? stripslashes($_POST['password2']) : $_POST['password2'];
	$notify = isset($_POST['notify']) ? $_POST['notify'] : 0;
	$notifysms = isset($_POST['notifysms']) ? $_POST['notifysms'] : 0;
	$sms = "";
	if (isset($_POST['sms'])) {
		$sms = get_magic_quotes_gpc() ? stripslashes($_POST['sms']) : $_POST['sms'];
	}
	$acceptterms = isset($_POST['acceptterms']);
	if($login != $confirmlogin){
		error(_L("The emails you have entered do not match"));
	} else if(!validEmail($login)){
		error(_L("That is not a valid email format"));
	} else if(!ereg("^[0-9]*$",$zipcode)){
		error(_L("Zip code must be a 5 digit number"));
	} else if(strlen($zipcode) != 5){
		error(_L("Zip code must be a 5 digit number"));
	} else if(strlen($firstname) == 0){
		error(_L("You must enter a First Name"));
	} else if(strlen($lastname) == 0){
		error(_L("You must enter a Last Name"));
	} else if($_POST['password1'] == ""){
		error(_L("You must enter a password"));
	} else if($password1 != $password2){
		error(_L("Your passwords don't match"));
	} else if(strlen($password1) < 5){
		error(_L("Passwords must be at least 5 characters long"));
	} else if($passworderror = validateNewPassword($login, $password1, $firstname, $lastname)){
		error($passworderror);
	} else if ($notifysms && $phoneerror = Phone::validate($sms)) {
		error($phoneerror);
	} else {
		if ($notify) {
			$notifyType = "message";
		} else {
			$notifyType = "none";
		}
		if ($notifysms) {
			$notifysmsType = "message";
			$sms = Phone::parse($sms);
		} else {
			$notifysmsType = "none";
			$sms = "";
		}
		
		$preferences = array('_locale' => $LOCALE);
		
		$result = portalCreateAccount($login, $password1, $firstname, $lastname, $zipcode, $notifyType, $notifysmsType, $sms, $preferences);
		if($result['result'] != ""){
			if($result['result'] == "duplicate"){
				$errordetails = "That email address is already in use";
			} else {
				$errordetails = "An unknown error occurred, please try again";
			}
			error("Your account was not created", $errordetails);
		} else {
			$success = true;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
PutFormData("login", "main", "_locale", isset($LOCALE)?$LOCALE:"en_US", "text", "nomin", "nomax");

$TITLE = _L("Create a New Account");
include_once("cmlogintop.inc.php");
if(!$success){
?>
	<form method="POST" action="newportaluser.php<?echo $appendcustomerurl;?>" name="newaccount" onsubmit='if(!(new getObj("tos").obj.checked)){ window.alert("You must accept the Terms of Service."); return false;}'>
		<table width="100%" style="color: #365F8D;" >
			<tr>
				<td colspan="2">
					<div style="font-size: 20px; font-weight: bold; text-align: left; float: left;"><?=$TITLE?></div>
					<div style="float:right;"> 
					<?
						// if no customerurl, need to include the ?, otherwise append with &
						$urlparams = (strlen($appendcustomerurl) == 0) ? "?locale=" : $appendcustomerurl . "&locale=";
						NewFormItem("login", "main", '_locale', 'selectstart', null, null, "id='locale' onchange='window.location.href=\"newportaluser.php" . $urlparams . "\"+this.options[this.selectedIndex].value'");
						foreach($LOCALES as $loc => $lang){
							NewFormItem("login", "main", '_locale', 'selectoption', $lang, $loc);
						}
						NewFormItem("login", "main", '_locale', 'selectend');
					?>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2"><?=_L("Please complete this form to create your Contact Manager account.  A confirmation code will be sent to activate your new account so a valid email address is required.  Your password must be at least 5 characters long and cannot be similar to your first name, last name, or email address.")?></td>
			</tr>
			<tr>
				<td colspan="4">&nbsp;</td>
			</tr>
			<tr>
				<td><?=str_replace(" ", "&nbsp;", _L("Email (this will be your login name)"))?>:</td>
				<td><input type="text" name="login" value="<?=escapehtml($login)?>" size="50" maxlength="255"/> </td>
			</tr>
			<tr>
				<td><?=_L("Confirm Email")?>:</td>
				<td><input type="text" name="confirmlogin" value="<?=escapehtml($confirmlogin)?>" size="50" maxlength="255"/> </td>
			</tr>
			<tr>
				<td><?=_L("Password")?>: </td>
				<td><input type="password" name="password1"  size="35" maxlength="50"/> </td>
			</tr>
			<tr>
				<td><?=_L("Confirm Password")?>: </td>
				<td><input type="password" name="password2"  size="35" maxlength="50"/> </td>
			</tr>
			<tr>
				<td><?=_L("First Name")?>:</td>
				<td><input type="text" name="firstname" value="<?=escapehtml($firstname)?>" maxlength="100"/></td>
			</tr>
			<tr>
				<td><?=_L("Last Name")?>:</td>
				<td><input type="text" name="lastname" value="<?=escapehtml($lastname)?>" maxlength="100"/></td>
			</tr>
			<tr>
				<td><?=_L("ZIP Code")?>:</td>
				<td><input type="text" name="zipcode" value="<?=escapehtml($zipcode)?>" size="5" maxlength="5"/></td>
			</tr>
			<tr>
				<td colspan="2"><input type="checkbox" name="notify" value="1" <?=$notify ? "checked" : "" ?>/>&nbsp;<?=_L("Email me when I have a new phone message.")?></td>
			</tr>
			<tr>
				<td colspan="2"><input type="checkbox" name="notifysms" value="1" <?=$notifysms ? "checked" : "" ?> onclick="document.getElementById('smsbox').disabled=!this.checked"/>&nbsp;<?=_L("Text me when I have a new phone message.")?></td>
			</tr>
			<tr>
				<td><?=_L("Mobile Phone for SMS Text")?>:</td>
				<td><input type="text" name="sms" id="smsbox" value="<?=escapehtml(Phone::format($sms))?>" size="20" maxlength="20" <?=$notifysms ? "" : "disabled=\"true\"" ?>/></td>
			</tr>
			<tr>
				<td colspan="2"><div style="overflow:scroll; height:250px; width:525px;"><?=$tos ?></div></td>
			</tr>
			<tr>
				<td colspan="2"><input type="checkbox" name="acceptterms" id="tos"/> <?=_L("Accept Terms of Service")?></td>
			</tr>
			<tr>
				<td colspan="2"><div><input type="submit" name="createaccount" value="<?=_L("Create Account")?>"></div></td>
			</tr>
			<tr>
				<td colspan="2"><br><a href="index.php<?echo $appendcustomerurl;?>"><?=_L("Return to Sign In")?></a></td>
			</tr>
		</table>
	</form>
<?
} else {
?>
	<table style="color: #365F8D;">
		<tr>
			<td>&nbsp;</td>
			<td>
				<div style="margin:5px">
					<?=_L("Thank you, Your account has been created.")?>
					<br><?=_L("Please check your email to activate your account.")?>
					<br><?=_L("You will be redirected to the activate page in 10 seconds.")?><br><a href="index.php<?echo $appendcustomerurl; if ($appendcustomerurl == "") echo "?n"; else echo "&n"; ?>"><?=_L("Click Here to redirect now.")?></a>
				</div>
				<meta http-equiv="refresh" content="10;url=index.php<?echo $appendcustomerurl; if ($appendcustomerurl == "") echo "?n"; else echo "&n"; ?>">
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
<?
}
include_once("cmloginbottom.inc.php");
?>