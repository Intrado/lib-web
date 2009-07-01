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
include_once("obj/Access.obj.php");
include_once("obj/Permission.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/User.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Phone.obj.php");
include_once("ruleeditform.inc.php");
require_once("inc/rulesutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$USER->authorize('manageaccount')) {
	redirect('unauthorized.php');
}


if (isset($_GET['clearrules'])) {
	if (isset($_SESSION['userid'])) {
		$usr = new User($_SESSION['userid']);

		// always remove Ffield and Gfield restrictions
		// optionally remove Cfield restrictions, only if 'by data'
		$query = "select id from rule r join userrule ur where ur.userid=$usr->id and r.id=ur.ruleid";
		if ($usr->staffpkey != null && strlen($usr->staffpkey) > 0) {
			$query = $query . " and (fieldnum like 'f%' or fieldnum like 'g%')";
		}
		$ruleids = QuickQueryList($query);
		if (count($ruleids) > 0) {
			$csv = implode("," , $ruleids);
			QuickUpdate("delete from userrule where ruleid in ($csv)");
			QuickUpdate("delete from rule where id in ($csv)");
		}
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

if (isset($_GET['resetpass'])) {
	// NOTE: form is not saved by this button, uses existing email from database record

	if (isset($_SESSION['userid'])) {
		$usr = new User($_SESSION['userid']);
		global $CUSTOMERURL;
		forgotPassword($usr->login, $CUSTOMERURL);  // TODO this takes a few seconds...
		redirect();
	}
}

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
	$query = "delete from userrule where userid = " . $_SESSION['userid'] . " and ruleid = '$deleterule'";
	QuickUpdate($query);

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

if((CheckFormSubmit($f,$s) || CheckFormSubmit($f,'submitbutton') || CheckFormSubmit($f,'applybutton')) && !$maxreached) // A hack to be able to differentiate between a submit and an add button click
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

		$usr = new User($_SESSION['userid']);

		/* Trim fields that are not processed bellow. */
		TrimFormData($f, $s,'firstname');
		TrimFormData($f, $s,'lastname');
		TrimFormData($f, $s,'description');

		
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
						
		if (GetFormData($f, $s, "radioselect") == "bydata") {
			$staffid = "";
		} else {
			$staffid = trim(GetFormData($f, $s, 'staffid'));
		}

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
		} elseif( strlen($accesscode) > 0 && ( !$pincode || !$pincodeconfirm || $pincode != $pincodeconfirm )) {
			error('Telephone Pin Code confirmation does not match, or is blank' . $extraMsg);
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
		} elseif (User::checkDuplicateStaffID($staffid, $_SESSION['userid'])) {
			error('This staff ID already exists, please choose another' . $extraMsg);
		} elseif(strlen($accesscode) > 0 && User::checkDuplicateAccesscode($accesscode, $_SESSION['userid'])) {
			$accesscode = getNextAvailableAccessCode(DBSafe($accesscode), $_SESSION['userid']);
			PutFormData($f, $s, 'accesscode', $accesscode, 'number', 'nomin', 'nomax'); // Repopulate the form/session data with the generated code
			error('Your telephone user id number must be unique - one has been generated for you' . $extraMsg);
		} elseif (empty($accesscode) && !ereg("^0*$", $pincode)) {
			$accesscode = getNextAvailableAccessCode("0000", $_SESSION['userid']);
			PutFormData($f, $s, 'accesscode', $accesscode, 'number', 'nomin', 'nomax'); // Repopulate the form/session data with the generated code
			error('Your telephone user id number must be unique - one has been generated for you' . $extraMsg);
		} elseif (CheckFormSubmit($f,$s) && !GetFormData($f,$s,"newrulefieldnum")) {
			error('Please select a field');
		} elseif(!passwordcheck(GetFormData($f, $s,'password'))){
			error('Your password must contain at least 2 of the following: a letter, a number or a symbol', $securityrules);
		} elseif( (($IS_LDAP && !GetFormData($f,$s,'ldap')) || !$IS_LDAP) && ($issame=validateNewPassword($login, GetFormData($f,$s,'password'), GetFormData($f,$s,'firstname'),GetFormData($f,$s,'lastname')))) {
			error($issame, $securityrules);
		} elseif( (($IS_LDAP && !GetFormData($f,$s,'ldap')) || !$IS_LDAP) && $checkpassword && ($iscomplex = isNotComplexPass(GetFormData($f,$s,'password'))) && !ereg("^0*$", GetFormData($f,$s,'password'))){
			error($iscomplex, $securityrules);
		} elseif($accesscode === $pincode && (($accesscode !== "" && $pincode!== ""))) {
			error('User ID and Pin code cannot be the same');
		} elseif((strlen($accesscode) < 4 || strlen($pincode) < 4) && (($accesscode !== "" && $pincode!== ""))) {
			error('User ID and Pin code must have at least 4 digits');
		} elseif ((!ereg("^[0-9]*$", $accesscode) || !ereg("^[0-9]*$", $pincode)) && (($accesscode !== "" && $pincode!== ""))) {
			error('User ID and Pin code must all be numeric');
		} elseif((isAllSameDigit($accesscode) || isAllSameDigit($pincode)) && (($accesscode !== "" && $pincode!== ""))
					&& (!ereg("^0*$", $pincode)) ){
			error('User ID and Pin code cannot have all the same digits');
		} elseif(isSequential($pincode) && !$IS_COMMSUITE) {
			error('Cannot have sequential numbers for Pin code');
		} elseif($bademaillist = checkemails($emaillist)) {
			error("These emails are invalid", $bademaillist);
		} elseif(!GetFormData($f,$s,"accessid")){
			error("No access profile was chosen");
		} elseif((GetFormData($f, $s, "radioselect") == "bystaff") && strlen(GetFormData($f, $s, "staffid")) == 0) {
			error("You must enter a Staff ID when 'By Staff ID' is selected");
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
			$usr->update(); // create or update the user

			// we need a user id for this
			if (GetFormData($f, $s, "radioselect") == "bydata") {
				if (strlen($usr->staffpkey) > 0) {
					// if it was bystaff and now bydata, remove old c01 rule
					$query = "delete r,ur from rule r inner join userrule ur on (ur.ruleid=r.id) where ur.userid=$usr->id and r.fieldnum='c01'";
					Query($query);
				}
				$usr->staffpkey = "";
			} else { // bystaff
				// remove any existing c01 rule
				$query = "delete r,ur from rule r inner join userrule ur on (ur.ruleid=r.id) where ur.userid=$usr->id and r.fieldnum='c01'";
				Query($query);

				// create the c01 rule based on current staffid
				$rule = new Rule();
				$rule->logical = "and";
				$rule->op = "in";
				$rule->val = $staffid;
				$rule->fieldnum = "c01";
				$rule->create();

				$query = "insert into userrule (userid, ruleid) values ($usr->id, $rule->id)";
				Query($query);

				// set current staffid
				$usr->staffpkey = $staffid;
			}

			// update again for staffid
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
			if (!ereg("^0*$", $pincode)) {
				$usr->setPincode($pincode);
			}

			if (strlen($callerid) == 0 )
				$callerid = false;
			$usr->setSetting("callerid",$callerid);

			$rule = getRuleFromForm($f,$s);			
			if ($rule != null && $usr->id) {
				$rule->create();
				//FIXME use UserRule.obj
				$query = "insert into userrule (userid, ruleid) values ($usr->id, $rule->id)";
				Query($query);
				$reloadform = 1;
			} else if(CheckFormSubmit($f,'applybutton')) {
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

$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => false, 'numeric' => true);
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

	$pass = $usr->accesscode ? '00000000' : '';
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
	putRuleFormData($f, $s);

	PutFormData($f,$s,"callerid", Phone::format($usr->getSetting("callerid","",true)), "text", 0, 20);
	PutFormData($f,$s,"staffid",$usr->staffpkey,"text");

	if ($usr->staffpkey == null || strlen($usr->staffpkey) == 0) {
		$radio = "bydata";
	} else {
		$radio = "bystaff";
	}
	PutFormData($f, $s, "radioselect", $radio);

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$usr = new User($_SESSION['userid']);
$readonly = $usr->importid != null;
$dis = "";
if ($readonly) $dis = "disabled";

$PAGE = "admin:users";
$TITLE = 'User Editor: ' . ($_SESSION['userid'] == NULL ? "New User" : escapehtml(GetFormData($f,$s,"firstname")) . ' ' . escapehtml(GetFormData($f,$s,"lastname")));
include_once("nav.inc.php");
NewForm($f);
if ($_SESSION['userid'] == NULL || (isset($usr) && $usr->email === "") ) {
	buttons(submit($f, 'submitbutton', 'Save'));
} else {
	buttons(submit($f, 'submitbutton', 'Save'), button('Email Password Reset', "if(confirm('Are you sure you want to Email this user to reset their password?')) window.location='?resetpass=1&id=$usr->id'"));
}
startWindow('User Information');
?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th valign="top" width="70" class="windowRowHeader<? if($USER->authorize('manageaccount')) print ' bottomBorder'; ?>" align="right" valign="top" style="padding-top: 6px;">Access Credentials:<br><? print help('User_AccessCredentials'); ?></th>
					<td class="<? if($USER->authorize('manageaccount')) print 'bottomBorder'; ?>">
						<table border="0" cellpadding="1" cellspacing="0">
							<tr>
								<td align="right">First Name:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $usr->firstname;
								} else {
									NewFormItem($f,$s, 'firstname', 'text', 20, 50);
								} ?>
								</td>
							</tr>
							<tr>
								<td align="right">Last Name:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $usr->lastname;
								} else {
									NewFormItem($f,$s, 'lastname', 'text', 20, 50);
								} ?>
								</td>
							</tr>
							<tr>
								<td align="right">Description:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $usr->description;
								} else {
									NewFormItem($f,$s, 'description', 'text', 20, 50);
								} ?>
								</td>
							</tr>							<tr>
								<td align="right">Username:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $usr->login;
								} else {
									NewFormItem($f,$s, 'login', 'text', 20);
								} ?>
								</td>
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
								<td colspan="4">
								<? if ($readonly) {
									echo $usr->accesscode;
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
									echo $usr->email;
								} else {
									NewFormItem($f,$s, 'email', 'text', 72, 10000);
								} ?>
								</td>
							</tr>
							<tr>
								<td align="right">Auto Report Email(s):</td>
								<td colspan="4">
								<? if ($readonly) {
									echo $usr->aremail;
								} else {
									NewFormItem($f,$s, 'aremail', 'text', 72, 10000);
								} ?>
								</td>
							</tr>
							<tr>
								<td align="right">Phone:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo Phone::format($usr->phone);
								} else {
									NewFormItem($f,$s, 'phone', 'text', 20);
								} ?>
								</td>
							</tr>

							<tr>
								<td align="right">Caller&nbsp;ID:</td>
								<td colspan="4">
								<? if ($readonly) {
									echo Phone::format($usr->getSetting("callerid","",true));
								} else {
									NewFormItem($f,$s, 'callerid', 'text', 20,20);
								} ?>
								</td>
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
								<? if ($readonly) {
									$profilename = QuickQuery("select name from access where id=".$usr->accessid);
									echo $profilename;
								} else {
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
								}
								?>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<table>
										<tr>
											<td>
												<? NewFormItem($f,$s,'restricttypes','checkbox',NULL,NULL,$dis.' id="restricttypes" onclick="clearAllIfNotChecked(this,\'jobtypeselect\');"'); ?>
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
									if ($readonly) {
										$userjobtypes = QuickQueryList("select name from jobtype where id in (select jobtypeid from userjobtypes where userid=".$usr->id.") and deleted=0 and not issurvey order by systempriority, name asc");
										echo implode(", ",$userjobtypes);
									} else {
										// changed query from name, id to id, name; jjl
										$options = QuickQueryList("select id, name from jobtype where deleted=0 and not issurvey order by systempriority, name asc", true);
										if(!count($options))
											$options['No Job Types Defined'] = 0;
										NewFormItem($f,$s,'jobtypes','selectmultiple',3,$options,$dis.' id="jobtypeselect" onmousedown="setChecked(\'restricttypes\')"');
									}
								?>
								</td>
							</tr>

