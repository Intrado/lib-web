<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/FormUserItems.obj.php");
require_once("obj/FormRuleWidget.fi.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('manageaccount')) {
	redirect('unauthorized.php');
}

if (isset($_GET['id']))
	$id = $_GET['id'] + 0;
else
	$id = "new";

/*CSDELETEMARKER_START*/
if (!$IS_COMMSUITE && $id !== "new")
	if (QuickQuery("select count(*) from user where login = 'schoolmessenger' and id =?", false, array($id)))
		redirect('unauthorized.php');

if ($id === "new") {
	$usercount = QuickQuery("select count(*) from user where enabled = 1 and login != 'schoolmessenger'");
	$maxusers = getSystemSetting("_maxusers", "unlimited");
	if (($maxusers !== "unlimited") && $maxusers <= $usercount)
		redirect('unauthorized.php');
}
/*CSDELETEMARKER_END*/

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$edituser = new User($id);

$readonly = $edituser->importid != null;
$ldapuser = $edituser->ldap;
$profilename = QuickQuery("select name from access where id=?", false, array($edituser->accessid));

$hasenrollment = QuickQuery("select count(id) from import where datatype = 'enrollment'")?true:false; 

$hasstaffid = QuickQuery("select count(r.id) from userrule ur, rule r where ur.userid=? and ur.ruleid = r.id and r.fieldnum = 'c01'", false, array($edituser->id))?true:false; 

if($IS_COMMSUITE) {
	$accessprofiles = QuickQueryList("select id, name from access", true);
}
/*CSDELETEMARKER_START*/
else
	$accessprofiles = QuickQueryList("select id, name from access where name != 'SchoolMessenger Admin'", true);
/*CSDELETEMARKER_END*/

$userjobtypeids = QuickQueryList("select id from jobtype where id in (select jobtypeid from userjobtypes where userid=?) and not deleted and not issurvey order by systempriority, name asc", false, false, array($edituser->id));
$jobtypes = QuickQueryList("select id, name from jobtype where not deleted and not issurvey order by systempriority, name asc", true);

$usersurveytypes = QuickQueryList("select id from jobtype where id in (select jobtypeid from userjobtypes where userid=?) and not deleted and issurvey order by systempriority, name asc", false, false, array($edituser->id));
$surveytypes = QuickQueryList("select id, name from jobtype where not deleted and issurvey order by systempriority, name asc", true);

////////////////////////////////////////////////////////////////////////////////
// Custom form items
////////////////////////////////////////////////////////////////////////////////
class InpageSubmitButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value=""/>';
		return $str.submit_button($this->args['name'], 'inpagesubmit', $this->args['icon']);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$formdata = array();
$helpsteps = array();

$formdata[] = _L("Account Information");

$formdata["firstname"] = array(
	"label" => _L("First Name"),
	"fieldhelp" => _L("The user's first name."),
	"value" => $edituser->firstname,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 1,"max" => 50)
	),
	"control" => array("TextField","maxlength" => 50, "size" => 30),
	"helpstep" => 1
);
$formdata["lastname"] = array(
	"label" => _L("Last Name"),
	"fieldhelp" => _L("The user's last name."),
	"value" => $edituser->lastname,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 1,"max" => 50)
	),
	"control" => array("TextField","maxlength" => 50, "size" => 30),
	"helpstep" => 1
);

$formdata["description"] = array(
	"label" => _L("Description"),
	"fieldhelp" => _L('An optional description of the user.'),
	"value" => $edituser->description,
	"validators" => array(),
	"control" => array("TextField","maxlength" => 50, "size" => 40),
	"helpstep" => 1
);

$formdata["login"] = array(
	"label" => _L("Username"),
	"fieldhelp" => _L('The username that the user will use to log into the system.'),
	"value" => $edituser->login,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => getSystemSetting("usernamelength", 5),"max" => 20),
		array("ValLogin", "userid" => $edituser->id)
	),
	"control" => array("TextField","maxlength" => 20, "size" => 20),
	"helpstep" => 1
);

if($IS_LDAP){
	$formdata["ldap"] = array(
		"label" => _L("Use LDAP Auth"),
		"value" => $ldapuser,
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 1
	);
}

