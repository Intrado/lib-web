<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");

// pass along the customerurl (used by phone activation feature to find a customer without any existing associations)
$appendcustomerurl = "";
if (isset($_GET['u'])) {
	$appendcustomerurl = "?u=".urlencode($_GET['u']);
}

$form = true;
$forgotsuccess = false;
$newusersuccess = false;
$token = "";
$success = false;
$error = false;
$result = null;
if(isset($_GET['t'])){
	$token = $_GET['t'];
}

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {

	$token = get_magic_quotes_gpc() ? stripslashes($_POST['token']) : $_POST['token'];
	if(isset($_POST['password1']) && isset($_POST['password2'])){
		$password1 = get_magic_quotes_gpc() ? stripslashes($_POST['password1']) : $_POST['password1'];
		$password2 = get_magic_quotes_gpc() ? stripslashes($_POST['password2']) : $_POST['password2'];
		$result = portalPreactivateForgottenPassword($token);
		if($result['result'] == ""){
			$user = $result['portaluser'];
			if($password1 !== $password2){
				error(_L("The passwords do not match"));
			} else if(strlen($password1) < 5){
				error(_L("Passwords must be at least 5 characters long"));
			} else if($password1 && $passworderror = validateNewPassword($user['portaluser.username'], $password1, $user['portaluser.firstname'], $user['portaluser.lastname'])){
				error($passworderror);
			} else {
				$result = portalActivateAccount($token, $password1);
				if($result['result'] == ""){
					if(!$forgot && $result['functionCode'] != 'forgotpassword'){
						error("An unknown error occurred");
						$error = true;
					} else {
						$form = false;
						$forgotsuccess = true;
						doStartSession();
						$_SESSION['portaluserid'] = $result['userID'];
						$_SESSION['colorscheme']['_brandtheme'] = "3dblue";
					}
				} else {
					$error = true;
				}
			}
		} else {
			$error = true;
		}
	} else if(isset($_POST['password'])){
		$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];
		$result = portalActivateAccount($token, $password);
		if($result['result'] == ""){
			$form = false;
			if($result['functionCode'] == 'newaccount'){
				$success = true;
			} else if ($result['functionCode'] == 'changeusername' && $changeuser){
				$newusersuccess = true;
			} else {
				error("An unknown error occurred");
				$error = true;
			}
			if(!$error){
				doStartSession();
				$_SESSION['portaluserid'] = $result['userID'];
				$_SESSION['colorscheme']['_brandtheme'] = "3dblue";
			}
		} else {
			$error = true;
		}

	} else {
		error("You are missing required fields");
	}
}

if ($forgot) {
	$TITLE = "Password Assistance";
	if ($appendcustomerurl == "")
		$action = "?f";
	else
		$action = $appendcustomerurl."&f";
	$text = _L("Please enter it below along with your new password.  Passwords must be 5 characters in length and cannot be similiar to your first name, last name, or email address");
} else if ($changeuser) {
	$TITLE = "Change Email";
	if ($appendcustomerurl == "")
		$action = "?c";
	else
		$action = $appendcustomerurl."&c";
	$text = _L("Please enter it below along with your password");
} else {
	$TITLE = "Activate Account";
	if ($appendcustomerurl == "")
		$action = "?n";
	else
		$action = $appendcustomerurl."&n";
	$text = _L("Please enter it below along with your password");
}
include("cmlogintop.inc.php");

if($forgotsuccess || $success || $newusersuccess){
?>
	<table style="color: #365F8D;">
		<tr>
			<td>&nbsp;</td>
			<td>
<?
}
if($forgotsuccess){
	?>
	<div style="margin:5px">
		<?=_L("Thank you, your password has been reset.")?>
		<br><?=_L("You will be redirected to the main page in 10 seconds or")?> <a href="choosecustomer.php<?echo $appendcustomerurl;?>"><?=_L("Click Here.")?></a>
	</div>
	<meta http-equiv="refresh" content="10;url=choosecustomer.php<?echo $appendcustomerurl;?>">
	<?
} else if($success){
	?>
	<div style="margin:5px">
		<?=_L("Thank you, your account has been activated.")?>
		<br><?=_L("You will be redirected to the main page in 10 seconds or")?> <a href="index.php<?echo $appendcustomerurl;?>"><?=_L("Click Here.")?></a>
	</div>
	<meta http-equiv="refresh" content="10;url=index.php<?echo $appendcustomerurl;?>">
	<?
} else if($newusersuccess){
	?>
	<div style="margin:5px">
		<?=_L("Thank you, your email address has been changed.")?>
		<br><?=_L("You will be redirected to the main page in 10 seconds or")?> <a href="index.php<?echo $appendcustomerurl;?>"><?=_L("Click Here.")?></a>
	</div>
	<meta http-equiv="refresh" content="10;url=index.php<?echo $appendcustomerurl;?>">
	<?
}
if($forgotsuccess || $success || $newusersuccess){
?>
			</td>
		</tr>
	</table>
<?
}

if($form){
?>
	<form method="POST" action="<?=$action?>" name="activate">
		<table  style="color: #365F8D;">
			<tr>
				<td colspan="2"><div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div></td>
			</tr>
			<tr>
				<td colspan="2"><?=_L("You should have received an email containing a confirmation code.")?> <?=$text?>.<br></td>
			</tr>

			<tr>
				<td><?=_L("Confirmation Code")?>: </td>
				<td><input type="text" name="token" value="<?=escapehtml($token)?>" size="35" /></td>
			</tr>
<?
		if($forgot){
?>
			<tr>
				<td><?=_L("New Password")?>:</td>
				<td><input type="password" name="password1"  size="35" maxlength="50" /></td>
			</tr>
			<tr>
				<td><?=_L("Confirm Password")?>:</td>
				<td><input type="password" name="password2"  size="35" maxlength="50" /></td>
			</tr>
<?
		} else {
?>
			<tr>
				<td><?=_L("Password")?>:</td>
				<td><input type="password" name="password"  size="35" maxlength="50"/></td>
			</tr>
<?
		}
?>
		<tr>
			<td>&nbsp;</td>
			<td><div><input type="submit" name="submit" value="Submit"></div></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>
<?
		if ($error && $forgot){
?>
			<div style="color: red;"><?=_L("That code is invalid or has expired.")?></div>
<?
		} else if ($error){
?>
			<div style="color: red;"><?=_L("That code is invalid or has expired or that is an incorrect password.")?></div>
<?
		} else {
			echo "&nbsp;";
		}
?>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><a href="index.php<?echo $appendcustomerurl;?>"><?=_L("Return to Sign In")?></a></td>
		</tr>
		</table>
	</form>
<?
}

include_once("cmloginbottom.inc.php");
?>