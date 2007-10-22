<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");


$login = "";
$firstname = "";
$lastname = "";
$zipcode = "";
$success = false;

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$firstname = get_magic_quotes_gpc() ? stripslashes($_POST['firstname']) : $_POST['firstname'];
	$lastname = get_magic_quotes_gpc() ? stripslashes($_POST['lastname']) : $_POST['lastname'];
	$zipcode = get_magic_quotes_gpc() ? stripslashes($_POST['zipcode']) : $_POST['zipcode'];
	$password1 = get_magic_quotes_gpc() ? stripslashes($_POST['password1']) : $_POST['password1'];
	$password2 = get_magic_quotes_gpc() ? stripslashes($_POST['password2']) : $_POST['password2'];
	if(!preg_match("/^[\w-\.]{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]{2,}$/", $login)){
		error("That is not a valid email format");
	} else if(!ereg("^[0-9]*$",$zipcode)){
		error("The zipcode must be a number");
	} else if(strlen($zipcode) != 5){
		error("Zip code must be a 5 digit number");
	} else if($_POST['password1'] == ""){
		error("You must enter a password");
	} else if($password1 != $password2){
		error("Your passwords don't match");
	} else {
		$result = portalCreateAccount($login, $password1, $firstname, $lastname, $zipcode);
		if($result['result'] != ""){
			if($result['result'] == "duplicate"){
				$errordetails = "The username is already in use";
			} else {
				$errordetails = "An unknown error occured";
			}
			error("Your account was not created", $errordetails);
		} else {
			$success = true;
		}
	}
}

$hidenav = 1;
$TITLE = "Create a New User";
$PAGE = ":";
include_once("nav.inc.php");
if(!$success){
?>
	<form method="POST" action="newportaluser.php">
		<table>
			<tr>
				<td>Email(this will be your login name):</td>
				<td><input type="text" name="login" value="<?=$login?>" size="50" maxlength="100"/> </td>
			</tr>
			<tr>
				<td>Password: </td>
				<td><input type="password" name="password1" /> </td>
			</tr>
			<tr>
				<td>Confirm Password: </td>
				<td><input type="password" name="password2" /> </td>
			</tr>
			<tr>
				<td>First Name:</td>
				<td><input type="text" name="firstname" value="<?=$firstname?>" /></td>
			</tr>
			<tr>
				<td>Last Name:</td>
				<td><input type="text" name="lastname" value="<?=$lastname?>" /></td>
			</tr>
			<tr>
				<td>Zip Code:</td>
				<td><input type="text" name="zipcode" value="<?=$zipcode?>" /></td>
			</tr>
		</table>
		<input type="submit" value="Create Account" name="submit" />
	</form>
	<br><a href="index.php">Return to Parent Portal Login</a>
<?
} else {
?>
	<br>Thank you, Your account has been created.
	<br>Please check your email to activate your account.
	<br>You will be redirected to the login page in 5 seconds.
	<meta http-equiv="refresh" content="5;url=index.php">
<?
}

include_once("navbottom.inc.php");
?>