$pass = ($edituser->id && $edituser->id !== "new") ? '00000000' : '';
$passlength = getSystemSetting("passwordlength", 5);
$formdata["password"] = array(
	"label" => _L("Password"),
	"fieldhelp" => _L('The password is used to log into the web interface.'),
	"value" => $pass,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => $passlength,"max" => 20),
		array("ValPassword", "login" => $edituser->login, "firstname" => $edituser->firstname, "lastname" => $edituser->lastname)
	),
	"requires" => array("firstname", "lastname", "login"),
	"control" => array("TextPasswordStrength","maxlength" => 20, "size" => 25, "minlength" => $passlength),
	"helpstep" => 1
);

$formdata["passwordconfirm"] = array(
	"label" => _L("Confirm Password"),
	"fieldhelp" => _L('This field is used used to confirm a new password.'),
	"value" => $pass,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => $passlength,"max" => 20),
		array("ValFieldConfirmation", "field" => "password")
	),
	"requires" => array("password"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 25),
	"helpstep" => 1
);

$formdata["accesscode"] = array(
	"label" => _L("Phone User ID"),
	"fieldhelp" => _L('The number in this field is like a username for logging into the system by phone.'),
	"value" => $edituser->accesscode,
	"validators" => array(
		array("ValNumeric", "min" => 4),
		array("ValAccesscode", "userid" => $edituser->id)
	),
	"control" => array("TextField","maxlength" => 20, "size" => 8),
	"helpstep" => 1
);

$pin = $edituser->accesscode ? '00000' : '';
$formdata["pin"] = array(
	"label" => _L("Phone PIN Code"),
	"fieldhelp" => _L('The number in this field is like a password for logging into the system by phone.'),
	"value" => $pin,
	"validators" => array(
		array("ValNumeric", "min" => 4),
		array("ValPin", "accesscode" => $edituser->accesscode)
	),
	"requires" => array("accesscode"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 8),
	"helpstep" => 1
);

$formdata["pinconfirm"] = array(
	"label" => _L("Confirm PIN"),
	"fieldhelp" => _L('This field is used to confirm a new PIN number.'),
	"value" => $pin,
	"validators" => array(
		array("ValNumeric"),
		array("ValFieldConfirmation", "field" => "pin")
	),
	"requires" => array("pin"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 8),
	"helpstep" => 1
);

$formdata["email"] = array(
	"label" => _L("Account Email"),
	"fieldhelp" => ("This is used for forgot passwords, reporting, and as the return address in email messages."),
	"value" => $edituser->email,
	"validators" => array(
		array("ValLength","min" => 3,"max" => 255),
		array("ValEmail")
	),
	"control" => array("TextField","maxlength" => 255, "size" => 35),
	"helpstep" => 1
);

$formdata["aremail"] = array(
	"label" => _L("Auto Report Emails"),
	"fieldhelp" => _L("Email addresses entered here will receive copies of any autoreports associated with this user. The user's email address will automatically receive reports and should not be entered here."),
	"value" => $edituser->aremail,
	"validators" => array(
		array("ValLength","min" => 3,"max" => 1024),
		array("ValEmailList")
	),
	"control" => array("TextField","maxlength" => 1024, "size" => 50),
	"helpstep" => 1
);

$formdata["phone"] = array(
	"label" => _L("Phone"),
	"fieldhelp" => _L('This is the direct access phone number for the user.'),
	"value" => Phone::format($edituser->phone),
	"validators" => array(
		array("ValLength","min" => 2,"max" => 20),
		array("ValPhone")
	),
	"control" => array("TextField","maxlength" => 20, "size" => 15),
	"helpstep" => 1
);

if (!getSystemSetting('_hascallback', false)) {
	$formdata["callerid"] = array(
		"label" => _L("Caller ID"),
		"fieldhelp" => _L('This is the default Caller ID phone number for jobs sent by the user.'),
		"value" => ($edituser->id +0 > 0)?Phone::format($edituser->getSetting("callerid", "")):"",
		"validators" => array(
			array("ValLength","min" => 2,"max" => 20),
			array("ValPhone")
		),
		"control" => array("TextField","maxlength" => 20, "size" => 15),
		"helpstep" => 1
	);
}

$formdata[] = _L("Account Restrictions");

