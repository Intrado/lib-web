<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/FieldMap.obj.php");


$person = new Person($_SESSION['personid']);

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();
$subscribeFields = FieldMap::getSubscribeMapNames();

$subscribeFieldValues = array();
foreach ($subscribeFields as $fieldnum => $name) {
	$subscribeFieldValues[$fieldnum] = QuickQueryList("select value from persondatavalues where fieldnum='".$fieldnum."' and editlock=1");
}


$error_failedupdate = "There was an error updating your information";
$error_failedupdatepassword = "There was an error updating your password";
$error_badpassword = "The old password provided is invalid";

$f="subscriber";
$s="main";
$reloadform = 0;
$error = 0;

if (CheckFormSubmit($f,$s)) {
	//check to see if formdata is valid
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);

		//do check
		$firstname = TrimFormData($f,$s,"firstname");
		$lastname = TrimFormData($f,$s,"lastname");
		$oldpassword = TrimFormData($f,$s,"oldpassword");
		$newpassword1 = TrimFormData($f, $s, "newpassword1");
		$newpassword2 = TrimFormData($f, $s, "newpassword2");
		$_SESSION['_locale'] = getFormData($f, $s, "_locale");
		if (CheckFormSection($f, $s)) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(strlen($newpassword1) > 0 && strlen($newpassword1) < 5){
			error("Passwords must be at least 5 characters long");
		} else if($newpassword1 && $passworderror = validateNewPassword($_SESSION['portaluser']['portaluser.username'], $newpassword1, $firstname, $lastname)){
			error($passworderror);
		} else if($newpassword1 != $newpassword2){
			error('Password confirmation does not match');
		} else {
			//submit changes
			
			$person->$firstnameField = $firstname;
			$person->$lastnameField = $lastname;
			
			foreach ($subscribeFields as $fieldnum => $name) {
				$val = GetFormData($f, $s, "fnum_".$fieldnum);
				error_log("VALVAL ".$val);
				
				if ('f' == substr($fieldnum, 0, 1)) {
					$person->$fieldnum = $subscribeFieldValues[$fieldnum][$val];
				} else { // 'g'
					// TODO groupdata
				}
			}
			
			$person->update();

			$_SESSION['subscriber.firstname'] = $person->$firstnameField;
			$_SESSION['subscriber.lastname'] = $person->$lastnameField;

			if ($newpassword1) {
			/*
				$result = portalUpdatePortalUserPassword($newpassword1, $oldpassword);
				if ($result['result'] != "") {
					$updateuser = false;
					$error = 1;
					if(strpos($result['resultdetail'], "oldpassword") !== false){
						error($error_badpassword);
					} else {
						error($error_failedupdatepassword);
					}
				}
			*/
			}
			if (!$error) {
				redirect("start.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if ($reloadform) {
	ClearFormData($f);
	
	PutFormData($f, $s, "firstname", $_SESSION['subscriber.firstname'], "text", "1", "100", true);
	PutFormData($f, $s, "lastname", $_SESSION['subscriber.lastname'], "text", "1", "100", true);
	PutFormData($f, $s, "newpassword1", "", "text");
	PutFormData($f, $s, "newpassword2", "", "text");
	PutFormData($f, $s, "oldpassword", "", "text");
	PutFormData($f, $s, "_locale", $_SESSION['_locale'], "text", "nomin", "nomax");
	
	foreach ($subscribeFields as $fieldnum => $name) {
		$val = $person->$fieldnum;
		PutFormData($f, $s, "fnum_".$fieldnum, $val, "text", "nomin", "nomax");
	}
}

$PAGE = "account:account";
$TITLE = _L("Account Information") . ": " . escapehtml($_SESSION['subscriber.firstname']) . " " . escapehtml($_SESSION['subscriber.lastname']);
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
						<td><?= escapehtml($_SESSION['subscriber.username']) ?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("First Name")?>:</td>
						<td><? NewFormItem($f,$s, 'firstname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("Last Name")?>:</td>
						<td><? NewFormItem($f,$s, 'lastname', 'text', 20,100); ?></td>
					</tr>
					
<?
					foreach ($subscribeFields as $fieldnum => $name) {
?>
					<tr>
						<td align="right"><?=$name ?>:</td>
						<td>
<?
							NewFormItem($f, $s, "fnum_".$fieldnum, 'selectstart', null, null, "id='fnum_".$fieldnum."'");
							foreach ($subscribeFieldValues[$fieldnum] as $index => $value) {
								NewFormItem($f, $s, "fnum_".$fieldnum, 'selectoption', $value, $index);
							}
							NewFormItem($f, $s, "fnum_".$fieldnum, 'selectend');
?>
						</td>
					</tr>
					
<?					
					}
?>					

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
				</table>
			<td>
		</tr>
	</table>

<?
endWindow();
buttons();
include_once("navbottom.inc.php");
?>