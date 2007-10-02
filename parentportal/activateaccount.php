<?
$ppNotLoggedIn = 1;
require_once("common.inc.php");

if(isset($_GET['token'])){
	if($id = portalActivateAccount($_GET['token'])){
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