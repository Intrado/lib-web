<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");


$login = "";
$firstname = "";
$lastname = "";
$zipcode = "";
$success = false;
$tos = file_get_contents("terms.html");

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$login = get_magic_quotes_gpc() ? stripslashes($_POST['login']) : $_POST['login'];
	$confirmlogin = get_magic_quotes_gpc() ? stripslashes($_POST['confirmlogin']) : $_POST['confirmlogin'];
	$firstname = get_magic_quotes_gpc() ? stripslashes($_POST['firstname']) : $_POST['firstname'];
	$lastname = get_magic_quotes_gpc() ? stripslashes($_POST['lastname']) : $_POST['lastname'];
	$zipcode = get_magic_quotes_gpc() ? stripslashes($_POST['zipcode']) : $_POST['zipcode'];
	$password1 = get_magic_quotes_gpc() ? trim(stripslashes($_POST['password1'])) : trim($_POST['password1']);
	$password2 = get_magic_quotes_gpc() ? trim(stripslashes($_POST['password2'])) : trim($_POST['password2']);
	$acceptterms = isset($_POST['acceptterms']);
	
	if($login != $confirmlogin){
		error("The emails you have entered do not match");
	} else if(!preg_match("/^[\w-\.]{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]{2,}$/", $login)){
		error("That is not a valid email format");
	} else if(!ereg("^[0-9]*$",$zipcode)){
		error("The zipcode must be a number");
	} else if(strlen($zipcode) != 5){
		error("Zip code must be a 5 digit number");
	} else if($_POST['password1'] == ""){
		error("You must enter a password");
	} else if($password1 != $password2){
		error("Your passwords don't match");
	} else if(strlen($password1) < 5){
		error("Passwords must be at least 5 characters long");
	} else if($passworderror = validateNewPassword($login, $password1, $firstname, $lastname)){
		error($passworderror);
	} else {
		$result = portalCreateAccount($login, $password1, $firstname, $lastname, $zipcode);
		if($result['result'] != ""){
			if($result['result'] == "duplicate"){
				$errordetails = "That email address is already in use";
			} else {
				$errordetails = "An unknown error occured, please try again";
			}
			error("Your account was not created", $errordetails);
		} else {
			$success = true;
		}
	}
}

$TITLE = "Create a New Account";
include_once("cmlogintop.inc.php");
if(!$success){
?>
	<form method="POST" action="newportaluser.php" name="newaccount">
		<table  style="color: #365F8D;" >
			<tr>
				<td width="20%">&nbsp;</td>
				<td colspan="2"><div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div></td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td>Email&nbsp;(this will be your login name):</td>
				<td><input type="text" name="login" value="<?=$login?>" size="50" maxlength="255"/> </td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td>Email Confirmation:</td>
				<td><input type="text" name="confirmlogin" value="<?=htmlentities($confirmlogin)?>" size="50" maxlength="255"/> </td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td>Password: </td>
				<td><input type="password" name="password1"  size="35" maxlength="50"/> </td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td>Confirm Password: </td>
				<td><input type="password" name="password2"  size="35" maxlength="50"/> </td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td>First Name:</td>
				<td><input type="text" name="firstname" value="<?=htmlentities($firstname)?>" maxlength="100"/></td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td>Last Name:</td>
				<td><input type="text" name="lastname" value="<?=htmlentities($lastname)?>" maxlength="100"/></td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td>ZIP Code:</td>
				<td><input type="text" name="zipcode" value="<?=htmlentities($zipcode)?>" size="5" maxlength="5"/></td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td colspan="2"><div style="overflow:scroll; height:250px; width:375px;"><?=$tos ?></div></td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td colspan="2"><input type="checkbox" name="acceptterms" id="tos"/> Accept Terms of Service</td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td colspan="2"><?=customsubmit("newaccount", "main", "Create Account")?></td>
			</tr>
			<tr>
				<td width="20%">&nbsp;</td>
				<td colspan="2"><br><a href="index.php">Return to Sign In</a></td>
			</tr>
		</table>
	</form>
<?
} else {
?>
	<div style="margin:5px">
		Thank you, Your account has been created.
		<br>Please check your email to activate your account.
		<br>You will be redirected to the activate page in 5 seconds.
	</div>
	<meta http-equiv="refresh" content="5;url=index.php?n">
<?
}
include_once("cmloginbottom.inc.php");

function custombutton($name, $onclick = NULL, $href = NULL) {

	$btn = '<div class="button" onmouseover="btn_rollover(this);" onmouseout="btn_rollout(this);"';


	if ($onclick)
		$btn .= ' onclick="' . $onclick . '; return false;" ';
	else if ($href)
		$btn .= ' onclick="window.location=\'' . $href . '\'; return false;" ';

	$btn .= '><a href="';

	if ($href)
		$btn .= htmlentities($href);
	else
		$btn .= "#";

	$btn.= '">
		<table><tr><td><img buttonrollover="left" src="img/button_left.gif"></td><td buttonrollover="middle" class="middle">' . $name . '</td><td><img buttonrollover="right" src="img/button_right.gif"></td></tr></table>
	</a></div>';

	return $btn;
}

function customsubmit($form, $section, $name = 'Submit') {
	//ugly hack. in order for enter key to submit form, either we need to add JS to each text field, or there must be an actual submit button
	//so we make a submit button and hide it off screen.
	$ret = '<input type="submit" value="submit" name="submit[' . $form . '][' . $section . ']" style="position: absolute; left: -1000px; top: -1000px;">';
	$ret .= button($name,"if(new getObj('tos').obj.checked){ submitForm('$form','$section'); } else { window.alert('You must accept the Terms of Service.');}");

	return $ret;
}
?>