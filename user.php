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
include_once("obj/Access.obj.php");
include_once("obj/Permission.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/User.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Phone.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$USER->authorize('manageaccount')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
//get the message to edit from the request params or session
if (isset($_GET['id'])) {
	setCurrentUser($_GET['id']);
	redirect();
}

if ($_POST['id'] == 'new' || isset($_POST['adduser_x'])) {
	$_SESSION['userid'] = NULL;
}

if($_GET['deleterule']) {
	$deleterule = DBSafe($_GET['deleterule']);
	if (customerOwns("user",$_SESSION['userid'])) {
		$query = "delete from userrule where userid = " . $_SESSION['userid'] . " and ruleid = '$deleterule'";
		QuickUpdate($query);
	}

	redirect();
}



/****************** main message section ******************/

$f = "user";
$s = "main";
$reloadform = 0;

$checkpassword = (getSystemSetting("checkpassword","",true)==0) ? getSystemSetting("checkpassword") : 1;
$usernamelength = getSystemSetting("usernamelength","",true) ? getSystemSetting("usernamelength") : 5;
$passwordlength = getSystemSetting("passwordlength","",true) ? getSystemSetting("passwordlength") : 5;

if($checkpassword){
	if($passwordlength < 6) {
		$passwordlength = 6;
	}
	$securityrules = "The password cannot be made from your username/firstname/lastname.  It cannot be a dictionary word and it must be atleast " . $passwordlength . " characters.  It must contain atleast one letter and number";
} else {
	$securityrules = "The password cannot be made from your username/firstname/lastname.  It must be atleast " . $passwordlength . " characters.  It must contain atleast one letter and number";
}

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'submitbutton')) // A hack to be able to differentiate between a submit and an add button click
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
		$usr = new User($_SESSION['userid']);

		$login = trim(GetFormData($f, $s, 'login'));

		// If a user has also submitted dataview rules then prepare an error message in case
		//	those rules get lost, which is what happens when there is an error() call below.
		if (GetFormData($f, $s, "newrulefieldnum") != "" && GetFormData($f, $s, "newrulefieldnum") != -1) {
			$extraMsg = " - You will also need to choose your data view rules again";
		}
				// do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly' . $extraMsg);
		} elseif( !GetFormData($f,$s,'ldap')&& (GetFormData($f, $s, 'password')=="") && (GetFormData($f, $s, 'passwordconfirm')=="")) {
			error('You must enter a password');
		} elseif(!GetFormData($f,$s,'ldap') && ereg("^0*$", GetFormData($f,$s,'password')) && $usr->ldap && $IS_LDAP) {
			error('You must enter a password');
		} elseif( GetFormData($f, $s, 'password') != GetFormData($f, $s, 'passwordconfirm') ) {
			error('Password confirmation does not match' . $extraMsg);
		} elseif( GetFormData($f, $s, 'pincode') != GetFormData($f, $s, 'pincodeconfirm') ) {
			error('Telephone Pin Code confirmation does not match');
		} else if ($phone != null && !Phone::validate($phone) ) {
			if ($IS_COMMSUITE)
				error('The phone number must be 2-6 digits or exactly 10 digits long (including area code)','You do not need to include a 1 for long distance' . $extraMsg);
			else
				error('The phone number must be exactly 10 digits long (including area code)','You do not need to include a 1 for long distance' . $extraMsg);
		} elseif (strlen($login) < $usernamelength) {
			error('Username must be atleast ' . $usernamelength . '  characters' . $extraMsg);
		} elseif(!ereg("^0*$", GetFormData($f,$s,'password')) && !GetFormData($f, $s, 'ldap') && (strlen(GetFormData($f, $s, 'password')) < $passwordlength)){
			error('Password must be atleast ' . $passwordlength . ' characters long');
		} elseif (User::checkDuplicateLogin($login, $USER->customerid, $_SESSION['userid'])) {
			error('This username already exists, please choose another' . $extraMsg);
		} elseif(strlen(GetFormData($f, $s, 'accesscode')) > 0 && User::checkDuplicateAccesscode(GetFormData($f, $s, 'accesscode'), $USER->customerid, $_SESSION['userid'])) {
			$newcode = getNextAvailableAccessCode(DBSafe(GetFormData($f, $s, 'accesscode')), $_SESSION['userid'],  $USER->customerid);
			PutFormData($f, $s, 'accesscode', $newcode, 'number', 'nomin', 'nomax'); // Repopulate the form/session data with the generated code
			error('Your telephone user id number must be unique - one has been generated for you' . $extraMsg);
		} elseif (CheckFormSubmit($f,$s) && !GetFormData($f,$s,"newrulefieldnum")) {
			error('Please select a field');
		} elseif( !ereg("^0*$", GetFormData($f,$s,'password')) && (!ereg("[0-9]", GetFormData($f, $s, 'password')) || !ereg("[a-zA-Z]", GetFormData($f, $s, 'password')))){
			error('Your password must contain atleast one letter and one number', $securityrules);
		} elseif(($issame=isSameUserPass($login, GetFormData($f,$s,'password'), GetFormData($f,$s,'firstname'),GetFormData($f,$s,'lastname'))) && !GetFormData($f,$s,'ldap')) {
			error($issame, $securityrules);
		} elseif($checkpassword && ($iscomplex = isNotComplexPass(GetFormData($f,$s,'password'))) && !ereg("^0*$", GetFormData($f,$s,'password')) && !GetFormData($f,$s,'ldap')){
			error($iscomplex, $securityrules);
		} elseif(GetFormData($f, $s, 'accesscode') === GetformData($f, $s, 'pincode') && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))) {
			error('User ID and Pin code cannot be the same');
		} elseif((strlen(GetFormData($f, $s, 'accesscode')) < 4 || strlen(GetformData($f, $s, 'pincode')) < 4) && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))) {
			error('User ID and Pin code must have length greater than 4.');
		} elseif ((!ereg("^[0-9]*$", GetFormData($f, $s, 'accesscode')) || !ereg("^[0-9]*$", GetformData($f, $s, 'pincode'))) && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))) {
			error('User ID and Pin code must all be numeric');
		} elseif((isAllSameDigit(GetFormData($f, $s, 'accesscode')) || isAllSameDigit(GetFormData($f, $s, 'pincode'))) && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))
					&& (!ereg("^0*$", $number))){
			error('User ID and Pin code cannot have all the same digits');
		} elseif(isSequential(GetFormData($f, $s, 'pincode')) && !$IS_COMMSUITE) {
			error('Cannot have sequential numbers for Pin code');
		} elseif($bademaillist = checkemails(GetFormData($f,$s,"email"))) {
			error("Some emails are invalid", $bademaillist);
		} else {
			// Submit changes

			if ($usr->id == NULL) {
				$usr->enabled = 1;
			}

			PopulateObject($f,$s,$usr,array("accessid","accesscode","firstname","lastname","email"));
			$usr->login = $login;
			$usr->customerid = $USER->customerid;
			$usr->phone = Phone::parse(GetFormData($f,$s,"phone"));
			if($IS_LDAP){
				if(GetFormData($f, $s, "ldap")) {
					$usr->ldap=1;
				} else {
					$usr->ldap=0;
				}
			}
			$usr->update();

			QuickUpdate("delete from userjobtypes where userid = $usr->id");
			if(GetFormData($f,$s,"restricttypes") && count(GetFormData($f,$s,'jobtypes')) > 0)
			foreach(GetFormData($f,$s,'jobtypes') as $type)
				QuickUpdate("insert into userjobtypes values ($usr->id, '" . DBSafe($type) . "')");

			$_SESSION['userid'] = $usr->id;

			if((!$usr->ldap && $IS_LDAP) || !$IS_LDAP){
				// If the password is all 0 characters then it was a default form value, so ignore it
				$password = GetFormData($f, $s, 'password');
				if (!ereg("^0*$", $password)) {
					$usr->setPassword($password);
				}
			}

			// If the pincode is all 0 characters then it was a default form value, so ignore it
			$pincode = GetFormData($f, $s, 'pincode');
			if (!ereg("^0*$", $pincode)) {
				$usr->setPincode($pincode);
			}

			$callerid = Phone::parse(GetFormData($f, $s, 'callerid'));
			if (strlen($callerid) == 0 )
				$callerid = false;
			$usr->setSetting("callerid",$callerid);

			$fieldnum = GetFormData($f,$s,"newrulefieldnum");
			if ($fieldnum != -1 && $usr->id) {
				$type = GetFormData($f,$s,"newruletype");
				$logic = GetFormData($f,$s,"newrulelogical_$type");
				$op = GetFormData($f,$s,"newruleoperator_$type");
				$value = GetFormData($f,$s,"newrulevalue_" . $fieldnum);
				if (count($value) > 0) {
					$rule = new Rule();
					$rule->logical = $logic;
					$rule->op = $op;
					$rule->val = ($type == 'multisearch' && is_array($value)) ? implode("|",$value) : $value;
					$rule->fieldnum = $fieldnum;

					$rule->create();
					//FIXME use UserRule.obj
					$query = "insert into userrule (userid, ruleid) values ($usr->id, $rule->id)";
					Query($query);
				}
				$reloadform = 1;
			} else {
				redirect('users.php');
			}
		}
	}
} else {
	$reloadform = 1;
}

