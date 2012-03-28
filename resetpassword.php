<?
$isindexpage = true;
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");

// force ssl
if ($SETTINGS['feature']['has_ssl'] && $SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
	$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/$CUSTOMERURL/resetpassword.php";
	// forward all params
	if (count($_GET) > 0)
		$secureurl .= "?" . http_build_query($_GET);
	redirect($secureurl);
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

			<div class="rightblock">
				<h3>Thank you, your account has been activated.</h3>
				<p>You will be redirected to the main page in 10 seconds or <a href="index.php">Click Here.</a></p>
			</div>

	<meta http-equiv="refresh" content="10;url=index.php">
<?
}

if($form){
?>
	<form method="POST" action="?f" name="activate">

			<h3><?=$TITLE?></h3>
			<p>You should receive an email containing a confirmation code shortly.  Please enter the confirmation code below along with your new password.  Passwords cannot be similar to your first name, last name, or login.  If you do not receive the email, please contact your System Administrator or call support for assistance.</p>

			<fieldset>
				<label for="form_code">Confirmation Code:</label>
				<input type="text" name="token" id="form_code" value="<?=escapehtml($token)?>" size="20" />
			</fieldset>

			<fieldset>
				<label for="form_newpass">New Password:</label>
				<input type="password" name="password1" id="form_newpass" size="20" maxlength="50" />
			</fieldset>
			
			<fieldset>
				<label for="form_confirmpass">Confirm Password:</label>
				<input type="password" name="password2" id="form_confirmpass" size="20" maxlength="50" />
			</fieldset>
			
			<fieldset>
				<input type="submit" name="submit" value="Continue">
			</fieldset>
			
			<p class="right"><a href="index.php">Return to Sign In</a></p>

	</form>
<?
}
include_once("loginbottom.inc.php");
?>