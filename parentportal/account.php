<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");

$error_failedupdate = "There was an error updating your information";
$error_failedupdatepassword = "There was an error updating your password";
$error_badpassword = "The old password provided is invalid";
$f="portaluser";
$s="main";
$reloadform = 0;
$error = 0;
$notifysms = 0;

if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check
		$firstname = TrimFormData($f,$s,"firstname");
		$lastname = TrimFormData($f,$s,"lastname");
		$zipcode = TrimFormData($f,$s,"zipcode");
		$oldpassword = TrimFormData($f,$s,"oldpassword");
		$newpassword1 = TrimFormData($f, $s, "newpassword1");
		$newpassword2 = TrimFormData($f, $s, "newpassword2");
		$notify = TrimFormData($f, $s, "notify");
		$notifysms = TrimFormData($f, $s, "notifysms");
		$sms = TrimFormData($f, $s, "sms");
		$_SESSION['portaluser']['portaluser.preferences']['_locale'] = getFormData($f, $s, "_locale");
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(strlen($zipcode) < 5){
			error("ZIP Code must be at least 5 digits long");
		} else if(strlen($newpassword1) > 0 && strlen($newpassword1) < 5){
			error("Passwords must be at least 5 characters long");
		} else if($newpassword1 && $passworderror = validateNewPassword($_SESSION['portaluser']['portaluser.username'], $newpassword1, $firstname, $lastname)){
			error($passworderror);
		} else if($newpassword1 != $newpassword2){
			error('Password confirmation does not match');
		} else if ($notifysms && $phoneerror = Phone::validate($sms)) {
			error($phoneerror);
		} else {
			//submit changes

			if ($notify){
				$notifyType = "message";
			} else {
				$notifyType = "none";
			}
			if ($notifysms){
				$notifysmsType = "message";
				$sms = Phone::parse($sms);
			} else {
				$notifysmsType = "none";
				$sms = "";
			}
			$result = portalUpdatePortalUser($firstname, $lastname, $zipcode, $notifyType, $notifysmsType, $sms, $_SESSION['portaluser']['portaluser.preferences']);
			if($result['result'] != ""){
				$updateuser = false;
				error($error_failedupdate);
				$error = 1;
			}
			if($newpassword1){
				$result = portalUpdatePortalUserPassword($newpassword1, $oldpassword);
				if($result['result'] != ""){
					$updateuser = false;
					$error = 1;
					if(strpos($result['resultdetail'], "oldpassword") !== false){
						error($error_badpassword);
					} else {
						error($error_failedupdatepassword);
					}
				}
			}
			if(!$error){
				redirect("start.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "firstname", $_SESSION['portaluser']['portaluser.firstname'], "text", "1", "100", true);
	PutFormData($f, $s, "lastname", $_SESSION['portaluser']['portaluser.lastname'], "text", "1", "100", true);
	PutFormData($f, $s, "newpassword1", "", "text");
	PutFormData($f, $s, "newpassword2", "", "text");
	PutFormData($f, $s, "oldpassword", "", "text");
	PutFormData($f, $s, "zipcode", $_SESSION['portaluser']['portaluser.zipcode'], "number", "0", "99999", true);
	PutFormData($f, $s, "_locale", $_SESSION['portaluser']['portaluser.preferences']['_locale'], "text", "nomin", "nomax");
	PutFormData($f, $s, "notify",  ($_SESSION['portaluser']['portaluser.notifytype'] == "message") ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, "notifysms",  ($_SESSION['portaluser']['portaluser.notifysmstype'] == "message") ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, "sms", Phone::format($_SESSION['portaluser']['portaluser.sms']), "phone", "2", "20"); // 20 is the max to accomodate formatting chars
	$notifysms = ($_SESSION['portaluser']['portaluser.notifysmstype'] == "message") ? 1 : 0;
}

$PAGE = "account:account";
$TITLE = _L("Account Information") . ": " . escapehtml($_SESSION['portaluser']['portaluser.firstname']) . " " . escapehtml($_SESSION['portaluser']['portaluser.lastname']);
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, _L('Save')), button(_L("Change Email"), NULL, "changeemail.php"), button(_L("Cancel"), NULL, "start.php"));

startWindow(_L('User Information'));
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;"><?=_L("Account Info")?>:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align="right"><?=_L("Email")?>:</td>
						<td><?= escapehtml($_SESSION['portaluser']['portaluser.username']) ?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("First Name")?>:</td>
						<td><? NewFormItem($f,$s, 'firstname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("Last Name")?>:</td>
						<td><? NewFormItem($f,$s, 'lastname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("ZIP Code")?>:</td>
						<td><? NewFormItem($f, $s, 'zipcode', 'text', '5'); ?></td>
					</tr>
					<tr>
						<td align="right">*<?=_L("Old Password")?>:</td>
						<td><? NewFormItem($f,$s, 'oldpassword', 'password', 20,50); ?></td>
					</tr>
					<tr>
						<td align="right">*<?=_L("New Password")?>:</td>
						<td><? NewFormItem($f,$s, 'newpassword1', 'password', 20,50); ?></td>
					</tr>
					<tr>
						<td align="right">*<?=_L("Confirm New Password")?>:</td>
						<td><? NewFormItem($f,$s, 'newpassword2', 'password', 20,50); ?></td>
					</tr>

				</table>
				<div>*<?=_L("Only required for changing your password")?></div>
			</td>
		</tr>
		<tr>
			<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;"><?=_L("Preferences")?>:</th>
			<td  class="bottomBorder">
				<table border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td width="30%"><?=_L("Change Interface Language")?>:</td>
						<td>
							<?
								NewFormItem($f, $s, '_locale', 'selectstart', null, null, "id='locale'");
								foreach($LOCALES as $loc => $lang){
									NewFormItem($f, $s, '_locale', 'selectoption', $lang, $loc);
								}
								NewFormItem($f, $s, '_locale', 'selectend');
							?>
						</td>
					</tr>
					<tr>
						<td colspan="2"><? NewFormItem($f,$s, 'notify', 'checkbox'); ?>&nbsp;<?=_L("Email me when I have a new phone message.")?></td>
					</tr>
					<tr>
						<td colspan="2"><? NewFormItem($f,$s, 'notifysms', 'checkbox', null, "", "id=\"smscheck\" onclick=\"document.getElementById('smsbox').disabled=!this.checked\""); ?>&nbsp;<?=_L("Text me when I have a new phone message.")?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("Mobile Phone for SMS Text")?>:</td>
						<td><? NewFormItem($f,$s, 'sms', 'text', 20, 20, "id=\"smsbox\" ". ($notifysms ? "" : "disabled=\"true\"")); ?></td>
					</tr>

				</table>
			<td>
		</tr>
	</table>

<?
endWindow();
buttons();
include_once("navbottom.inc.php");
?>