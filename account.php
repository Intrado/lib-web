<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/text.inc.php");
include_once("obj/Phone.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managemyaccount')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

/****************** main message section ******************/

$f = "user";
$s = "main";
$reloadform = 0;

$checkpassword = (getSystemSetting("checkpassword")==0) ? getSystemSetting("checkpassword") : 1;
$usernamelength = getSystemSetting("usernamelength") ? getSystemSetting("usernamelength") : 5;
$passwordlength = getSystemSetting("passwordlength") ? getSystemSetting("passwordlength") : 5;

if($checkpassword){
	if($passwordlength < 6) {
		$passwordlength = 6;
	}
	$securityrules = "The username must be at least " . $usernamelength . " characters.  The password cannot be made from your username/firstname/lastname.  It cannot be a dictionary word and it must be at least " . $passwordlength . " characters.  It must contain at least one letter and number";
} else {
	$securityrules = "The username must be at least " . $usernamelength . " characters.  The password cannot be made from your username/firstname/lastname.  It must be at least " . $passwordlength . " characters.  It must contain at least one letter and number";
}

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

		$phone = Phone::parse(GetFormData($f,$s,"phone"));
		$callerid = Phone::parse(GetFormData($f, $s, 'callerid'));
		$login = trim(GetFormData($f, $s, 'login'));
		$emaillist = GetFormData($f, $s, "email");
		$emaillist = preg_replace('[,]' , ';', $emaillist);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} elseif( !$USER->ldap && (GetFormData($f, $s, 'password')=="") && (GetFormData($f, $s, 'passwordconfirm')=="")) {
			error('You must enter a password');
		} elseif ( GetFormData($f, $s, 'password') != GetFormData($f, $s, 'passwordconfirm') ) {
			error('Password confirmation does not match');
		} elseif( GetFormData($f, $s, 'pincode') != GetFormData($f, $s, 'pincodeconfirm') ) {
			error('Telephone Pin Code confirmation does not match');
		} elseif (($phone != "") && ($error = Phone::validate($phone))) {
			error($error);
		} elseif (($callerid != "") && (strlen($callerid)!=10)){
			error('Caller ID must be 10 digits long', 'You do not need to include a 1 for long distance');
		} elseif (strlen($login) < $usernamelength && !$USER->ldap) {
			error('Username must be at least ' . $usernamelength . ' characters', $securityrules);
		} elseif(!ereg("^0*$", GetFormData($f,$s,'password')) && (strlen(GetFormData($f, $s, 'password')) < $passwordlength) && !$USER->ldap){
			error('Password must be at least ' . $passwordlength . ' characters long', $securityrules);
		} elseif (User::checkDuplicateLogin($login, $USER->customerid, $USER->id)) {
			error('This username already exists, please choose another');
		} elseif (strlen(GetFormData($f, $s, 'accesscode')) > 0 && User::checkDuplicateAccesscode(GetFormData($f, $s, 'accesscode'), $USER->customerid, $USER->id)) {
			$newcode = getNextAvailableAccessCode(DBSafe(GetFormData($f, $s, 'accesscode')), $USER->id,  $USER->customerid);
			PutFormData($f, $s, 'accesscode', $newcode, 'number', 'nomin', 'nomax'); // Repopulate the form/session data with the generated code
			error('Your telephone user id number must be unique - one has been generated for you');
		} elseif( !ereg("^0*$", GetFormData($f,$s,'password')) && (!ereg("[0-9]", GetFormData($f, $s, 'password')) || !ereg("[a-zA-Z]", GetFormData($f, $s, 'password')))){
			error('Your password must contain at least one letter and one number', $securityrules);
		} elseif($issame=isSameUserPass($login, GetFormData($f,$s,'password'), GetFormData($f,$s,'firstname'),GetFormData($f,$s,'lastname')) && !$USER->ldap) {
			error($issame, $securityrules);
		} elseif($checkpassword && ($iscomplex = isNotComplexPass(GetFormData($f,$s,'password'))) && !ereg("^0*$", GetFormData($f,$s,'password')) && !$USER->ldap){
			error($iscomplex, $securityrules);
		} elseif(GetFormData($f, $s, 'accesscode') === GetformData($f, $s, 'pincode') && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))) {
			error('User ID and Pin code cannot be the same');
		} elseif((strlen(GetFormData($f, $s, 'accesscode')) < 4 || strlen(GetformData($f, $s, 'pincode')) < 4) && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))) {
			error('User ID and Pin code must have length greater than 4.');
		} elseif ((!ereg("^[0-9]*$", GetFormData($f, $s, 'accesscode')) || !ereg("^[0-9]*$", GetformData($f, $s, 'pincode'))) && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))) {
			error('User ID and Pin code must all be numeric');
		} elseif((isAllSameDigit(GetFormData($f, $s, 'accesscode')) || isAllSameDigit(GetFormData($f, $s, 'pincode'))) && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))
			&& (!ereg("^0*$", GetFormData($f, $s, 'pincode')))){
			error('User ID and Pin code cannot have all the same digits');
		} elseif( isSequential(GetFormData($f, $s, 'pincode'))) {
			error('Cannot have sequential numbers for Pin code');
		} elseif($bademaillist = checkemails($emaillist)) {
			error("These emails are invalid", $bademaillist);
		} elseif(GetFormData($f, $s, 'callearly') >= GetFormData($f, $s, 'calllate')) {
			error("The earlist call time must be before the latest call time");
		} else {
			//submit changes
			PopulateObject($f,$s,$USER,array("accesscode","firstname","lastname"));
			$USER->login = $login;
			$USER->phone = Phone::parse(GetFormData($f,$s,"phone"));
			$USER->email = $emaillist;
			$USER->update();

			// If the password is all 0 characters then it was a default form value, so ignore it
			if((!$USER->ldap && $IS_LDAP )|| !$IS_LDAP) {
				$newpassword = GetFormData($f, $s, 'password');
				if (!ereg("^0*$", $newpassword))
					$USER->setPassword($newpassword);
			}

			// If the pincode is all 0 characters then it was a default form value, so ignore it
			$newpin = GetFormData($f, $s, 'pincode');
			if (!ereg("^0*$", $newpin))
				$USER->setPincode($newpin);


			//save prefs

			$USER->setSetting("callearly",GetFormData($f, $s, 'callearly'));
			$USER->setSetting("calllate",GetFormData($f, $s, 'calllate'));
			$USER->setSetting("callmax",GetFormData($f, $s, 'callmax'));
			$USER->setSetting("maxjobdays",GetFormData($f, $s, 'maxjobdays'));
			$USER->setSetting("callall",GetFormData($f, $s, 'callall'));

			//dont save any callerid stuff if they don't have access to change it
			if (strlen($callerid) == 0 )
				$callerid = false;
			if ($USER->authorize('setcallerid'))
				$USER->setSetting("callerid",$callerid);

			redirect("start.php");
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	$fields = array(
			array("login","text",1,20,true),
			array("accesscode","number","nomin","nomax"),
			array("firstname","text",1,50,true),
			array("lastname","text",1,50,true),
			array("email","text")
			);

	PopulateForm($f,$s,$USER,$fields);
	PutFormData($f,$s,"phone",Phone::format($USER->phone),"text",2, 20);

	$pass = $USER->id ? '00000000' : '';
	PutFormData($f,$s,"password",$pass,"text");
	PutFormData($f,$s,"passwordconfirm",$pass,"text");
	PutFormData($f,$s,"pincode",$pass,"number","nomin","nomax");
	PutFormData($f,$s,"pincodeconfirm",$pass,"number","nomin","nomax");

	//prefs

	//Preferred message delivery window:
	PutFormData($f,$s,"callearly", $USER->getCallEarly() , "text",1,50,true);
	PutFormData($f,$s,"calllate", $USER->getCallLate(), "text",1,50,true);

	//Maximum call attempts
	if (($callmax = $USER->getSetting("callmax")) === false) {
		$callmax = min(4,$ACCESS->getValue('callmax'));
	} else {
		$callmax = min($USER->getSetting("callmax"), $ACCESS->getValue('callmax'));
	}
	PutFormData($f,$s,"callmax", $callmax, "text",1,50,true);

	//Number of days for jobs to run


	if (($maxjobdays = $USER->getSetting("maxjobdays")) === false) {
		$maxjobdays = min(2,$ACCESS->getValue('maxjobdays'));
	} else {
		$maxjobdays = min($USER->getSetting("maxjobdays"), $ACCESS->getValue('maxjobdays'));
	}

	PutFormData($f, $s, 'maxjobdays', $maxjobdays, 'number', 1, 7, true);

	//Call every available phone number for each person
	PutFormData($f,$s,"callall",$USER->getDefaultAccessPref("callall","0"), "bool",0,1);

	//Default caller ID
	//default to system setting unless user has a pref
	$callerid = $USER->getSetting("callerid","");
	PutFormData($f,$s,"callerid", Phone::format($callerid), "text", 0, 20);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "start:account";
$TITLE = "Account Information: $USER->firstname $USER->lastname";

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'save'));