$RULEMODE = array('multisearch' => true, 'text' => false, 'reldate' => false);
if ($_SESSION[userid])
	$RULES = DBFindMany('Rule', "from rule inner join userrule on rule.id = userrule.ruleid where userid = $_SESSION[userid]");
else
	$RULES = array();

if( $reloadform )
{
	ClearFormData($f);

	if (isset($_POST['adduser_x'])) {
		$_SESSION['userid'] = NULL;
		$usr = new User();
		$usr->firstname = get_magic_quotes_gpc() ? stripslashes($_POST['adduserfirst']) : $_POST['adduserfirst'];
		$usr->lastname = get_magic_quotes_gpc() ? stripslashes($_POST['adduserlast']) : $_POST['adduserlast'];
		$usr->enabled = 1;
	} else {
		$usr = new User($_SESSION['userid']);
	}

	$fields = array(
			array("accessid","number","nomin","nomax"),
			array("login","text",1,20,true),
			array("accesscode","number","nomin","nomax"),
			array("firstname","text",1,50,true),
			array("lastname","text",1,50,true),
			array("email","text","nomin","nomax"),
			);

	PopulateForm($f,$s,$usr,$fields);
	PutFormData($f,$s,"phone",Phone::format($usr->phone),"text",2, 20);

	$checked = false;
	$pass = $usr->id ? '00000000' : '';
	PutFormData($f,$s,"password",$pass,"text",1,50);
	PutFormData($f,$s,"passwordconfirm",$pass,"text",1,50);

	PutFormData($f,$s,"pincode",$pass,"number","nomin","nomax");
	PutFormData($f,$s,"pincodeconfirm",$pass,"number","nomin","nomax");

	if ($usr->id)
		$types = QuickQueryList("select jobtypeid from userjobtypes where userid = $usr->id");
	else
		$types = array();

	PutFormData($f,$s,"jobtypes",$types,"array");
	PutFormData($f,$s,"restricttypes",(bool)count($types),"bool",0,1);
	PutFormData($f,$s,"restrictpeople",(bool)count($RULES),"bool",0,1);

	if($IS_LDAP) {
		if($usr->ldap){
			$checked = true;
		}
		PutFormData($f,$s,"ldap",(bool)$checked, "bool", 0, 1);
	}
	PutFormData($f,$s,"newrulefieldnum","");
	PutFormData($f,$s,"newruletype","text","text",1,50);
	PutFormData($f,$s,"newrulelogical_text","and","text",1,50);
	PutFormData($f,$s,"newrulelogical_multisearch","and","text",1,50);
	PutFormData($f,$s,"newruleoperator_text","eq","text",1,50);
	PutFormData($f,$s,"newruleoperator_multisearch","in","text",1,50);
	PutFormData($f,$s,"callerid", Phone::format($usr->getSetting("callerid","",true)), "text", 0, 20);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:users";
$TITLE = 'User Editor: ' . ($_SESSION['userid'] == NULL ? "New User" : GetFormData($f,$s,"firstname") . ' ' . GetFormData($f,$s,"lastname"));
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, 'submitbutton', 'save', 'save'));

