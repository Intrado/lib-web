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


if (isset($_GET['clearrules'])) {
	if (isset($_SESSION['userid'])) {
		QuickUpdate("delete from userrule where userid='" . $_SESSION['userid'] . "'");
	}
	redirect();
}

/*CSDELETEMARKER_START*/
if(!$IS_COMMSUITE && isset($_GET['id'])){
	$id = $_GET['id']+0;
	if(QuickQuery("select count(*) from user where login = 'schoolmessenger' and id = '$id'")){
		redirect('unauthorized.php');
	}
}
/*CSDELETEMARKER_END*/

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	setCurrentUser($_GET['id']);
	redirect();
}
$maxreached = false;
/*CSDELETEMARKER_START*/
if($_SESSION['userid']=== NULL){
	$usercount = QuickQuery("select count(*) from user where enabled = 1 and login != 'schoolmessenger'");
	$maxusers = getSystemSetting("_maxusers", "unlimited");
	if(($maxusers != "unlimited") && $maxusers <= $usercount){
		print '<script language="javascript">window.alert(\'You already have the maximum amount of users.\');window.location="users.php";</script>';
		$maxreached=true;
	}
}
/*CSDELETEMARKER_END*/

if(isset($_GET['deleterule'])) {
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


//TODO: remove all "secure password" code, "checkpassword" setting
$checkpassword = (getSystemSetting("checkpassword","",true)==0) ? getSystemSetting("checkpassword") : 1;
$usernamelength = getSystemSetting("usernamelength",5);
$passwordlength = getSystemSetting("passwordlength",5);

if($checkpassword){
	if($passwordlength < 6) {
		$passwordlength = 6;
	}
	$securityrules = "The username must be at least " . $usernamelength . " characters.  The password cannot be made from your username/firstname/lastname.  It cannot be a dictionary word and it must be at least " . $passwordlength . " characters.  It must contain at least 2 of the following: a letter, a number or a symbol";
} else {
	$securityrules = "The username must be at least " . $usernamelength . " characters.  The password cannot be made from your username/firstname/lastname.  It must be at least " . $passwordlength . " characters.  It must contain at least 2 of the following: a letter, a number or a symbol";
}

if((CheckFormSubmit($f,$s) || CheckFormSubmit($f,'submitbutton')) && !$maxreached) // A hack to be able to differentiate between a submit and an add button click
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
		$callerid = Phone::parse(GetFormData($f,$s, "callerid"));
		$usr = new User($_SESSION['userid']);
		$email = GetFormData($f, $s, "email");
		$emaillist = GetFormData($f, $s, "aremail");
		$emaillist = preg_replace('[,]' , ';', $emaillist);
		$password = trim(GetFormData($f, $s, "password"));
		$passwordconfirm = trim(GetFormData($f, $s, "passwordconfirm"));

		$login = trim(GetFormData($f, $s, 'login'));

		// If a user has also submitted dataview rules then prepare an error message in case
		//	those rules get lost, which is what happens when there is an error() call below.
		if (GetFormData($f, $s, "newrulefieldnum") != "" && GetFormData($f, $s, "newrulefieldnum") != -1) {
			$extraMsg = " - You will also need to choose your data view rules again";
		} else {
			$extraMsg= "";
		}
				// do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly' . $extraMsg);
		} elseif((($IS_LDAP && !GetFormData($f,$s,'ldap')) || !$IS_LDAP) && ($password=="") && ($passwordconfirm=="")) {
			error('You must enter a password');
		} elseif($IS_LDAP && !GetFormData($f,$s,'ldap') && $usr->ldap && ereg("^0*$", GetFormData($f,$s,'password'))) {
			error('You must enter a password');
		} elseif( $password != $passwordconfirm ) {
			error('Password confirmation does not match' . $extraMsg);
		} elseif( GetFormData($f, $s, 'pincode') != GetFormData($f, $s, 'pincodeconfirm') ) {
			error('Telephone Pin Code confirmation does not match' . $extraMsg);
		} elseif (($phone != "") && ($error = Phone::validate($phone))) {
			error($error);
		} elseif(GetFormData($f, $s, "callerid") && strlen($callerid) != 10){
			error('Caller ID must be 10 digits long', 'You do not need to include a 1 for long distance');
		} elseif ((($IS_LDAP && !GetFormData($f,$s,'ldap')) || !$IS_LDAP) && strlen($login) < $usernamelength) {
			error('Username must be at least ' . $usernamelength . ' characters', $securityrules);
		} elseif((($IS_LDAP && !GetFormData($f,$s,'ldap')) || !$IS_LDAP) && !ereg("^0*$", GetFormData($f,$s,'password')) && (strlen($password) < $passwordlength)){
			error('Password must be at least ' . $passwordlength . ' characters long', $securityrules);
		} elseif (User::checkDuplicateLogin($login, $_SESSION['userid'])) {
			error('This username already exists, please choose another' . $extraMsg);
		} elseif(strlen(GetFormData($f, $s, 'accesscode')) > 0 && User::checkDuplicateAccesscode(GetFormData($f, $s, 'accesscode'), $_SESSION['userid'])) {
			$newcode = getNextAvailableAccessCode(DBSafe(GetFormData($f, $s, 'accesscode')), $_SESSION['userid']);
			PutFormData($f, $s, 'accesscode', $newcode, 'number', 'nomin', 'nomax'); // Repopulate the form/session data with the generated code
			error('Your telephone user id number must be unique - one has been generated for you' . $extraMsg);
		} elseif (CheckFormSubmit($f,$s) && !GetFormData($f,$s,"newrulefieldnum")) {
			error('Please select a field');
		} elseif(!passwordcheck(GetFormData($f, $s,'password'))){
			error('Your password must contain at least 2 of the following: a letter, a number or a symbol', $securityrules);
		} elseif( (($IS_LDAP && !GetFormData($f,$s,'ldap')) || !$IS_LDAP) && ($issame=validateNewPassword($login, GetFormData($f,$s,'password'), GetFormData($f,$s,'firstname'),GetFormData($f,$s,'lastname')))) {
			error($issame, $securityrules);
		} elseif( (($IS_LDAP && !GetFormData($f,$s,'ldap')) || !$IS_LDAP) && $checkpassword && ($iscomplex = isNotComplexPass(GetFormData($f,$s,'password'))) && !ereg("^0*$", GetFormData($f,$s,'password'))){
			error($iscomplex, $securityrules);
		} elseif(GetFormData($f, $s, 'accesscode') === GetformData($f, $s, 'pincode') && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))) {
			error('User ID and Pin code cannot be the same');
		} elseif((strlen(GetFormData($f, $s, 'accesscode')) < 4 || strlen(GetformData($f, $s, 'pincode')) < 4) && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))) {
			error('User ID and Pin code must have at least 4 digits');
		} elseif ((!ereg("^[0-9]*$", GetFormData($f, $s, 'accesscode')) || !ereg("^[0-9]*$", GetformData($f, $s, 'pincode'))) && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))) {
			error('User ID and Pin code must all be numeric');
		} elseif((isAllSameDigit(GetFormData($f, $s, 'accesscode')) || isAllSameDigit(GetFormData($f, $s, 'pincode'))) && ((GetFormData($f, $s, 'accesscode') !== "" && GetformData($f, $s, 'pincode')!== ""))
					&& (!ereg("^0*$", GetFormData($f, $s, 'pincode'))) ){
			error('User ID and Pin code cannot have all the same digits');
		} elseif(isSequential(GetFormData($f, $s, 'pincode')) && !$IS_COMMSUITE) {
			error('Cannot have sequential numbers for Pin code');
		} elseif($bademaillist = checkemails($emaillist)) {
			error("These emails are invalid", $bademaillist);
		} elseif(!GetFormData($f,$s,"accessid")){
			error("No access profile was chosen");
		} else {
			// Submit changes

			if ($usr->id == NULL) {
				$usr->enabled = 1;
			}

			PopulateObject($f,$s,$usr,array("accessid","accesscode","firstname","lastname","description"));
			$usr->email = $email;
			$usr->aremail = $emaillist;
			$usr->login = $login;
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
				if(GetFormData($f,$s,"restrictsurveytypes") && count(GetFormData($f,$s,'surveyjobtypes')) > 0)
			foreach(GetFormData($f,$s,'surveyjobtypes') as $surveytype)
				QuickUpdate("insert into userjobtypes values ($usr->id, '" . DBSafe($surveytype) . "')");

			$_SESSION['userid'] = $usr->id;

			if((!$usr->ldap && $IS_LDAP) || !$IS_LDAP){
				// If the password is all 0 characters then it was a default form value, so ignore it
				if (!ereg("^0*$", $password)) {
					$usr->setPassword($password);
				}
			}

			// If the pincode is all 0 characters then it was a default form value, so ignore it
			$pincode = GetFormData($f, $s, 'pincode');
			if (!ereg("^0*$", $pincode)) {
				$usr->setPincode($pincode);
			}

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
				ClearFormData($f);
				redirect('users.php#viewrecent');
			}
		}
	}
} else {
	$reloadform = 1;
}

