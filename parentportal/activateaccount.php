<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");

if(isset($_GET['token'])){
	$result = portalActivateAccount($_GET['token']);
	if($result['result'] == ""){
		$id = $result['userID'];
	} else {
		$id = 0;
	}
	if($id){
		doStartSession();
		$_SESSION['portaluserid'] = $id;
		$_SESSION['custname'] = "";
		$_SESSION['customerid'] = 0;
	}
	if($id && isset($_GET['forgot'])) {
		?>
		<br>Thank you, you will be redirected in 5 seconds to reset your password.
		<meta http-equiv="refresh" content="5;url=account.php">
		<?
	} else if($id) {
		?>
		<br>Thank you, your account has been activated.
		<br>You will be redirected to the welcome page in 5 seconds.
		<meta http-equiv="refresh" content="5;url=index.php">
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