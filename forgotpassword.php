<?
// Cannot use form.inc because no session has started

$isindexpage = true;
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");


if ($SETTINGS['feature']['has_ssl']) {
	if ($IS_COMMSUITE)
		$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/forgotpassword.php";
	/*CSDELETEMARKER_START*/
	else
		$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/$CUSTOMERURL/forgotpassword.php";
	/*CSDELETEMARKER_END*/

	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect($secureurl);
	}
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
		<div style="padding-top: 10px; padding-left: 25px; color: #<?=$primary?>">
			<h1 style="font-size: 20px; "><?=$TITLE?></h1>
			<p>To begin the password reset process, enter your username.</p>
			<form method="POST" action="forgotpassword.php" name="forgotpassword">
			
			<label class="indexform"><?=_L("Username:")?><br>
			<input type="text" name="username1" size="20" alue="<?=escapehtml($username1)?>">
			</label><br>

			<label class="indexform"><?=_L("Confirm Username:")?><br>
			<input type="text" name="username2" size="20" alue="<?=escapehtml($username2)?>">
			</label><br>

			<input type="submit" name="submit" value="Send Password Reset Email"><br>
			
			<br><a href="index.php">Return to Login Page</a>

			</form>
		</div>
<?
} else {
?>
	<div style="margin-top: 50px; margin-left: 25px;">

	<table  style="color: #365F8D;" >
		<tr>
			<td>&nbsp;</td>
			<td>
				<div style="margin:5px; font-size:12px">
					You should receive an email containing a confirmation code shortly.  If you do not receive the email, please contact your System Administrator or call support for assistance.
					<br>You will be redirected to the password assistance page in 10 seconds, or you can <a href="index.php?f">Click Here to continue.</a>
				</div>
				<meta http-equiv="refresh" content="10;url=index.php?f">
			</td>
		</tr>
	</table>
</div>
<?
}
include_once("loginbottom.inc.php");
?>