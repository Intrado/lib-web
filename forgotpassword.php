<?
// Cannot use form.inc because no session has started

$isindexpage = true;
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");

// force ssl
if ($SETTINGS['feature']['has_ssl'] && $SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
	$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/$CUSTOMERURL/forgotpassword.php";
	// forward all params
	if (count($_GET) > 0)
		$secureurl .= "?" . http_build_query($_GET);
	redirect($secureurl);
}

$error = "";
$success = false;
$emailnotfound = false;
$generalerror = false;
$username1 = "";
$username2 = "";
if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$username1 = get_magic_quotes_gpc() ? stripslashes($_POST['username1']) : $_POST['username1'];
	$username2 = get_magic_quotes_gpc() ? stripslashes($_POST['username2']) : $_POST['username2'];
	if ($username1 !== $username2) {
		error("The usernames you have entered do not match");
	} else if ($username1 == "") {
		error("Please enter your username");
	} else {
		$result = forgotPassword($username1, $CUSTOMERURL);
		if ($result['result'] == "") {
			$success = true;
		} else if ($result['result'] == "invalid data") {
			if ($result['resultdetail'] == "bad data: user is ldap") {
				error("This account is managed by another authentication server.  Please contact your System Administrator to update your password");
			} else {
				error("There was a problem with your request.  Either the username you entered is not valid, or there is no email address associated with this user. Please contact your System Administrator or call support for assistance");
			}
		} else {
			error("There was a problem with your request.  Please try again later");
		}
	}
}

$TITLE = "Password Assistance";
//primary colors are pulled in login top
include_once("logintop.inc.php");
if(!$success){
	?>
		
			<form method="POST" action="forgotpassword.php" name="forgotpassword">
			
			<h3><?=$TITLE?></h3>
			<p>To begin the password reset process, enter your username.</p>
			
			<fieldset>
				<label for="form_name"><?=_L("Username:")?></label>
				<input type="text" id="form_name" name="username1" size="20" alue="<?=escapehtml($username1)?>"/>
			</fieldset>

			<fieldset>
				<label for="form_confirm"><?=_L("Confirm Username:")?></label>
				<input type="text" id="form_confirm" name="username2" size="20" alue="<?=escapehtml($username2)?>">
			</fieldset>

			<fieldset>
				<input type="submit" name="submit" value="Send">
			</fieldset>
			
			<p class="right"><a href="index.php">Return to Login Page</a></p>

			</form>

<?
} else {
?>

			<div class="right">
				<p>You should receive an email containing a confirmation code shortly.  If you do not receive the email, please contact your System Administrator or call support for assistance.</p>
				<p>You will be redirected to the password assistance page in 10 seconds, or you can <a href="index.php?f">Click Here to continue.</a></p>
			</div>
			<meta http-equiv="refresh" content="10;url=index.php?f">

<?
}
include_once("loginbottom.inc.php");
?>