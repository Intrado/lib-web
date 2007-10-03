<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");

if(isset($_GET['token'])){
	$id = portalActivateAccount($_GET['token']);
	if($id && isset($_GET['forgot'])) {
		doStartSession();
		$_SESSION['portaluserid'] = $id;
		$_SESSION['custname'] = "";
		$_SESSION['customerid'] = 0;
		?>
		<br>Thank you, you will be redirected in 3 seconds to reset your password.
		<meta http-equiv="refresh" content="3;url=account.php">
		<?
	} else if($id) {
		?>
		<br>Thank you, your account has been activated.
		<br>You will be redirected to the login page in 10 seconds.
		<meta http-equiv="refresh" content="10;url=index.php">
		<?
	} else {
		?>
		<br>That token is invalid or has expired.
		<br>Please re-create your account or contact your system administrator
		<br><a href="index.php">Return to Parent Portal Login</a>
		<?
	}
}

?>