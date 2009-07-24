<?
$isNotLoggedIn = 1;

if (!isset($_SESSION['_locale']))
	$_SESSION['_locale'] = isset($_COOKIE['locale'])?$_COOKIE['locale']:"en_US";

require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");

if (isset($_GET['locale'])) {
	setcookie('locale', $_GET['locale']);
	redirect();
}

if (isset($_GET['deletelocale'])) {
	setcookie('locale', '');
	redirect();
}

$success = false;
$emailnotfound = false;
$generalerror = false;
$email1 = "";
$email2 = "";
if ((strtolower($_SERVER['REQUEST_METHOD']) == 'post') ) {
	$email1 = get_magic_quotes_gpc() ? stripslashes($_POST['email1']) : $_POST['email1'];
	$email2 = get_magic_quotes_gpc() ? stripslashes($_POST['email2']) : $_POST['email2'];
	if ($email1 !== $email2){
		error(_L("The 2 emails you have entered do not match"));
	} else if(!validEmail($email1)){
		error(_L("That is not a valid email address"));
	} else {
		$result = subscriberForgotPassword($email1);
		if ($result['result'] == "") {
			$success = true;
		} else {
			if ($result['result'] == "invalid argument") {
				if ($result['resultdetail'] == "unknown username" ||
					$result['resultdetail'] == "deactivated subscriber account") {
					error(_L("This user does not exist, please Create an Account."));
				} else
					$success = true;
			} else {
				$generalerror = true;
			}
		}
	}
}

$TITLE = _L("Password Assistance");
require_once("logintop.inc.php");
if ($generalerror) {
	error(_L("There was a problem with your request.  Please try again later"));
}

if (!$success) {
?>
<form method="POST" action="forgotpassword.php" name="forgotpassword">
	<table width="100%" style="color: #<?=$primary?>;" >
			<tr>
				<td colspan="2">
					<div style="float:right;">
						<select id="locale" onchange='window.location.href="forgotpassword.php?locale="+this.options[this.selectedIndex].value'>
						<?foreach ($LOCALES as $loc => $lang){?>
							<option value="<?=$loc?>" <?=($loc == $_SESSION['_locale'])?"selected":""?>><?=$lang?></option>
						<?}?>
						</select>
					</div>
				</td>
			</tr>

		<tr>
			<td colspan="2"">
				<div style="font-size: 20px; font-weight: bold; text-align: left;"><?=$TITLE?></div>
			</td>
		</tr>
		<tr>
			<td colspan="2"><?=_L("To begin the password reset process, enter your email address.")?></td>
		</tr>
		<tr>
			<td>Email:</td>
			<td><input type="text" name="email1" size="50" maxlength="255" value="<?=escapehtml($email1)?>"></td>
		</tr>
		<tr>
			<td>Confirm Email:</td>
			<td><input type="text" name="email2" size="50" maxlength="255" value="<?=escapehtml($email2)?>"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align="right"><?= submit_button("Submit","save","tick") ?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><br><a href="index.php"><?=_L("Return to Sign In")?></a></td>
		</tr>
	</table>
</form>

<?
} else {
?>
	<table  style="color: #365F8D;" >
		<tr>
			<td>&nbsp;</td>
			<td>
				<div style="margin:5px">
					<?=_L("Check your email to receive the password reset link.")?>
					<br><?=_L("You will be redirected to the Activation page in 10 seconds, or you can")?> <a href="index.php?f"><?=_L("Click Here to continue.")?></a>
				</div>
				<meta http-equiv="refresh" content="10;url=index.php?f"; ?>">
			</td>
		</tr>
	</table>
<?
}
require_once("loginbottom.inc.php");
?>