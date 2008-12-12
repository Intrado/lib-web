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
		$firstname = GetFormData($f,$s,"firstname");
		$lastname = GetFormData($f,$s,"lastname");
		$zipcode = GetFormData($f,$s,"zipcode");
		$oldpassword = GetFormData($f,$s,"oldpassword");
		$newpassword1 = GetFormData($f, $s, "newpassword1");
		$newpassword2 = GetFormData($f, $s, "newpassword2");
		$notify = GetFormData($f, $s, "notify");
		$notifysms = GetFormData($f, $s, "notifysms");
		$sms = GetFormData($f, $s, "sms");
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
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
			$result = portalUpdatePortalUser($firstname, $lastname, $zipcode, $notifyType, $notifysmsType, $sms);
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
	PutFormData($f, $s, "zipcode", $_SESSION['portaluser']['portaluser.zipcode'], "number", "10000", "99999", true);
	PutFormData($f, $s, "notify",  ($_SESSION['portaluser']['portaluser.notifytype'] == "message") ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, "notifysms",  ($_SESSION['portaluser']['portaluser.notifysmstype'] == "message") ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, "sms", Phone::format($_SESSION['portaluser']['portaluser.sms']), "phone", "2", "20"); // 20 is the max to accomodate formatting chars
}

$PAGE = "account:account";
$TITLE = "Account Information: " . escapehtml($_SESSION['portaluser']['portaluser.firstname']) . " " . escapehtml($_SESSION['portaluser']['portaluser.lastname']);
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'Save'), button("Change Email",NULL, "changeemail.php"));

startWindow('User Information');
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Account Info:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align="right">Email:</td>
						<td><?= escapehtml($_SESSION['portaluser']['portaluser.username']) ?></td>
					</tr>
					<tr>
						<td align="right">First Name:</td>
						<td><? NewFormItem($f,$s, 'firstname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right">Last Name:</td>
						<td><? NewFormItem($f,$s, 'lastname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right">ZIP Code:</td>
						<td><? NewFormItem($f, $s, 'zipcode', 'text', '5'); ?></td>
					</tr>
					<tr>
						<td align="right">*Old Password:</td>
						<td><? NewFormItem($f,$s, 'oldpassword', 'password', 20,50); ?></td>
					</tr>
					<tr>
						<td align="right">*New Password:</td>
						<td><? NewFormItem($f,$s, 'newpassword1', 'password', 20,50); ?></td>
					</tr>
					<tr>
						<td align="right">*Confirm New Password:</td>
						<td><? NewFormItem($f,$s, 'newpassword2', 'password', 20,50); ?></td>
					</tr>

				</table>
				<div>*Only required for changing your password</div>
			</td>
		</tr>
		<tr>
			<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Preferences:</th>
			<td  class="bottomBorder">
				<table border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td colspan="2"><? NewFormItem($f,$s, 'notify', 'checkbox'); ?>&nbsp;Email me when I have a new phone message.</td>
					</tr>
					<tr>
						<td colspan="2"><? NewFormItem($f,$s, 'notifysms', 'checkbox', null, "", "id=\"smscheck\" onclick=\"document.getElementById('smsbox').disabled=!this.checked\""); ?>&nbsp;Text me when I have a new phone message.</td>
					</tr>
					<tr>
						<td align="right">Mobile Phone for Text Messaging:</td>
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