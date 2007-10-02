<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");


$login = "";
$firstname = "";
$lastname = "";
$zipcode = "";

if(isset($_POST['login'])){
	$login = $_POST['login'];
	$firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$zipcode = $_POST['zipcode'];
	if(!preg_match("/^[\w-\.]{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]{2,}$/", $login)){
		error("That is not a valid email format");
	} else if(!ereg("^[0-9]*$",$zipcode)){
		error("The zipcode must be a number");
	} else if(strlen($zipcode) != 5){
		error("Zip code must be a 5 digit number");
	} else if($_POST['password1'] == ""){
		error("You must enter a password");
	} else if($_POST['password1'] != $_POST['password2']){
		error("Your passwords don't match");
	} else if(portalCreateAccount($login, $_POST['password1'], $firstname, $lastname, $zipcode)){
		redirect("accountcreated.php");
	} else {
		error("Your account was not created, please check your information");
	}
}
?>



<form method="POST" action="newportaluser.php">
	<table>
		<tr>
			<td>Email(this will be your login name):</td>
			<td><input type="text" name="login" value="<?=$login?>" /> </td>
		</tr>
		<tr>
			<td>Password: </td>
			<td><input type="password" name="password1" /> </td>
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
if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
?>