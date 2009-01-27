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
include_once("inc/themes.inc.php");

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
$usernamelength = getSystemSetting("usernamelength", 5);
$passwordlength = getSystemSetting("passwordlength", 5);

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

		/* Trim fields that are not processed bellow. */
		TrimFormData($f, $s,'firstname');
		TrimFormData($f, $s,'lastname');

		/* Password should not be trimmed*/
		$password = GetFormData($f, $s, "password");
		$passwordconfirm = GetFormData($f, $s, "passwordconfirm");

		/* Trim and get data from fields that are processed.*/
		$login = TrimFormData($f, $s, 'login');
		$accesscode = TrimFormData($f, $s, 'accesscode');
		$pincode = TrimFormData($f, $s, 'pincode');
		$pincodeconfirm = TrimFormData($f, $s, 'pincodeconfirm');
		$email = TrimFormData($f, $s, "email");

		/* Email list will need a special trim since the list could end with ; etc.
		 * There is no need to put the trimmed data back to the form since
		 * the email list form field does not check for errors.
		 * */
		$emaillist = GetFormData($f, $s, "aremail");
		$emaillist = preg_replace('[,]' , ';', $emaillist);
		$emaillist = trim($emaillist,"\t\n\r\0\x0B,; ");

		$phone = Phone::parse(TrimFormData($f,$s,"phone"));
		$callerid = Phone::parse(TrimFormData($f,$s, "callerid"));


		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} elseif( !$USER->ldap && (GetFormData($f, $s, 'password')=="") && (GetFormData($f, $s, 'passwordconfirm')=="")) {
			error('You must enter a password');
		} elseif ( GetFormData($f, $s, 'password') != GetFormData($f, $s, 'passwordconfirm') ) {
			error('Password confirmation does not match');
		} elseif( strlen($accesscode) > 0 && ( !$pincode || !$pincodeconfirm || $pincode != $pincodeconfirm )) {
			error('Telephone Pin Code confirmation does not match, or is blank');
		} elseif (($phone != "") && ($error = Phone::validate($phone))) {
			error($error);
		} elseif ((GetFormData($f, $s, 'callerid') != "") && (strlen($callerid)!=10)){
			error('Caller ID must be 10 digits long', 'You do not need to include a 1 for long distance');
		} elseif (strlen($login) < $usernamelength && !$USER->ldap) {
			error('Username must be at least ' . $usernamelength . ' characters', $securityrules);
		} elseif(!ereg("^0*$", GetFormData($f,$s,'password')) && (strlen(GetFormData($f, $s, 'password')) < $passwordlength) && !$USER->ldap){
			error('Password must be at least ' . $passwordlength . ' characters long', $securityrules);
		} elseif (User::checkDuplicateLogin($login, $USER->id)) {
			error('This username already exists, please choose another');
		} elseif (strlen($accesscode) > 0 && User::checkDuplicateAccesscode($accesscode, $USER->id)) {
			$newcode = getNextAvailableAccessCode(DBSafe($accesscode), $USER->id);
			PutFormData($f, $s, 'accesscode', $newcode, 'number', 'nomin', 'nomax'); // Repopulate the form/session data with the generated code
			error('Your telephone user id number must be unique - one has been generated for you');
		} elseif (empty($accesscode) && !ereg("^0*$", $pincode)) {
			$newcode = getNextAvailableAccessCode(DBSafe($accesscode), $USER->id);
			PutFormData($f, $s, 'accesscode', $newcode, 'number', 'nomin', 'nomax'); // Repopulate the form/session data with the generated code
			error('Your telephone user id number must be unique - one has been generated for you');
		} elseif(!passwordcheck(GetFormData($f, $s, "password"))){
			error('Your password must contain at least 2 of the following: a letter, a number or a symbol', $securityrules);
		} elseif(($issame=validateNewPassword($login, GetFormData($f,$s,'password'), GetFormData($f,$s,'firstname'),GetFormData($f,$s,'lastname'))) && !$USER->ldap) {
			error($issame, $securityrules);
		} elseif($checkpassword && ($iscomplex = isNotComplexPass(GetFormData($f,$s,'password'))) && !ereg("^0*$", GetFormData($f,$s,'password')) && !$USER->ldap){
			error($iscomplex, $securityrules);
		} elseif($accesscode === $pincode && (($accesscode !== "" && $pincode!== ""))) {
			error('User ID and Pin code cannot be the same');
		} elseif((strlen($accesscode) < 4 || strlen($pincode) < 4) && (($accesscode !== "" && $pincode!== ""))) {
			error('User ID and Pin code must have at least 4 digits');
		} elseif ((!ereg("^[0-9]*$", $accesscode) || !ereg("^[0-9]*$", $pincode)) && (($accesscode !== "" && $pincode!== ""))) {
			error('User ID and Pin code must all be numeric');
		} elseif((isAllSameDigit($accesscode) || isAllSameDigit($pincode)) && (($accesscode !== "" && $pincode!== ""))
			&& (!ereg("^0*$", $pincode))){
			error('User ID and Pin code cannot have all the same digits');
		} elseif( isSequential($pincode)) {
			error('Cannot have sequential numbers for Pin code');
		} elseif($bademaillist = checkemails($emaillist)) {
			error("These emails are invalid", $bademaillist);
		} elseif(strtotime(GetFormData($f, $s, 'callearly')) >= strtotime(GetFormData($f, $s, 'calllate'))) {
			error("The earliest call time must be before the latest call time");
		} else if(!eregi("^[0-9A-F]{6}$", TrimFormData($f, $s, "_brandprimary"))){
			error("That is not a valid 'Primary Color'. Please enter only a 6 digit hexadecimal value");
		} else if (GetFormData($f, $s, "_brandratio") < 0 || GetFormData($f, $s, "_brandratio") > .5) {
			error("The ratio of primary to background can only be between 0 and .5(50%)");
		} else {
			//submit changes
			PopulateObject($f,$s,$USER,array("accesscode","firstname","lastname"));
			$USER->login = $login;
			$USER->phone = $phone;
			$USER->email = $email;
			$USER->aremail = $emaillist;
			$USER->update();

			// If the password is all 0 characters then it was a default form value, so ignore it
			if(!$USER->ldap) {
				$newpassword = GetFormData($f, $s, 'password');
				if (!ereg("^0*$", $newpassword))
					$USER->setPassword($newpassword);
			}

			// If the pincode is all 0 characters then it was a default form value, so ignore it
			$newpin = $pincode;
			if (!ereg("^0*$", $newpin))
				$USER->setPincode($newpin);


			//save prefs

			$USER->setSetting("callearly",GetFormData($f, $s, 'callearly'));
			$USER->setSetting("calllate",GetFormData($f, $s, 'calllate'));
			$USER->setSetting("callmax",GetFormData($f, $s, 'callmax'));
			$USER->setSetting("maxjobdays",GetFormData($f, $s, 'maxjobdays'));

			//dont save any callerid stuff if they don't have access to change it
			if (strlen($callerid) == 0)
				$callerid = false;
			if ($USER->authorize('setcallerid')) {
				$USER->setSetting("callerid",$callerid);
				/*CSDELETEMARKER_START*/
				// if customer has callback feature, and
				if (getSystemSetting('_hascallback', false)) {
					$radio = "0";
					if (GetFormData($f, $s, "radiocallerid") == "byuser")
						$radio = "1";
					$USER->setSetting('prefermycallerid', $radio);
				}
				/*CSDELETEMARKER_END*/
			}


			if (GetFormData($f, $s, "themeoverride")){
				$USER->setSetting("_brandtheme", GetFormData($f, $s, "_brandtheme"));
				$USER->setSetting("_brandtheme1", $COLORSCHEMES[GetFormData($f, $s, "_brandtheme")]["_brandtheme1"]);
				$USER->setSetting("_brandtheme2", $COLORSCHEMES[GetFormData($f, $s, "_brandtheme")]["_brandtheme2"]);
				$_SESSION['colorscheme']['_brandtheme'] = GetFormData($f, $s, "_brandtheme");
				$_SESSION['colorscheme']['_brandtheme1'] = $COLORSCHEMES[GetFormData($f, $s, "_brandtheme")]["_brandtheme1"];
				$_SESSION['colorscheme']['_brandtheme2'] = $COLORSCHEMES[GetFormData($f, $s, "_brandtheme")]["_brandtheme2"];



				$USER->setSetting("_brandprimary", GetFormData($f, $s, "_brandprimary"));
				$_SESSION['colorscheme']['_brandprimary'] = GetFormData($f, $s, "_brandprimary");



				$USER->setSetting("_brandratio", GetFormData($f, $s, "_brandratio"));
				$_SESSION['colorscheme']['_brandratio'] = GetFormData($f, $s, "_brandratio");
			} else {

				$USER->setSetting("_brandtheme", "");
				$USER->setSetting("_brandtheme1", "");
				$USER->setSetting("_brandtheme2", "");
				$USER->setSetting("_brandprimary", "");
				$USER->setSetting("_brandratio", "");

				$_SESSION['colorscheme']['_brandtheme'] = getSystemSetting("_brandtheme");
				$_SESSION['colorscheme']['_brandtheme1'] = getSystemSetting("_brandtheme1");
				$_SESSION['colorscheme']['_brandtheme2'] = getSystemSetting("_brandtheme2");
				$_SESSION['colorscheme']['_brandprimary'] = getSystemSetting("_brandprimary");
				$_SESSION['colorscheme']['_brandratio'] = getSystemSetting("_brandratio");

			}


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
			array("email","email"),
			array("aremail", "text")
			);

	PopulateForm($f,$s,$USER,$fields);
	PutFormData($f,$s,"phone",Phone::format($USER->phone),"text",2, 20);

	$pass = $USER->id ? '00000000' : '';
	PutFormData($f,$s,"password",$pass,"text");
	PutFormData($f,$s,"passwordconfirm",$pass,"text");
	$pass = $USER->accesscode ? '00000000' : '';
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
		$maxjobdays = 1;
	} else {
		$maxjobdays = min($USER->getSetting("maxjobdays"), $ACCESS->getValue('maxjobdays'));
	}

	PutFormData($f, $s, 'maxjobdays', $maxjobdays, 'number', 1, 7, true);

	// if callerid is blank
	//default to empty string because if set to empty string, setting will not be set and system default will be used
	$callerid = $USER->getSetting("callerid","");
	PutFormData($f,$s,"callerid", Phone::format($callerid), "phone", 10, 10);

	/*CSDELETEMARKER_START*/
		// if _hascallback feature, then use customer inboundnumber, aka toll free
		// else no callback, use customer default callerid
	// if the user prefers their callerid, or they have no setting preference but have set callerid then default to "byuser"
	if (($USER->getSetting("prefermycallerid","0") == "1") ||
		($USER->getSetting("prefermycallerid") === false && $callerid != "")) {
		$radio = "byuser";
	} else {
		$radio = "bydefault"; // use customer inboundnumber (aka toll free number)
	}
	PutFormData($f, $s, "radiocallerid", $radio);
	/*CSDELETEMARKER_END*/

	PutFormData($f, $s, "_brandtheme", $USER->getSetting('_brandtheme', getSystemSetting('_brandtheme')), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "_brandratio", $USER->getSetting('_brandratio', getSystemSetting('_brandratio')), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "_brandprimary", $USER->getSetting('_brandprimary', getSystemSetting('_brandprimary')), "text", "nomin", "nomax", true);
	$themechecked = 0;
	if($USER->getSetting('_brandtheme')){
		$themechecked = 1;
	}
	PutFormData($f, $s, "themeoverride", $themechecked, "bool", 0, 1);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$readonly = $USER->importid != null;