startWindow('User Information');
?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Access Credentials:<br><? print help('Account_AccessCredentials', NULL, 'grey'); ?></th>
					<td class="bottomBorder">
						<table border="0" cellpadding="1" cellspacing="0">
							<tr>
								<td align="right">First Name:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'firstname', 'text', 20,50); ?></td>
							</tr>
							<tr>
								<td align="right">Last Name:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'lastname', 'text', 20,50); ?></td>
							</tr>
							<tr>
								<td align="right">Username:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'login', 'text', 20); ?></td>
							</tr>
							<?
								if((!$USER->ldap && $IS_LDAP) || !$IS_LDAP) {
							?>
								<tr>
									<td align="right">Password:</td>
									<td><? NewFormItem($f,$s, 'password', 'password', 20,50); ?></td>
									<td>&nbsp;</td>
									<td align="right">Confirm Password:</td>
									<td><? NewFormItem($f,$s, 'passwordconfirm', 'password', 20,50); ?></td>
								</tr>
							<?
								}
							?>
							<tr>
								<td align="right">Telephone User ID#:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'accesscode', 'text', 10); ?></td>
							</tr>
							<tr>
								<td align="right">Telephone Pin Code #:</td>
								<td><? NewFormItem($f,$s, 'pincode', 'password', 20,100); ?></td>
								<td>&nbsp;</td>
								<td align="right">Telephone Pin Code #:</td>
								<td><? NewFormItem($f,$s, 'pincodeconfirm', 'password', 20,100); ?></td>
							</tr>
							<tr>
								<td align="right">Email(s):</td>
								<td colspan="4"><? NewFormItem($f,$s, 'email', 'text', 72,10000); ?></td>
							</tr>
							<tr>
								<td align="right">Phone:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'phone', 'text', 20); ?></td>
							</tr>

						</table>
						