<? if (getSystemSetting('_hassurvey', true)) { ?>

							<tr>
								<td colspan="2">
									<table>
										<tr>
											<td>
												<? NewFormItem($f,$s,'restrictsurveytypes','checkbox',NULL,NULL,$dis.' id="restrictsurveytypes" onclick="clearAllIfNotChecked(this,\'surveyjobtypeselect\'); "'); ?>
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
								if ($readonly) {
									$userjobtypes = QuickQueryList("select name from jobtype where id in (select jobtypeid from userjobtypes where userid=".$usr->id.") and deleted=0 and issurvey=1 order by systempriority, name asc");
									echo implode(", ",$userjobtypes);
								} else {
									// changed query from name, id to id, name; jjl
									$surveyoptions = QuickQueryList("select id, name from jobtype where deleted=0 and issurvey=1 order by systempriority, name asc", true);
									if(!count($surveyoptions))
										$surveyoptions['No Job Types Defined'] = 0;
									NewFormItem($f,$s,'surveyjobtypes','selectmultiple',3,$surveyoptions,$dis.' id="surveyjobtypeselect" onmousedown="setChecked(\'restrictsurveytypes\')"');
								}
								?>
								</td>
							</tr>
<? } ?>
						</table>
					</td>
				</tr>
				<tr>
					<th valign="top" align="right" class="windowRowHeader">Data View:<br><? print help('User_DataView'); ?></th>
					<td>
						<table>
							<tr>
								<td colspan="2">
								Restrict this user's data access:
								</td>
							</tr>
							<tr>
								<td><? $extrahtml = "";
									if ($readonly) $extrahtml = "disabled=\"disabled\"";
									NewFormItem($f, $s, "radioselect", "radio", null, "bydata", "onclick='toggleDataViewRestriction(\"bydata\");' ".$extrahtml); ?> By Data</td>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "bystaff","onclick='toggleDataViewRestriction(\"bystaff\");' ".$extrahtml); ?> By Staff ID</td>
								<td><? if (!$readonly) print submit($f, 'applybutton', 'Apply'); ?> </td>
							</tr>
							<tr></tr>
						</table>

						<div id="bystaff" style="width:100%;">
						<table width="100%">
							<tr>
								<td>Staff&nbsp;ID:&nbsp;&nbsp;
								<? if ($readonly) {
									echo $usr->staffpkey;
								} else {
									NewFormItem($f,$s, 'staffid', 'text', 20, 255);
								} ?>
								</td>
							</tr>
						</table>
						</div>

						<div id="ruleform" style="width:100%;">
						<table width="100%">
							<tr>
								<td>
								<?
								if ($readonly) {
									echo "<BR>";
								} else {
									?>
									<a href="?clearrules" onclick="return confirm('Are you sure you want to clear all data view restrictions?');">Clear All</a>
									<?
								}
								if ($usr->staffpkey == null || strlen($usr->staffpkey) == 0) {
									$cfield = true;
								} else {
									$cfield = $readonly; // if readonly, display cfield restrictions otherwise do not
								}

								drawRuleTable($f, $s, $readonly, true, true, $cfield);
								?>
								</td>
							</tr>
						</table>
						</div>

						<div id="mustapply" style="width:100%;">
						<table width="100%">
							<tr>
								<td>
								Click 'Apply' to edit data view restrictions.
								</td>
							</tr>
						</table>
						</div>

					</td>
				</tr>
				<? } ?>
			</table>