$formdata["accessid"] = array(
	"label" => _L("Access Profile"),
	"fieldhelp" => _L('Access Profiles define which sets of features a group of users may access.'),
	"value" => ($edituser->accessid)?$edituser->accessid:"",
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($accessprofiles))
	),
	"control" => array("SelectMenu", "values"=>$accessprofiles),
	"helpstep" => 2
);
if (!count($accessprofiles)) {
	$formdata["accessid"]["control"] = array("FormHtml","html" => "<div id='accessprofilediv'></div><div style='color: red'>"._L("You have no Access Profiles defined! Go to the Admin->Profiles tab and create one.")."</div>");
	unset($formdata["accessid"]["validators"]);
}

$formdata["jobtypes"] = array(
	"label" => _L("Job Type Restriction"),
	"fieldhelp" => _L('If the user should only be able to send certain types of jobs, check the job types here. Checking nothing will allow the user to send any job type.'),
	"value" => $userjobtypeids,
	"validators" => array(),
	"control" => array("MultiCheckBox", "values"=>$jobtypes),
	"helpstep" => 2
);
$formdata["surveytypes"] = array(
	"label" => _L("Survey Type Restriction"),
	"fieldhelp" => _L('If the user should only be able to send certain types of surveys, check the survey types here. Checking nothing will allow the user to send any survey type.'),
	"value" => $usersurveytypes,
	"validators" => array(),
	"control" => array("MultiCheckBox", "values"=>$surveytypes),
	"helpstep" => 2
);

$formdata[] = _L("Data View");

if ($hasenrollment) {
	$formdata["staffpkey"] = array(
		"label" => _L("Staff ID"),
		"fieldhelp" => _L("If the user is directly related to a staff ID and data access should be controlled based on it's value."),
		"value" => $edituser->staffpkey,
		"validators" => array(),
		"control" => array("TextField","maxlength" => 20, "size" => 12),
		"helpstep" => 1
	);
	$formdata["submit"] = array(
		"label" => "",
		"value" => "",
		"validators" => array(),
		"control" => array("InpageSubmitButton", "name" => (($hasstaffid)?_L('Remove Staff ID'):_L('Set Staff ID')), "icon" => (($hasstaffid)?"cross":"disk")),
		"helpstep" => 1
	);
}

$rules = cleanObjects($edituser->rules());
$fields = QuickQueryMultiRow("select fieldnum from fieldmap where options not like '%multisearch%'");
$ignoredFields = array();
foreach ($fields as $fieldnum)
	$ignoredFields[] = $fieldnum[0];
if (!in_array('c01', $ignoredFields)) $ignoredFields[] = 'c01';
$formdata["datarules"] = array(
	"label" => _L("Data Restriction"),
	"fieldhelp" => _L('If the user should only be able to access certain data, you may create restriction rules here.'),
	"value" => json_encode(array_values($rules)),
	"validators" => array(
		array("ValRules")
	),
	"control" => array("FormRuleWidget", "ignoredFields" => $ignoredFields),
	"helpstep" => 1
);

if ($hasstaffid) {
	$formdata["staffpkey"]["control"] = array("FormHtml", "html" => '<div style="border: 1px solid gray; width: 20%">'.$edituser->staffpkey.'</div>');
	$formdata["datarules"]["control"]["allowedFields"] = array('f', 'g');
}

