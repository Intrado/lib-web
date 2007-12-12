<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");


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
		$password1 = get_magic_quotes_gpc() ? trim(stripslashes($_POST['password1'])) : trim($_POST['password1']);
		$password2 = get_magic_quotes_gpc() ? trim(stripslashes($_POST['password2'])) : trim($_POST['password2']);
		$result = portalPreactivateForgottenPassword($token);
		if($result['result'] == ""){
			$user = $result['portaluser'];
			if($password1 !== $password2){
				error("The passwords do not match");
			} else if(strlen($password1) < 5){
				error("Passwords must be at least 5 characters long");
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
			}
		} else {
			$error = true;
		}

	} else {
		error("You are missing required fields");
	}
}

if($forgot){
	$TITLE = "Password Assistance";
	$action = "?f";
	$text = "your new password.  Passwords must be 5 characters in length and cannot be similiar to your first name, last name, or email address";
} else if($changeuser){
	$TITLE = "Change Email";
	$action = "?c";
	$text = "your password";
} else {
	$TITLE = "Activate Account";
	$action = "?n";
	$text = "your password";
}
include("cmlogintop.inc.php");

if($forgotsuccess || $success || $newusersuccess){
?>
	<table style="color: #365F8D;">
		<tr>
			<td width="20%">&nbsp;</td>
			<td>
<?
}
if($forgotsuccess){
	?>
	<div style="margin:5px">
		Thank you, your password has been reset.
		<br>You will be redirected to the main page in 5 seconds.
	</div>
	<meta http-equiv="refresh" content="5;url=choosecustomer.php">
	<?
} else if($success){
	?>
	<div style="margin:5px">
		Thank you, your account has been activated.
		<br>You will be redirected to the main page in 5 seconds.
	</div>
	<meta http-equiv="refresh" content="5;url=index.php">
	<?
} else if($newusersuccess){
	?>
	<div style="margin:5px">
		Thank you, your email address has been changed.
		<br>You will be redirected to the main page in 5 seconds.
	</div>
	<meta http-equiv="refresh" content="5;url=index.php">
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
		<table  style="color: #365F8D;" width="100%">
			<tr>
				<td width="20%">&nbsp;</td>
				<td colspan="2"><div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div></td>
				<td width="80%">&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2">You should have recieved an email containing a confirmation code. Please enter it below along with <?=$text?>.<br></td>
			</tr>
			
			<tr>
				<td>&nbsp;</td>
				<td>Confirmation Code: </td>
				<td><input type="text" name="token" value="<?=htmlentities($token)?>" size="35" /></td>
				<td>&nbsp;</td>
			</tr>
<?
		if($forgot){
?>
			<tr>
				<td>&nbsp;</td>
				<td>New Password:</td>
				<td><input type="password" name="password1"  size="35" maxlength="50" /></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>Confirm Password:</td>
				<td><input type="password" name="password2"  size="35" maxlength="50" /></td>
				<td>&nbsp;</td>
			</tr>
<?
		} else {
?>
			<tr>
				<td>&nbsp;</td>
				<td>Password:</td>
				<td><input type="password" name="password"  size="35" maxlength="50"/></td>
				<td>&nbsp;</td>
			</tr>
<?
		}
?>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td><?=submit("activate", "main", "Submit")?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>
<?
		if ($error && $forgot){
?>
			<div style="color: red;">That code is invalid or has expired.</div>
<?
		} else if ($error){
?>
			<div style="color: red;">That code is invalid or has expired or that is an incorrect password.</div>
<?
		} else {
			echo "&nbsp;";
		}
?>
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td><a href="index.php">Return to Sign In</a></td>
			<td>&nbsp;</td>
		</tr>
		</table>
	</form>
<?
}

include_once("cmloginbottom.inc.php");
?>