<script language="javascript">

<?
if (GetFormData($f, $s, "radioselect") == "bydata") {
	?>$("bystaff").hide();<?
	if ($usr->staffpkey == null || strlen($usr->staffpkey) == 0) {
		?>$("ruleform").show(); $("mustapply").hide();<?
	} else {
		?>$("ruleform").hide(); $("mustapply").show();<?
	}
} else {
	?>$("bystaff").show();<?
	if ($usr->staffpkey == null || strlen($usr->staffpkey) == 0) {
		?>$("ruleform").hide(); $("mustapply").show();<?
	} else {
		?>$("ruleform").show(); $("mustapply").hide();<?
	}
}
?>


function toggleDataViewRestriction(bytype) {
	if (bytype == "bydata") {
		$("bystaff").hide();
<?
if ($usr->staffpkey == null || strlen($usr->staffpkey) == 0) {
	?>$("ruleform").show(); $("mustapply").hide();<?
} else {
	?>$("ruleform").hide(); $("mustapply").show();<?
}
?>
	} else {
		$("bystaff").show();
<?
if ($usr->staffpkey == null || strlen($usr->staffpkey) == 0) {
	?>$("ruleform").hide(); $("mustapply").show();<?
} else {
	?>$("ruleform").show(); $("mustapply").hide();<?
}
?>
	}
}

</script>
<?
endWindow();
buttons();
EndForm();
?>
<script SRC="script/calendar.js"></script>
<?
include_once("navbottom.inc.php");
?>
