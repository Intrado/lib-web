<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");

$success = false;
$emailnotfound = false;
$generalerror = false;
$email1 = "";
$email2 = "";
if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$email1 = get_magic_quotes_gpc() ? stripslashes($_POST['email1']) : $_POST['email1'];
	$email2 = get_magic_quotes_gpc() ? stripslashes($_POST['email2']) : $_POST['email2'];
	if ($email1 !== $email2){
		error("The 2 emails you have entered do not match");
	} else if(!preg_match("/^[\w-\.]{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]{2,}$/", $email1)){
		error("That is not a valid email address");
	} else {
		$result = portalForgotPassword($email1);
		if($result['result'] == ""){
			$success = true;
		} else {
			if($result['result'] == "invalid argument"){
				$success = true;
			} else {
				$generalerror = true;	
			}
		}
	}
}

$TITLE = "Password Assistance";
include_once("cmlogintop.inc.php");
if($generalerror){
	error("There was a problem with your request.  Please try again later");
}

if(!$success){
?>
<br>
<div>We will email you a link to a page where you can reset your password</div>
<br>
<table  style="color: #365F8D;" >
	<form method="POST" action="forgotpassword.php" name="forgotpassword">
			<tr>
				<td>Enter Your Email Address:</td>
				<td><input type="text" name="email1" size="30" value="<?=$email1?>"></td>
			</tr>
			<tr>
				<td>Please Confirm Your Email Address:</td>
				<td><input type="text" name="email2" size="30" value="<?=$email2?>"></td>
			</tr>
			<tr><td>&nbsp;</td><td><?=submit("forgotpassword", "main", "Submit")?></td></tr>
	</form>
	<tr><td colspan="2"><a href="index.php">Return to Contact Manager Login</a></td></tr>
</table>


<?
} else {
?>
	<div style="margin:5px">
		A link has been sent to your email address to log in.
		<br>Please remember to change your password.
		<br>You will be redirected to the activate page in 5 seconds.
	</div>
	<meta http-equiv="refresh" content="5;url=index.php?f">
<?
}
include_once("cmloginbottom.inc.php");
?>