// Read only users have some control items disabled and some validators removed
if ($readonly) {
	// First Name
	$formdata["firstname"]["control"] = array("FormHtml","html" => $edituser->firstname);
	unset($formdata["firstname"]["validators"]);
	// Last Name
	$formdata["lastname"]["control"] = array("FormHtml","html" => $edituser->lastname);
	unset($formdata["lastname"]["validators"]);
	// User Login
	$formdata["login"]["control"] = array("FormHtml","html" => $edituser->login);
	unset($formdata["login"]["validators"]);
	// Use LDAP login auth
	if($IS_LDAP) $formdata["ldap"]["control"] = array("FormHtml","html" => $ldapuser?"True":"False");
	// Password
	unset($formdata["password"]["requires"]);
	// Access code
	$formdata["accesscode"]["control"] = array("FormHtml","html" => $edituser->accesscode);
	unset($formdata["accesscode"]["validators"]);
	// Phone login PIN
	unset($formdata["pin"]["requires"]);
	// Account Email
	$formdata["email"]["control"] = array("FormHtml","html" => $edituser->email);
	unset($formdata["email"]["validators"]);
	// Autoreport Email
	$formdata["aremail"]["control"] = array("FormHtml","html" => $edituser->aremail);
	unset($formdata["aremail"]["validators"]);
	// Phone
	$formdata["phone"]["control"] = array("FormHtml","html" => Phone::format($edituser->phone));
	unset($formdata["phone"]["validators"]);
	// Caller ID
	if (!getSystemSetting('_hascallback', false)) {
		$formdata["callerid"]["control"] = array("FormHtml","html" => ($edituser->id +0 > 0)?Phone::format($edituser->getSetting("callerid", "")):"");
		unset($formdata["callerid"]["validators"]);
	}
	// Access Profile
	$formdata["accessid"]["control"] = array("FormHtml","html" => $profilename);
	unset($formdata["accessid"]["validators"]);
	// Job Types
	$displayjobtypes = "";
	foreach ($jobtypes as $jobtypeid => $jobtypename) {
		if (count($userjobtypeids)) {
			if (in_array($jobtypeid, $userjobtypeids))
				$displayjobtypes .= $jobtypename. "<br>";
		} else
			$displayjobtypes .= $jobtypename. "<br>";
	}
	$formdata["jobtypes"]["control"] = array("FormHtml", "html" => "<div style='border: 1px dotted;'>$displayjobtypes</div>");
	unset($formdata["jobtypes"]["validators"]);
	// Survey Types
	$displaysurveytypes = "";
	foreach ($surveytypes as $jobtypeid => $jobtypename) {
		if (count($usersurveytypes)) {
			if (in_array($jobtypeid, $usersurveytypes))
				$displaysurveytypes .= $jobtypename. "<br>";
		} else
			$displaysurveytypes .= $jobtypename. "<br>";
	}
	$formdata["surveytypes"]["control"] = array("FormHtml", "html" => "<div style='border: 1px dotted;'>$displaysurveytypes</div>");
	unset($formdata["surveytypes"]["validators"]);
	// Staff Pkey value
	if ($hasenrollment) {
		unset($formdata["staffpkey"]);
		unset($formdata["submit"]);
	}
	// Data restrictions
	if (count($rules)) {
		$formdata["datarules"]["control"] = array("FormRuleWidget", "readonly" => true);
	} else {
		unset($formdata["datarules"]);
		unset($formdata["and"]);
	}
}

$buttons = array(submit_button(_L("Done"),"submit","tick"), icon_button(_L("Cancel"),"cross",null,"users.php"));

