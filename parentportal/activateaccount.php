<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");

$form=true;
$forgotsuccess = false;

if(isset($_GET['token'])){
	$token = $_GET['token'];
}

if(isset($_GET['forgot'])) {
	$form = false;
	$result = portalActivateAccount($_GET['token'], ''); // pass empty password, not used in validation but cannot be null

	if($result['result'] == ""){
		$id = $result['userID'];
	} else {
		$id = 0;
	}
	if($id){
		doStartSession();
		$_SESSION['portaluserid'] = $id;
		$form = false;
		$forgotsuccess = true;
	} else {
		error("That was an invalid activation code");
	}
}

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {

	$password = get_magic_quotes_gpc() ? stripslashes($_POST['password']) : $_POST['password'];
	$token = get_magic_quotes_gpc() ? stripslashes($_POST['token']) : $_POST['token'];
	$result = portalActivateAccount($token, $password);

	if($result['result'] == ""){
		$id = $result['userID'];
	} else {
		$id = 0;
	}
	if($id){
		$form=false;
		doStartSession();
		$_SESSION['portaluserid'] = $id;
?>
			<br>Thank you, your account has been activated.
			<br>You will be redirected to the welcome page in 5 seconds.
			<meta http-equiv="refresh" content="5;url=index.php">
<?
	} else {
?>
		<br>That token is invalid or has expired or that is an incorrect password.
<?
	}
}
$PAGE = ":";
$TITLE = "Parent Portal Login";
$hidenav = 1;
include_once("nav.inc.php");
if($form){
?>
	<form method="POST" action="activateaccount.php">
		<p>Please Enter your password to proceed:</p>
		<p><input type="password" name="password" /></p>
		<p><input type="text" name="token" style="display:none" value="<?=$token?>" /></p>
		<p><input type="submit" name="submit" /></p>
	</form>
<?
} else if($forgotsuccess){
	?>
	<br>Thank you, you will be redirected in 5 seconds to reset your password.
	<meta http-equiv="refresh" content="5;url=choosecustomer.php?forgot=1">
	<?
}
include_once("navbottom.inc.php");
?>