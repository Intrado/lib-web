<?
$isNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/FieldMap.obj.php");

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
if (isset($_GET['t'])) {
	$token = $_GET['t'];
}

if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {

	$token = get_magic_quotes_gpc() ? stripslashes($_POST['token']) : $_POST['token'];
	if(isset($_POST['password1']) && isset($_POST['password2'])){
		$password1 = get_magic_quotes_gpc() ? stripslashes($_POST['password1']) : $_POST['password1'];
		$password2 = get_magic_quotes_gpc() ? stripslashes($_POST['password2']) : $_POST['password2'];
		$result = subscriberPreactivateForgottenPassword($token);
		if($result['result'] == ""){
			if ($password1 !== $password2) {
				error("The passwords do not match");
			} else if (strlen($password1) < 5) {
				error("Passwords must be at least 5 characters long");
			} else if($password1 && $passworderror = validateNewPassword($result['subscriber.username'], $password1, $result['subscriber.firstname'], $result['subscriber.lastname'])) {
				error($passworderror);
			} else {
				$result = subscriberActivateAccount($token, $password1);
				if($result['result'] == ""){
					if (!$forgot && $result['functionCode'] != 'token_forgotpassword') {
						error("An unknown error occurred");
						$error = true;
					} else {
						$form = false;
						$forgotsuccess = true;
						doStartSession($result['userID']);
					}
				} else {
					$error = true;
				}
			}
		} else {
			$error = true;
		}
	} else if (isset($_POST['password'])) {
		$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];
		$result = subscriberActivateAccount($token, $password);
		if($result['result'] == ""){
			$form = false;
			if($result['functionCode'] == 'token_newsubscriber'){
				$success = true;
			} else if ($result['functionCode'] == 'token_changeemail' && $changeuser){
				$newusersuccess = true;
			} else {
				error("An unknown error occurred");
				$error = true;
			}
			if(!$error){
				doStartSession($result['userID']);
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
	$text = "your new password.  Passwords must be 5 characters in length and cannot be similiar to your first name, last name, or email address";
} else if ($changeuser) {
	$TITLE = "Change Email";
	if ($appendcustomerurl == "")
		$action = "?c";
	else
		$action = $appendcustomerurl."&c";
	$text = "your password";
} else {
	$TITLE = "Activate Account";
	if ($appendcustomerurl == "")
		$action = "?n";
	else
		$action = $appendcustomerurl."&n";
	$text = "your password";
}

require_once("logintop.inc.php");

if ($forgotsuccess || $success || $newusersuccess) {
?>
	<table style="color: #365F8D;">
		<tr>
			<td>&nbsp;</td>
			<td>
<?
}
if ($forgotsuccess) {
	?>
	<div style="margin:5px">
		Thank you, your password has been reset.
		<br>You will be redirected to the main page in 10 seconds or <a href="index.php<?echo $appendcustomerurl;?>">Click Here.</a>
	</div>
	<meta http-equiv="refresh" content="10;url=index.php<?echo $appendcustomerurl;?>">
	<?
} else if ($success) {
	?>
	<div style="margin:5px">
		Thank you, your account has been activated.
		<br>You will be redirected to the main page in 10 seconds or <a href="index.php<?echo $appendcustomerurl;?>">Click Here.</a>
	</div>
	<meta http-equiv="refresh" content="10;url=index.php<?echo $appendcustomerurl;?>">
	<?
} else if ($newusersuccess) {
	?>
	<div style="margin:5px">
		Thank you, your email address has been changed.
		<br>You will be redirected to the main page in 10 seconds or <a href="index.php<?echo $appendcustomerurl;?>">Click Here.</a>
	</div>
	<meta http-equiv="refresh" content="10;url=index.php<?echo $appendcustomerurl;?>">
	<?
}
if ($forgotsuccess || $success || $newusersuccess) {
?>
			</td>
		</tr>
	</table>
<?
}

if ($form) {
?>
	<form method="POST" action="<?=$action?>" name="activate">
		<table  style="color: #365F8D;">
			<tr>
				<td colspan="2"><div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div></td>
			</tr>
			<tr>
				<td colspan="2">You should have received an email containing a confirmation code. Please enter it below along with <?=$text?>.<br></td>
			</tr>

			<tr>
				<td>Confirmation Code: </td>
				<td><input type="text" name="token" value="<?=escapehtml($token)?>" size="35" /></td>
			</tr>
<?
		if($forgot){
?>
			<tr>
				<td>New Password:</td>
				<td><input type="password" name="password1"  size="35" maxlength="50" /></td>
			</tr>
			<tr>
				<td>Confirm Password:</td>
				<td><input type="password" name="password2"  size="35" maxlength="50" /></td>
			</tr>
<?
		} else {
?>
			<tr>
				<td>Password:</td>
				<td><input type="password" name="password"  size="35" maxlength="50"/></td>
			</tr>
<?
		}
?>
		<tr>
			<td>&nbsp;</td>
			<td><div><input type="image" src="img/submit.gif" onmouseover="this.src='img/submit_over.gif';" onmouseout="this.src='img/submit.gif';"></div></td>
		</tr>
		<tr>
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
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><a href="index.php<?echo $appendcustomerurl;?>">Return to Sign In</a></td>
		</tr>
		</table>
	</form>
<?
}

require_once("loginbottom.inc.php");
?>