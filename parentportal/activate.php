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
			} else if($password1 && $passworderror = isSameUserPass($user['portaluser.username'], $password1, $user['portaluser.firstname'], $user['portaluser.lastname'])){
				error($passworderror);
			} else {
				$result = portalActivateAccount($token, $password1);
				if($result['result'] == ""){
					if(!$forgot && $result['functionCode'] != '3'){
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
			if($result['functionCode'] == '1'){
				$success = true;
			} else if ($result['functionCode'] == '2' && $changeuser){
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
	$TITLE = "Forgot Password";
} else if($changeuser){
	$TITLE = "Change Email";
} else {
	$TITLE = "Activate Account";
}
include("cmlogintop.inc.php");
if($forgot){
	$action = "?f";
} else if($changeuser){
	$action = "?c";
} else {
	$action = "?n";
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
} else if ($error && $forgot){
?>
	<div style="color: red;">That code is invalid or has expired.</div>
<?
} else if ($error){
?>
	<div style="color: red;">That code is invalid or has expired or that is an incorrect password.</div>
<?
}
if($form){
?>
	<form method="POST" action="<?=$action?>" name="activate">
		<table  style="color: #365F8D;" >
			<tr>
				<td>Confirmation Code: </td>
				<td><input type="text" name="token" value="<?=$token?>" size="50" /></td>
			</tr>
<?
		if($forgot){
?>
			<tr>
				<td>New Password:</td>
				<td><input type="password" name="password1" /></td>
			</tr>
			<tr>
				<td>Confirm Password:</td>
				<td><input type="password" name="password2" /></td>
			</tr>		
<?
		} else {
?>
			<tr>
				<td>Password:</td>
				<td><input type="password" name="password" /></td>
			</tr>
<?
		}
?>
		<tr>
			<td>&nbsp;</td>
			<td><?=submit("activate", "main", "Submit")?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><a href="index.php">Return to Contact Manager Login</a></td>
		</tr>
		</table>
	</form>
<?
}

include_once("cmloginbottom.inc.php");
?>