$form = new Form("account", $formdata, null, $buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		if (!count($accessprofiles)) {
			$form->modifyElement('accessprofilediv', '<script>alert("'._L("You have no Access Profiles defined! Go to the Admin->Profiles tab and create one.").'")</script>');
		}
		
		if ($edituser->id == NULL) {
			$edituser->enabled = 1;
		}
		
		Query("BEGIN");

		if (!$readonly) {
			$edituser->firstname = $postdata['firstname'];
			$edituser->lastname = $postdata['lastname'];
			$edituser->description = $postdata['description'];
			$edituser->login = $postdata['login'];
			$edituser->ldap = isset($postdata['ldap'])?$postdata['ldap']:false;
			$edituser->accesscode = $postdata['accesscode'];
			
			$edituser->email = $postdata['email'];
			$edituser->aremail = $postdata['aremail'];
			
			$userphone = Phone::parse($postdata['phone']);
			$edituser->phone = $userphone;
			
			if (strlen($userphone) == 0 )
				$userphone = false;
			
			if($IS_LDAP){
				if(isset($postdata['ldap']) && $postdata['ldap'])
					$edituser->ldap=1;
				else
					$edituser->ldap=0;
			}
			
			$edituser->accessid = $postdata['accessid'];
			
			if ($edituser->id == "new") // create or update the user
				$edituser->create();
			else
				$edituser->update(); 
			
			if (isset($postdata['callerid']))
				$edituser->setSetting("callerid",$postdata['callerid']);

			if (!$edituser->getSetting("maxjobdays", false))
				$edituser->setSetting("maxjobdays", 1);
			
			// Remove all existing user rules
			$rules = $edituser->rules();
			if (count($rules)) {
				foreach ($rules as $rule) {
					// don't remove c field rules if they are using a staff id
					if ($hasstaffid) {
						if (substr($rule->fieldnum, 0, 1) !== "c") {
							Query("delete from rule where id=?", false, array($rule->id));
							Query("delete from userrule where userid=? and ruleid=?", false, array($edituser->id, $rule->id));
						}
					 } else {
						Query("delete from rule where id=?", false, array($rule->id));
						Query("delete from userrule where userid=? and ruleid=?", false, array($edituser->id, $rule->id));
					}
				}
			}

			$existingstaffidrule = QuickQuery("select r.id from userrule ur, rule r where ur.userid=? and ur.ruleid = r.id and r.fieldnum = 'c01'", false, array($edituser->id));
			if (isset($postdata['datarules']))
				$datarules = json_decode($postdata['datarules']);

			if ($button == 'inpagesubmit' && $hasstaffid) {
				// remove existing c01 rule if exists
				if ($existingstaffidrule) {
					Query("delete from rule where id=?", false, array($existingstaffidrule));
					Query("delete from userrule where userid=? and ruleid=?", false, array($edituser->id, $existingstaffidrule));
				}
				// remove current staff id from user
				$edituser->staffpkey = "";
			}
			
			if (!$hasstaffid && isset($postdata['staffpkey']) && strlen($postdata['staffpkey'])) {
				// remove existing c01 rule if exists
				if ($existingstaffidrule) {
					Query("delete from rule where id=?", false, array($existingstaffidrule));
					Query("delete from userrule where userid=? and ruleid=?", false, array($edituser->id, $existingstaffidrule));
				}
				// create the c01 rule based on current staffid
				$rule = new Rule();
				$rule->fieldnum = "c01";
				$rule->type = "multisearch";
				$rule->logical = "and";
				$rule->op = "in";
				$rule->val =$postdata['staffpkey'];
				$rule->create();
				
				Query("insert into userrule values (?, ?)", false, array($edituser->id, $rule->id));
				
				// set current staffid
				$edituser->staffpkey = $postdata['staffpkey'];
				
				// remove any c fields from data rules
				if (count($datarules))
					foreach ($datarules as $index => $datarule)
						if (substr($datarule->fieldnum, 0, 1) == "c")
							unset($datarules[$index]);
			}
			
			if (count($datarules)) {
				foreach ($datarules as $datarule) {
					$rule = new Rule();
					$rule->fieldnum = $datarule->fieldnum;
					$rule->type = $datarule->type;
					$rule->logical = $datarule->logical;
					$rule->op = $datarule->op;
					$rule->val = is_array($datarule->val)?implode("|", $datarule->val):$datarule->val;
					$rule->create();
					
					Query("insert into userrule values (?, ?)", false, array($edituser->id, $rule->id));
				}
			}
			
			// update again for staffid
			$edituser->update();
			
			QuickUpdate("delete from userjobtypes where userid =?", false, array($edituser->id));
			
			if(count($postdata['jobtypes']))
				foreach($postdata['jobtypes'] as $type)
					QuickUpdate("insert into userjobtypes values (?, ?)", false, array($edituser->id, $type));
			if(count($postdata['surveytypes']))
				foreach($postdata['surveytypes'] as $type)
					QuickUpdate("insert into userjobtypes values (?, ?)", false, array($edituser->id, $type));
		}

		if((!$edituser->ldap && $IS_LDAP) || !$IS_LDAP){
			// If the password is all 0 characters then it was a default form value, so ignore it
			if (!ereg("^0*$", $postdata['password']))
				$edituser->setPassword($postdata['password']);
		}

		// If the pincode is all 0 characters then it was a default form value, so ignore it
		if (!ereg("^0*$", $postdata['pin']))
			$edituser->setPincode($postdata['pin']);
		
		Query("COMMIT");
		if ($button == 'inpagesubmit') {
			if ($ajax)
				$form->sendTo("user.php?id=".$edituser->id);
			else
				redirect("user.php?id=".$edituser->id);
		} else {
			if ($ajax)
				$form->sendTo("users.php");
			else
				redirect("users.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:users";
$TITLE = _L('User Editor: ') . ($id == "new" ? _L("New User") : escapehtml($edituser->firstname) . ' ' . escapehtml($edituser->lastname));

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValLogin", "ValPassword", "ValAccesscode", "ValPin", "ValRules")); ?>
</script>
<?

startWindow(_L("User Information"));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>