$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => false);
if ($_SESSION['userid'])
	$RULES = DBFindMany('Rule', "from rule inner join userrule on rule.id = userrule.ruleid where userid = $_SESSION[userid]");
else
	$RULES = array();

if( $reloadform )
{
	ClearFormData($f);


	$usr = new User($_SESSION['userid']);

	$fields = array(
			array("accessid","number","nomin","nomax"),
			array("login","text",1,20,true),
			array("accesscode","number","nomin","nomax"),
			array("firstname","text",1,50,true),
			array("lastname","text",1,50,true),
			array("description","text",0,50),
			array("email","email"),
			array("aremail", "text")
			);

	PopulateForm($f,$s,$usr,$fields);
	PutFormData($f,$s,"phone",Phone::format($usr->phone),"text",2, 20);

	$checked = false;
	$pass = $usr->id ? '00000000' : '';
	PutFormData($f,$s,"password",$pass,"text");
	PutFormData($f,$s,"passwordconfirm",$pass,"text");

	PutFormData($f,$s,"pincode",$pass,"number","nomin","nomax");
	PutFormData($f,$s,"pincodeconfirm",$pass,"number","nomin","nomax");

	if ($usr->id){
		$surveytypes = array();
		$types = QuickQueryList("select ujt.jobtypeid, issurvey from userjobtypes ujt inner join jobtype jt on (jt.id = ujt.jobtypeid) where userid = $usr->id", true);
		foreach($types as $jtid => $issurvey){
			if($issurvey){
				$surveytypes[] = $jtid;
				unset($types[$jtid]);
			}
		}
		$types = array_keys($types);
	}else {
		$types = array();
		$surveytypes = array();
	}
	PutFormData($f,$s,"jobtypes",$types,"array");
	PutFormData($f,$s,"restricttypes",(bool)count($types),"bool",0,1);
	PutFormData($f,$s,"surveyjobtypes",$surveytypes,"array");
	PutFormData($f,$s,"restrictsurveytypes",(bool)count($surveytypes),"bool",0,1);
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
buttons(submit($f, 'submitbutton', 'Save'));

startWindow('User Information');
?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th valign="top" width="70" class="windowRowHeader<? if($USER->authorize('manageaccount')) print ' bottomBorder'; ?>" align="right" valign="top" style="padding-top: 6px;">Access Credentials:<br><? print help('User_AccessCredentials'); ?></th>
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
								<td align="right">Description:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'description', 'text', 20, 50); ?></td>
							</tr>							<tr>
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
							<? if($IS_LDAP && GetFormData($f,$s,'ldap')) { ?>
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
								<td align="right">Email(s):</td>
								<td colspan="4"><? NewFormItem($f,$s, 'email', 'text', 72, 10000); ?></td>
							</tr>
							<tr>
								<td align="right">Auto Report Email(s):</td>
								<td colspan="4"><? NewFormItem($f,$s, 'aremail', 'text', 72, 10000); ?></td>
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
								if($IS_LDAP && $IS_COMMSUITE) {
							?>
								<tr>
									<td> LDAP Enabled:</td>
									<td><? NewFormItem($f,$s,'ldap','checkbox',NULL,NULL,"onclick=\"new getObj('passwordfield1').obj.disabled=this.checked; new getObj('passwordfield2').obj.disabled=this.checked\"" ); ?></td>
								</tr>
							<?
								}
							?>

						</table>

						<br>Please note: Username and password are case-sensitive. The username must be a minimum of <?=$usernamelength?> characters long and the password <?=$passwordlength?> characters long.
						<br>Additionally, the telephone user ID and telephone PIN code must be all numeric.
					</td>
				</tr>
				<? if($USER->authorize('manageaccount')) { ?>
				<tr>
					<th valign="top" align="right" class="windowRowHeader bottomBorder" width="70">Restrictions:<br><? print help('User_Restrictions'); ?></th>
					<td class="bottomBorder">
						<table border="0" cellpadding="1" cellspacing="0">
							<tr>
								<td align="right" valign="top" style="padding-top: 4px;">
								 	Access Profile:
								</td>
								<td>
								<?
								NewFormItem($f,$s,'accessid','selectstart');

								if($IS_COMMSUITE)
									$accss = DBFindMany('Access', "from access");
								/*CSDELETEMARKER_START*/
								else
									$accss = DBFindMany('Access', "from access where name != 'SchoolMessenger Admin'");
								/*CSDELETEMARKER_END*/

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
									// changed query from name, id to id, name; jjl
									$options = QuickQueryList("select id, name from jobtype where deleted=0 and not issurvey order by systempriority, name asc", true);
									if(!count($options))
										$options['No Job Types Defined'] = 0;
									NewFormItem($f,$s,'jobtypes','selectmultiple',3,$options,'id="jobtypeselect" onmousedown="setChecked(\'restricttypes\')"');
								?>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<table>
										<tr>
											<td>
												<? NewFormItem($f,$s,'restrictsurveytypes','checkbox',NULL,NULL,'id="restrictsurveytypes" onclick="clearAllIfNotChecked(this,\'surveyjobtypeselect\'); "'); ?>
											</td>
											<td>
												Restrict this user to the following types of survey jobs
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td align="right" valign="top" style="padding-top: 4px;">Survey Job Types:</td>
								<td>
								<?
									// changed query from name, id to id, name; jjl
									$surveyoptions = QuickQueryList("select id, name from jobtype where deleted=0 and issurvey=1 order by systempriority, name asc", true);
									if(!count($surveyoptions))
										$surveyoptions['No Job Types Defined'] = 0;
									NewFormItem($f,$s,'surveyjobtypes','selectmultiple',3,$surveyoptions,'id="surveyjobtypeselect" onmousedown="setChecked(\'restrictsurveytypes\')"');
								?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<th valign="top" align="right" class="windowRowHeader">Data View:<br><? print help('User_DataView'); ?></th>
					<td>
						Restrict this user's access to the following data<br>
						<a href="?clearrules" onclick="return confirm('Are you sure you want to clear all data view restrictions?');">Clear All</a>
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
