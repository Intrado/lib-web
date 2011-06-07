<?
$isindexpage = true;
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");

if ($SETTINGS['feature']['has_ssl']) {

	$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/$CUSTOMERURL/resetpassword.php";

	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect($secureurl);
	}
}


$form = true;
$token = "";
$success = false;
$error = false;
$result = null;
if(isset($_GET['t'])){
	$token = $_GET['t'];
}

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {

	$token = get_magic_quotes_gpc() ? stripslashes($_POST['token']) : $_POST['token'];
	$user = prefetchUserInfo($token);
	if($user == false || $user['result'] != ""){
		error("That code is invalid or has expired");
	} else {
		if(isset($_POST['password1']) && isset($_POST['password2'])){
			$password1 = get_magic_quotes_gpc() ? trim(stripslashes($_POST['password1'])) : trim($_POST['password1']);
			$password2 = get_magic_quotes_gpc() ? trim(stripslashes($_POST['password2'])) : trim($_POST['password2']);

			if($password1 !== $password2){
				error("The passwords do not match");
			} else if(strlen($password1) < $user['passwordlength']){
				error("Passwords must be at least " . $user['passwordlength'] . " characters long");
			} else if($password1 && $passworderror = validateNewPassword($user['user.login'], $password1, $user['user.firstname'], $user['user.lastname'])){
				error($passworderror);
			} elseif(!passwordcheck($password1)){
				error('Your password must contain a letter and a number or symbol.');
			} else {
				$userid = resetPassword($token, $password1, $_SERVER['REMOTE_ADDR']);
				if($userid){
					doStartSession();
					loadCredentials($userid);
					$USER->lastlogin = QuickQuery("select now()");
					$USER->update(array("lastlogin"));
					loadDisplaySettings();
					redirect("start.php");
				} else {
					error("That code is invalid or has expired");
				}
			}
		} else {
			error("You are missing required fields");
		}
	}
}


$TITLE = "Password Assistance";
//primary colors are pulled in login top
include("logintop.inc.php");

if($success){
?>
	<table style="color: #365F8D;">
		<tr>
			<td>&nbsp;</td>
			<td>
				<div style="padding:5px">
					Thank you, your account has been activated.
					<br>You will be redirected to the main page in 10 seconds or <a href="index.php">Click Here.</a>
				</div>
			</td>
		</tr>
	</table>
	<meta http-equiv="refresh" content="10;url=index.php">
<?
}

if($form){
?>
	<form method="POST" action="?f" name="activate">
		<div><table width="100%" style="color: #<?=$primary?>; font-size: 12px;padding-left: 25px;" >
			<tr>
				<td colspan="2"><h1 style="font-size: 20px; "><?=$TITLE?></h1></td>
			</tr>
			<tr>
				<td colspan="2">You should receive an email containing a confirmation code shortly.  Please enter the confirmation code below along with your new password.  Passwords cannot be similar to your first name, last name, or login.  If you do not receive the email, please contact your System Administrator or call support for assistance.<br><br></td>
			</tr>

			<tr>
				<td>Confirmation Code: </td>
				<td><input type="text" name="token" value="<?=escapehtml($token)?>" size="20" /></td>
			</tr>

			<tr>
				<td>New Password:</td>
				<td><input type="password" name="password1"  size="20" maxlength="50" /></td>
			</tr>
			<tr>
				<td>Confirm Password:</td>
				<td><input type="password" name="password2"  size="20" maxlength="50" /></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><div><input type="submit" name="submit" value="Continue"></div></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><a href="index.php">Return to Sign In</a></td>
			</tr>
		</table></div>
	</form>
<?
}
include_once("loginbottom.inc.php");
?>