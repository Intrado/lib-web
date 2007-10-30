<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");


$form = true;
$forgotsuccess = false;
$token = "";
$forgot = false;
$success = false;
$error = false;
if(isset($_GET['token'])){
	$token = $_GET['token'];
}

if(isset($_GET['forgot'])){
	$forgot = true;
}

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {

	$token = get_magic_quotes_gpc() ? stripslashes($_POST['token']) : $_POST['token'];
	
	if(isset($_POST['password1']) && isset($_POST['password2'])){
		$password1 = get_magic_quotes_gpc() ? stripslashes($_POST['password1']) : $_POST['password1'];
		$password2 = get_magic_quotes_gpc() ? stripslashes($_POST['password2']) : $_POST['password2'];
		if($password1 !== $password2){
			error("The passwords do not match");
		} else {
			$result = portalActivateAccount($token, $password1);
			if($result['result'] == ""){
				$form = false;
				$forgotsuccess = true;
				doStartSession();
				$_SESSION['portaluserid'] = $result['userID'];
			} else {
				$error = true;
			}
		}
		
	} else if(isset($_POST['password'])){
		$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];
		$result = portalActivateAccount($token, $password);
		if($result['result'] == ""){
			$form = false;
			$success = true;
			doStartSession();
			$_SESSION['portaluserid'] = $result['userID'];
		} else {
			$error = true;
		}
		
	} else {
		error("You are missing required fields");
	}
}



$PAGE = ":";
$TITLE = "Activate Account";
$hidenav = 1;
include_once("nav.inc.php");

if($forgot){
	startWindow("Activate" . help("Activateforgotpassword"));
	$action = "?forgot=1";
} else {
	startWindow("Activate" . help("Activateaccount"));
	$action = "";
}
if($forgotsuccess){
	?>
	<br>Thank you, your password has been updated.
	<br>You will be redirected to the welcome page in 5 seconds.
	<meta http-equiv="refresh" content="5;url=choosecustomer.php">
	<?
} else if($success){
	?>
	<br>Thank you, your account has been activated.
	<br>You will be redirected to the welcome page in 5 seconds.
	<meta http-equiv="refresh" content="5;url=index.php">
	<?
} else if ($error && $forgot){
?>
	<div style="color: red;">That token is invalid or has expired.</div>
<?
} else if ($error){
?>
	<div style="color: red;">That token is invalid or has expired or that is an incorrect password.</div>
<?
}
if($form){
?>
	<form method="POST" action="activate.php<?=$action?>" name="activate">
		<table>
			<tr>
				<td>Activation Code: </td>
				<td><input type="text" name="token" value="<?=$token?>" size="50" /></td>
			</tr>
<?
		if($forgot){
?>
			<tr>
				<td>New Password:</td>
				<td><input type="password" name="password1" /></td>
			</tr>
			<tr>
				<td>Confirm Password:</td>
				<td><input type="password" name="password2" /></td>
			</tr>		
<?
		} else {
?>
			<tr>
				<td>Password:</td>
				<td><input type="password" name="password" /></td>
			</tr>
<?
		}
?>
		<tr>
			<td>&nbsp;</td>
			<td><?=submit("activate", "main", "Submit")?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><a href="index.php">Return to Contact Manager Login</a></td>
		</tr>
		</table>
	</form>
<?
}
endWindow();
include_once("navbottom.inc.php");
?>