$PAGE = "start:account";
$TITLE = "Account Information: " . escapehtml($USER->firstname) . " " . escapehtml($USER->lastname);

include_once("nav.inc.php");

?><script src="script/picker.js?<?=rand()?>"></script><?

NewForm($f);
buttons(submit($f, $s, 'Save'));

startWindow('User Information');
?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Access Credentials:<br><? print help('Account_AccessCredentials'); ?></th>
					<td class="bottomBorder">
						<table border="0" cellpadding="1" cellspacing="0">
							<tr>
								<td align="right">First Name:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $USER->firstname;
								} else {
									NewFormItem($f,$s, 'firstname', 'text', 20,50);
								} ?>
								</td>
							</tr>
							<tr>
								<td align="right">Last Name:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $USER->lastname;
								} else {
									NewFormItem($f,$s, 'lastname', 'text', 20,50);
								} ?>
								</td>
							</tr>
							<tr>
								<td align="right">Username:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $USER->login;
								} else {
									NewFormItem($f,$s, 'login', 'text', 20);
								} ?>
								</td>
							</tr>
							<?
								if(!$USER->ldap) {
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
								<td colspan="4">
								<? if ($readonly) {
									echo $USER->accesscode;
								} else {
									NewFormItem($f,$s, 'accesscode', 'text', 10);
								} ?>
								</td>
							</tr>
							<tr>
								<td align="right">Telephone Pin Code #:</td>
								<td><? NewFormItem($f,$s, 'pincode', 'password', 20,100); ?></td>
								<td>&nbsp;</td>
								<td align="right">Telephone Pin Code #:</td>
								<td><? NewFormItem($f,$s, 'pincodeconfirm', 'password', 20,100); ?></td>
							</tr>
							<tr>
								<td align="right">Email:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $USER->email;
								} else {
									NewFormItem($f,$s, 'email', 'text', 72,10000);
								} ?>
								</td>
							</tr>
							<tr>
								<td align="right">Auto Report Email(s):</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $USER->aremail;
								} else {
									NewFormItem($f,$s, 'aremail', 'text', 72,10000);
								} ?>
								</td>
							</tr>
							<tr>
								<td align="right">Phone:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo Phone::format($USER->phone);
								} else {
									NewFormItem($f,$s, 'phone', 'text', 20);
								} ?>
								</td>
							</tr>
						</table>

