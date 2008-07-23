<?
$isindexpage = true;
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");

if ($SETTINGS['feature']['has_ssl']) {
	if ($IS_COMMSUITE)
		$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/resetpassword.php";
	/*CSDELETEMARKER_START*/
	else
		$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/$CUSTOMERURL/resetpassword.php";
	/*CSDELETEMARKER_END*/

	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect($secureurl);
	}
}


$form = true;
$token = "";
$success = false;
$error = false;
$result = null;
if(isset($_GET['t'])){
	$token = $_GET['t'];
}

if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {

	$token = get_magic_quotes_gpc() ? stripslashes($_POST['token']) : $_POST['token'];
	$user = prefetchUserInfo($token);
	if($user == false || $user['result'] != ""){
		error("That code is invalid or has expired");
	} else {
		if(isset($_POST['password1']) && isset($_POST['password2'])){
			$password1 = get_magic_quotes_gpc() ? trim(stripslashes($_POST['password1'])) : trim($_POST['password1']);
			$password2 = get_magic_quotes_gpc() ? trim(stripslashes($_POST['password2'])) : trim($_POST['password2']);

			if($password1 !== $password2){
				error("The passwords do not match");
			} else if(strlen($password1) < $user['passwordlength']){
				error("Passwords must be at least 5 characters long");
			} else if($password1 && $passworderror = validateNewPassword($user['user.login'], $password1, $user['user.firstname'], $user['user.lastname'])){
				error($passworderror);
			} elseif(!passwordcheck($password1)){
				error('Your password must contain at least 2 of the following: a letter, a number or a symbol', $securityrules);
			} else {
				$userid = resetPassword($token, $password1, $_SERVER['REMOTE_ADDR']);
				if($userid){
					loadCredentials($userid);
					if (!$USER->enabled || $USER->deleted | !$ACCESS->getValue('loginweb')) {
						@session_destroy();
						$badlogin = true;
						error_log("User trying to log in but is disabled or doesnt have access");
					} else {
						if ($updatelogin) {
							$USER->lastlogin = QuickQuery("select now()");
							$USER->update(array("lastlogin"));
						}
						if (!isset($_SESSION['etagstring'])){
								$_SESSION['etagstring'] = mt_rand();
						}
						// fetch default scheme
						$scheme = getCustomerData($CUSTOMERURL);
						if($scheme == false){
							$scheme = array("_brandtheme" => "3dblue",
											"_supportemail" => "support@schoolmessenger.com",
											"_supportphone" => "800.920.3897",
											"colors" => array("_brandprimary" => "26477D"));
						}
						$userprefs = array();
						$userprefs['_brandprimary'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandprimary'");
						$userprefs['_brandtheme1'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandtheme1'");
						$userprefs['_brandtheme2'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandtheme2'");
						$userprefs['_brandratio'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandratio'");
						$userprefs['_brandtheme'] = QuickQuery("select value from usersetting where userid=" . $USER->id . " and name = '_brandtheme'");

						if($userprefs['_brandprimary']){
							$_SESSION['colorscheme'] = $userprefs;
						} else {
							$_SESSION['colorscheme'] = array("_brandtheme" => $scheme['_brandtheme'],
														"_brandprimary" => $scheme['colors']['_brandprimary'],
														"_brandtheme1" => $scheme['colors']['_brandtheme1'],
														"_brandtheme2" => $scheme['colors']['_brandtheme2'],
														"_brandratio" => $scheme['colors']['_brandratio']);
						}

						$_SESSION['productname'] = isset($scheme['productname']) ? $scheme['productname'] : "" ;
						$_SESSION['_supportphone'] = $scheme['_supportphone'];
						$_SESSION['_supportemail'] = $scheme['_supportemail'];
						redirect("start.php");
					}
				} else {
					error("That code is invalid or has expired");
				}
			}
		} else {
			error("You are missing required fields");
		}
	}
}


$TITLE = "Password Assistance";
//primary colors are pulled in login top
include("logintop.inc.php");

if($success){
?>
	<table style="color: #365F8D;">
		<tr>
			<td>&nbsp;</td>
			<td>
				<div style="margin:5px">
					Thank you, your account has been activated.
					<br>You will be redirected to the main page in 10 seconds or <a href="index.php">Click Here.</a>
				</div>
			</td>
		</tr>
	</table>
	<meta http-equiv="refresh" content="10;url=index.php">
<?
}

if($form){
?>
	<form method="POST" action="?f" name="activate">
		<table width="100%" style="color: #<?=$primary?>; font-size: 12px;" >
			<tr>
				<td colspan="2"><div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div></td>
			</tr>
			<tr>
				<td colspan="2">You should have recieved an email containing a confirmation code. Please enter it below along with your new password.  Passwords must be 5 characters in length and cannot be similiar to your first name, last name, or email address.<br></td>
			</tr>

			<tr>
				<td>Confirmation Code: </td>
				<td><input type="text" name="token" value="<?=htmlentities($token)?>" size="35" /></td>
			</tr>

			<tr>
				<td>New Password:</td>
				<td><input type="password" name="password1"  size="35" maxlength="50" /></td>
			</tr>
			<tr>
				<td>Confirm Password:</td>
				<td><input type="password" name="password2"  size="35" maxlength="50" /></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><div><input type="image" src="img/submit.gif" onmouseover="this.src='img/submit_over.gif';" onmouseout="this.src='img/submit.gif';"></div></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><a href="index.php">Return to Sign In</a></td>
			</tr>
		</table>
	</form>
<?
}
include_once("loginbottom.inc.php");
?>