<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("parentportalutils.inc.php");

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
	} else if(!validEmail($email1)){
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
<form method="POST" action="forgotpassword.php" name="forgotpassword">
	<table  style="color: #365F8D;" >
		<tr>
			<td width="20%">&nbsp;</td>
			<td colspan="2"><div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div></td>
		</tr>
		<tr>
			<td width="20%">&nbsp;</td>
			<td colspan="2">We will email you a link to a page where you can reset your password.</td>
		</tr>
		<tr>
			<td width="20%">&nbsp;</td>
			<td>Email Address:</td>
			<td><input type="text" name="email1" size="50" maxlength="255" value="<?=htmlentities($email1)?>"></td>
		</tr>
		<tr>
			<td width="20%">&nbsp;</td>
			<td>Email Confirmation:</td>
			<td><input type="text" name="email2" size="50" maxlength="255" value="<?=htmlentities($email2)?>"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td><?=submit("forgotpassword", "main", "Submit")?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td><br><a href="index.php">Return to Sign In</a></td>
		</tr>
	</table>
</form>

<?
} else {
?>
	<table  style="color: #365F8D;" >
		<tr>
			<td width=20%>&nbsp;</td>
			<td>
				<div style="margin:5px">
					A link has been sent to your email address to log in.
					<br>Please remember to change your password.
					<br>You will be redirected to the activate page in 5 seconds.
				</div>
				<meta http-equiv="refresh" content="5;url=index.php?f">
			</td>
		</tr>
	</table>
<?
}
include_once("cmloginbottom.inc.php");
?>