<? /*CSDELETEMARKER_START*/
						if($USER->authorize('loginphone') && !$IS_COMMSUITE) {
							$query = "Select inboundnumber from customer where customer.id='$USER->customerid'";
							$tollfree = Phone::format(QuickQuery($query));
?>
							<br>Your toll free number is: <?=$tollfree?>
<?
						}
	/*CSDELETEMARKER_END*/
?>
						<br>Please note: Username and password are case-sensitive. The username must be a minimum of <?=$usernamelength?> characters long and the password <?=$passwordlength?> characters long.
						<br>Additionally, the telephone user ID and telephone PIN code must be all numeric.
					</td>
				</tr>
				<tr>
					<th valign="top" width="70" class="windowRowHeader" align="right" valign="top" style="padding-top: 6px;">Notification Defaults:</th>
					<td>
						<table border="0" cellpadding="1" cellspacing="0">

							<tr>
								<td colspan="2">Default Delivery Window:</td>
							<tr>
								<td width="30%">&nbsp;&nbsp;Earliest <?= help('Account_PhoneEarliestTime', NULL, 'small') ?></td>
								<td><? time_select($f,$s,"callearly", NULL, NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate')); ?></td>
							</tr>
							<tr>
								<td>&nbsp;&nbsp;Latest <?= help('Account_PhoneLatestTime', NULL, 'small') ?></td>
								<td><? time_select($f,$s,"calllate", NULL, NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate')); ?></td>
							</tr>
							<tr>
								<td>Call attempts <?= help('Account_PhoneMaxAttempts', NULL, 'small')  ?></td>
								<td>
									<?
									$max = first($ACCESS->getValue('callmax'), 1);
									NewFormItem($f,$s,"callmax","selectstart");
									for($i = 1; $i <= $max; $i++)
									NewFormItem($f,$s,"callmax","selectoption",$i,$i);
									NewFormItem($f,$s,"callmax","selectend");
									?>
								</td>
							</tr>

							<tr>
								<td>Days to run <?= help('Job_SettingsNumDays', NULL, "small"); ?></td>
								<td>
								<?
								NewFormItem($f, $s, 'maxjobdays', "selectstart");
								$maxdays = $ACCESS->getValue('maxjobdays');
								if ($maxdays == null) {
									$maxdays = 7; // Max out at 7 days if the permission is not set.
								}
								for ($i = 1; $i <= $maxdays; $i++) {
									NewFormItem($f, $s, 'maxjobdays', "selectoption", $i, $i);
								}
								NewFormItem($f, $s, 'maxjobdays', "selectend");
								?>
								</td>
							</tr>
<? if ($USER->authorize('setcallerid')) { ?>
							<tr>
									<td>Caller&nbsp;ID <?= help('Job_CallerID',NULL,"small"); ?></td>
									<td><? NewFormItem($f,$s,"callerid","text", 20, 20); ?></td>
							</tr>
<? } ?>

							<tr>
								<td>Call every available phone number for each person <?= help('Job_PhoneCallAll', NULL, 'small') ?></td>
								<td><? NewFormItem($f,$s,"callall","checkbox",1); ?>Call all phone numbers</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>