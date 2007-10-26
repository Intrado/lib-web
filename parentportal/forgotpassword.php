<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");

$success = false;
$emailnotfound = false;
if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$email = get_magic_quotes_gpc() ? stripslashes($_POST['email']) : $_POST['email'];
	if(!preg_match("/^[\w-\.]{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]{2,}$/", $email)){
		error("That is not a valid email address");
	} else {
		$result = portalForgotPassword($email);
		if($result['result'] == ""){
			$success = true;
		} else {
			$emailnotfound = true;
		}
	}
}

$PAGE = ":";
$TITLE = "Parent Portal Login";
$hidenav = 1;
include_once("nav.inc.php");
if($emailnotfound){
	?>
		<div style="color: red;">That email is not in our system.</div>
	<?
}

if(!$success){
?>

<form method="POST" action="forgotpassword.php">
	<table>
		<tr>
			<td>Email:</td>
			<td><input type="text" name="email"></td>
		</tr>
	</table>
	<input type="submit" name="submit" value="Submit">
</form>

<br><a href="index.php">Return to Parent Portal Login</a>
<?
} else {
?>
			<br>A link has been sent to your email address to log in.
			<br>Please remember to change your password.
			<br>You will be redirected to the login page in 5 seconds.
			<meta http-equiv="refresh" content="5;url=index.php">
<?
}
include_once("navbottom.inc.php");
?>