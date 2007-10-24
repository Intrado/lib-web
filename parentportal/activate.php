<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");

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
				$forgotsucess = true;
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
			$sucess = true;
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
$TITLE = "Parent Portal Login";
$hidenav = 1;
include_once("nav.inc.php");
if($forgotsuccess){
	?>
	<br>Thank you, your password has been updated.
	<meta http-equiv="refresh" content="5;url=choosecustomer.php">
	<?
} else if($success){
	?>
	<br>Thank you, your account has been activated.
	<br>You will be redirected to the welcome page in 5 seconds.
	<meta http-equiv="refresh" content="5;url=index.php">
	<?
} else if ($error){
?>
	<br>That token is invalid or has expired or that is an incorrect password.
<?
}

if($form){
?>
	<form method="POST" action="activate.php">
		<table>
			<tr>
				<td>Activation Code: </td>
				<td><input type="text" name="token" value="<?=$token?>" size="30" /></td>
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
			<td><input type="submit" name="submit" value="Submit" /></td>
		</tr>
	</form>
<?
}
include_once("navbottom.inc.php");
?>