startWindow('User Information');
?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th valign="top" width="70" class="windowRowHeader<? if($USER->authorize('manageaccount')) print ' bottomBorder'; ?>" align="right" valign="top" style="padding-top: 6px;">Access Credentials:<br><? print help('User_AccessCredentials', NULL, 'grey'); ?></th>
					<td class="<? if($USER->authorize('manageaccount')) print 'bottomBorder'; ?>">
						<table border="0" cellpadding="1" cellspacing="0">
							<tr>
								<td align="right">First Name:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'firstname', 'text', 20, 50); ?></td>
							</tr>
							<tr>
								<td align="right">Last Name:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'lastname', 'text', 20, 50); ?></td>
							</tr>
							<tr>
								<td align="right">Username:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'login', 'text', 20); ?></td>
							</tr>
							<tr>
								<td align="right">Password:</td>
								<td><? NewFormItem($f,$s, 'password', 'password', 20,50, 'id="passwordfield1"'); ?></td>
								<td>&nbsp;</td>
								<td align="right">Confirm Password:</td>
								<td><? NewFormItem($f,$s, 'passwordconfirm', 'password', 20,50, 'id="passwordfield2"'); ?></td>
							</tr>
							<? if(GetFormData($f,$s,'ldap') && $IS_LDAP) { ?>
								<script>
								new getObj('passwordfield1').obj.disabled=1;
								new getObj('passwordfield2').obj.disabled=1;
								</script>
							<? } ?>
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
								<td align="right">Email:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'email', 'text', 20, 100); ?></td>
							</tr>
							<tr>
								<td align="right">Phone:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'phone', 'text', 20); ?></td>
							</tr>

							<tr>
								<td align="right">Caller&nbsp;ID:</td>
								<td colspan="4"><? NewFormItem($f,$s,"callerid","text", 20, 20); ?></td>
							</tr>
							<?
								if($IS_LDAP) {
							?>
								<tr>
									<td> LDAP Enabled:</td>
									<td><? NewFormItem($f,$s,'ldap','checkbox',NULL,NULL,"onchange=\"new getObj('passwordfield1').obj.disabled=this.checked; new getObj('passwordfield2').obj.disabled=this.checked\"" ); ?></td>
								</tr>
							<?
								}
							?>

						</table>

						<br>Please note: username and password are case-sensitive and must be a minimum of <?=$passwordlength?> characters long.
						<br>Additionally, the telephone user ID and telephone PIN code must be all numeric.
					</td>
				</tr>
				<? if($USER->authorize('manageaccount')) { ?>
				<tr>
					<th valign="top" align="right" class="windowRowHeader bottomBorder" width="70">Restrictions:<br><? print help('User_Restrictions', NULL, 'grey'); ?></th>
					<td class="bottomBorder">
						<table border="0" cellpadding="1" cellspacing="0">
							<tr>
								<td align="right" valign="top" style="padding-top: 4px;">
								 	Access Profile:
								</td>
								<td>
								<?
								NewFormItem($f,$s,'accessid','selectstart');
								$accss = DBFindMany('Access', "from access where customerid = $USER->customerid");
								if(count($accss))
									foreach($accss as $acc)
										NewFormItem($f,$s,'accessid','selectoption',$acc->name,$acc->id);
								else
									NewFormItem($f,$s,'accessid','selectoption','No Access Profiles Defined',0);
								NewFormItem($f,$s,'accessid','selectend');
								?>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<table>
										<tr>
											<td>
												<? NewFormItem($f,$s,'restricttypes','checkbox',NULL,NULL,'id="restricttypes" onclick="clearAllIfNotChecked(this,\'jobtypeselect\');"'); ?>
											</td>
											<td>
												Restrict this user to the following types of jobs
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td align="right" valign="top" style="padding-top: 4px;">Job Types:</td>
								<td>
								<?
									$options = QuickQueryList("select name, id from jobtype where customerid=$USER->customerid and deleted=0 order by priority asc", true);
									if(!count($options))
										$options['No Job Types Defined'] = 0;
									NewFormItem($f,$s,'jobtypes','selectmultiple',3,$options,'id="jobtypeselect" onmousedown="setChecked(\'restricttypes\')"');
								?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<th valign="top" align="right" class="windowRowHeader">Data View:<br><? print help('User_DataView', NULL, 'grey'); ?></th>
					<td>
						<table>
							<tr>
								<td>
									Restrict this user's access to the following data
								</td>
							</tr>
						</table>
					<?
					include('ruleeditform.inc.php');
					?>
					</td>
				</tr>
				<? } ?>
			</table>
		<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>