<? /*CSDELETEMARKER_START*/
						if($USER->authorize('loginphone') && !$IS_COMMSUITE) {
							$tollfree = Phone::format(getSystemSetting("inboundnumber"));
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
					<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Notification Defaults:</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="1" cellspacing="0" width="70%">

							<tr>
								<td colspan="2">Default Delivery Window:</td>
							</tr>
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
								<td>Days to run <?= help('Account_NumDays', NULL, "small"); ?></td>
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
<?
if ($USER->authorize('setcallerid')) {
	/*CSDELETEMARKER_START*/
	if (getSystemSetting('_hascallback', false)) {
?>
							<tr>
								<td><? NewFormItem($f, $s, "radiocallerid", "radio", null, "bydefault",""); ?> Use toll free Caller&nbsp;ID</td>
								<td><? echo Phone::format(getSystemSetting('inboundnumber')); ?></td>
							</tr>
							<tr>
								<td><? NewFormItem($f, $s, "radiocallerid", "radio", null, "byuser",""); ?> Use my Caller ID</td>
								<td><? NewFormItem($f,$s,"callerid","text", 20, 20); ?></td>
							</tr>
<?
	} else {
	/*CSDELETEMARKER_END*/
?>
							<tr>
								<td>Caller&nbsp;ID <?= help('Account_CallerID',NULL,"small"); ?></td>
								<td>
								<? if ($readonly) {
									echo Phone::format($callerid);
								} else {
									NewFormItem($f,$s,"callerid","text", 20, 20);
								} ?>
								</td>
							</tr>
<?
	/*CSDELETEMARKER_START*/
	}
	/*CSDELETEMARKER_END*/
}
?>
						</table>
					</td>
				</tr>
				<tr>
					<th valign="top" width="70" class="windowRowHeader" align="right" valign="top" style="padding-top: 6px;">Display Defaults:</th>
					<td>
						<table border="0" cellpadding="1" cellspacing="0">
							<tr>
								<td width="30%">Customize Theme</td>
								<td><? NewFormItem($f, $s, "themeoverride", "checkbox", null, null, "id='themeoverride' onclick='disablethemes(this.checked)'"); ?></td>
							</tr>
							<tr>
								<td>Color Theme<? print help('Account_ColorTheme', NULL, "small"); ?></td>
								<td>
									<?
										NewFormItem($f, $s, '_brandtheme', 'selectstart', null, null, "id='themes' onchange='resetPrimaryAndRatio(this.value)'");
										foreach($COLORSCHEMES as $theme => $scheme){
											NewFormItem($f, $s, '_brandtheme', 'selectoption', $scheme['displayname'], $theme);
										}
										NewFormItem($f, $s, '_brandtheme', 'selectend');
									?>
								</td>
							</tr>
							<tr>
								<td>Primary Color(in hex)<? print help('Account_PrimaryColor', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, "_brandprimary", "text", 0, 10, "id='brandprimary'") ?><img src="img/sel.gif" onclick="TCP.popup(new getObj('brandprimary').obj)"/></td>
							</tr>
							<tr>
								<td>Ratio of Primary to Background<? print help('Account_Ratio', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, "_brandratio", "text", 0, 3, "id='brandratio'") ?></td>
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
<script>

	var colorscheme = new Array();

<?
	//Make js array of colorschemes
	foreach($COLORSCHEMES as $index => $properties){
?>
		colorscheme['<?=$index?>'] = new Array();
		colorscheme['<?=$index?>']['_brandprimary'] = '<?=$properties['_brandprimary']?>';
		colorscheme['<?=$index?>']['_brandratio'] = '<?=$properties['_brandratio']?>';
<?
	}
?>

	disablethemes(new getObj('themeoverride').obj.checked);

	function resetPrimaryAndRatio(value){

		new getObj('brandprimary').obj.value = colorscheme[value]['_brandprimary'];
		new getObj('brandratio').obj.value = colorscheme[value]['_brandratio'];
	}

	function disablethemes(checked){
		if(checked){
			new getObj('themes').obj.disabled=false;
			new getObj('brandprimary').obj.disabled=false;
			new getObj('brandratio').obj.disabled=false;
		} else {
			new getObj('themes').obj.disabled=true;
			new getObj('brandprimary').obj.disabled=true;
			new getObj('brandratio').obj.disabled=true;
		}
	}

</script>