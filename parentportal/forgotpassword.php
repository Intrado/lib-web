<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");

$success = false;
$emailnotfound = false;
$email1 = "";
$email2 = "";
if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$email1 = get_magic_quotes_gpc() ? stripslashes($_POST['email1']) : $_POST['email1'];
	$email2 = get_magic_quotes_gpc() ? stripslashes($_POST['email2']) : $_POST['email2'];
	if ($email1 !== $email2){
		error("Those emails don't match");
	} else if(!preg_match("/^[\w-\.]{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]{2,}$/", $email1)){
		error("That is not a valid email address");
	} else {
		$result = portalForgotPassword($email1);
		if($result['result'] == ""){
			$success = true;
		} else {
			$emailnotfound = true;
		}
	}
}

$PAGE = ":";
$TITLE = "Forgot Password";
$hidenav = 1;
include_once("nav.inc.php");
if($emailnotfound){
	?>
		<div style="color: red;">That email is not in our system.</div>
	<?
}
startWindow("Send Reset Password" . help('Forgotpassword'));
if(!$success){
?>
<table>
	<form method="POST" action="forgotpassword.php" name="forgotpassword">
			<tr>
				<td>Enter Your Email:</td>
				<td><input type="text" name="email1" size="30" value="<?=$email1?>"></td>
			</tr>
			<tr>
				<td>Confirm Email:</td>
				<td><input type="text" name="email2" size="30" value="<?=$email2?>"></td>
			</tr>
			<tr><td>&nbsp;</td><td><?=submit("forgotpassword", "main", "Submit")?></td></tr>
	</form>
	<tr><td>&nbsp;</td><td><a href="index.php">Return to Contact Manager Login</a></td></tr>
</table>


<?
} else {
?>
			<br>A link has been sent to your email address to log in.
			<br>Please remember to change your password.
			<br>You will be redirected to the activate page in 5 seconds.
			<meta http-equiv="refresh" content="5;url=activate.php">
<?
}
endWindow();
include_once("navbottom.inc.php");
?>