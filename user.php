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
require_once("obj/FormRuleWidget.obj.php");

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
// Validators
////////////////////////////////////////////////////////////////////////////////
class ValDataRules extends Validator {
	function validate ($value, $args, $requiredvalues) {
		if (isset($requiredvalues['staffpkey']) && strlen(trim($requiredvalues['staffpkey'])) !== 0)
			return "$this->label " . _L("Cannot have both Staff ID and Data Restriction rules. Delete the contents of one or the other.");
		else
			return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args, requiredvalues) {
				var staffpkey = "";
				var datarules = value.evalJSON();
				if (typeof(requiredvalues.staffpkey) !== "undefined")
					staffpkey = requiredvalues.staffpkey;
				if (staffpkey.length != 0 && datarules.length != 0)
					return label + " '. addslashes(_L("Cannot have both Staff ID and Data Restriction rules. Delete the contents of one or the other.")). '";
				return true;
			}';
	}
}

class ValStaffPKey extends Validator {
	function validate ($value, $args, $requiredvalues) {
		$datarules = json_decode($requiredvalues['datarules']);
		if ($datarules)
			return "$this->label " . _L("Cannot have both Staff ID and Data Restriction rules. Delete the contents of one or the other.");
		return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args, requiredvalues) {
				datarules = requiredvalues.datarules.evalJSON();
				if (datarules.length != 0)
					return label + " '. addslashes(_L("Cannot have both Staff ID and Data Restriction rules. Delete the contents of one or the other.")). '";
				return true;
			}';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$formdata = array();
$helpsteps = array();

$formdata[] = _L("Account Information");

if ($readonly) {
	$formdata["firstname"] = array(
		"label" => _L("First Name"),
		"control" => array("FormHtml","html" => $edituser->firstname),
		"helpstep" => 1
	);
	$formdata["lastname"] = array(
		"label" => _L("Last Name"),
		"control" => array("FormHtml","html" => $edituser->lastname),
		"helpstep" => 1
	);
} else {
	$formdata["firstname"] = array(
		"label" => _L("First Name"),
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
		"value" => $edituser->lastname,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("TextField","maxlength" => 50, "size" => 30),
		"helpstep" => 1
	);
}

$formdata["description"] = array(
	"label" => _L("Description"),
	"value" => $edituser->description,
	"validators" => array(),
	"control" => array("TextField","maxlength" => 50, "size" => 40),
	"helpstep" => 1
);

if ($readonly) {
	$formdata["login"] = array(
		"label" => _L("Username"),
		"control" => array("FormHtml","html" => $edituser->login),
		"helpstep" => 1
	);
} else {
	$formdata["login"] = array(
		"label" => _L("Username"),
		"value" => $edituser->login,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => getSystemSetting("usernamelength", 5),"max" => 20),
			array("ValLogin", "userid" => $edituser->id)
		),
		"control" => array("TextField","maxlength" => 20, "size" => 20),
		"helpstep" => 1
	);
}

if($IS_LDAP){
	if ($readonly) {
		$formdata["ldap"] = array(
			"label" => _L("Use LDAP Auth"),
			"control" => array("FormHtml","html" => $ldapuser?"True":"False"),
			"helpstep" => 1
		);
	} else {
		$formdata["ldap"] = array(
			"label" => _L("Use LDAP Auth"),
			"value" => $ldapuser,
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 1
		);
	}
}

$pass = ($edituser->id && $edituser->id !== "new") ? '00000000' : '';
$passlength = getSystemSetting("passwordlength", 5);
if ($readonly) {
	$formdata["password"] = array(
		"label" => _L("Password"),
		"value" => $pass,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => $passlength,"max" => 20),
			array("ValPassword", "login" => $edituser->login, "firstname" => $edituser->firstname, "lastname" => $edituser->lastname)
		),
		"control" => array("PasswordField","maxlength" => 20, "size" => 25),
		"helpstep" => 1
	);
} else {
	$formdata["password"] = array(
		"label" => _L("Password"),
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
}

$formdata["passwordconfirm"] = array(
	"label" => _L("Confirm Password"),
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

if ($readonly) {
	$formdata["accesscode"] = array(
		"label" => _L("Phone User ID"),
		"control" => array("FormHtml","html" => $edituser->accesscode),
		"helpstep" => 1
	);
} else {
	$formdata["accesscode"] = array(
		"label" => _L("Phone User ID"),
		"value" => $edituser->accesscode,
		"validators" => array(
			array("ValNumeric", "min" => 4),
			array("ValAccesscode", "userid" => $edituser->id)
		),
		"control" => array("TextField","maxlength" => 20, "size" => 8),
		"helpstep" => 1
	);
}

$pin = $edituser->accesscode ? '00000' : '';
if ($readonly) {
	$formdata["pin"] = array(
		"label" => _L("Phone PIN Code"),
		"value" => $pin,
		"validators" => array(
			array("ValNumeric", "min" => 4),
			array("ValPin", "accesscode" => $edituser->accesscode)
		),
		"control" => array("PasswordField","maxlength" => 20, "size" => 8),
		"helpstep" => 1
	);
} else {
	$formdata["pin"] = array(
		"label" => _L("Phone PIN Code"),
		"value" => $pin,
		"validators" => array(
			array("ValNumeric", "min" => 4),
			array("ValPin", "accesscode" => $edituser->accesscode)
		),
		"requires" => array("accesscode"),
		"control" => array("PasswordField","maxlength" => 20, "size" => 8),
		"helpstep" => 1
	);
}

$formdata["pinconfirm"] = array(
	"label" => _L("Confirm PIN"),
	"value" => $pin,
	"validators" => array(
		array("ValNumeric"),
		array("ValFieldConfirmation", "field" => "pin")
	),
	"requires" => array("pin"),
	"control" => array("PasswordField","maxlength" => 20, "size" => 8),
	"helpstep" => 1
);

if ($readonly) {
	$formdata["email"] = array(
		"label" => _L("Email"),
		"control" => array("FormHtml","html" => $edituser->email),
		"helpstep" => 1
	);

	$formdata["aremail"] = array(
		"label" => _L("Auto Report Emails"),
		"control" => array("FormHtml","html" => $edituser->aremail),
		"helpstep" => 1
	);

	$formdata["phone"] = array(
		"label" => _L("Phone"),
		"control" => array("FormHtml","html" => Phone::format($edituser->phone)),
		"helpstep" => 1
	);
} else {
	$formdata["email"] = array(
		"label" => _L("Email"),
		"value" => $edituser->email,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 3,"max" => 255),
			array("ValEmail")
		),
		"control" => array("TextField","maxlength" => 255, "size" => 35),
		"helpstep" => 1
	);

	$formdata["aremail"] = array(
		"label" => _L("Auto Report Emails"),
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
		"value" => Phone::format($edituser->phone),
		"validators" => array(
			array("ValLength","min" => 2,"max" => 20),
			array("ValPhone")
		),
		"control" => array("TextField","maxlength" => 20, "size" => 15),
		"helpstep" => 1
	);
}

$formdata[] = _L("Account Restrictions");

if ($readonly) {
	$formdata["accessid"] = array(
		"label" => _L("Access Profile"),
		"control" => array("FormHtml","html" => $profilename),
		"helpstep" => 1
	);
} else if (!count($accessprofiles)) {
	$formdata["accessid"] = array(
		"label" => _L("Access Profile"),
		"control" => array("FormHtml","html" => _L("You have no Aceess Profiles defined! Go to the Admin->Profiles tab and create one.")),
		"helpstep" => 1
	);
} else {
	$formdata["accessid"] = array(
		"label" => _L("Access Profile"),
		"value" => $edituser->accessid,
		"validators" => array(
			array("ValRequired")
		),
		"control" => array("SelectMenu", "values"=>$accessprofiles),
		"helpstep" => 2
	);
}

if ($readonly) {
	$displayjobtypes = "";
	foreach ($jobtypes as $jobtypeid => $jobtypename) {
		if (count($userjobtypeids)) {
			if (in_array($jobtypeid, $userjobtypeids))
				$displayjobtypes .= $jobtypename. "<br>";
		} else
			$displayjobtypes .= $jobtypename. "<br>";
	}
	$displaysurveytypes = "";
	foreach ($surveytypes as $jobtypeid => $jobtypename) {
		if (count($usersurveytypes)) {
			if (in_array($jobtypeid, $usersurveytypes))
				$displaysurveytypes .= $jobtypename. "<br>";
		} else
			$displaysurveytypes .= $jobtypename. "<br>";
	}
	$formdata["jobtypes"] = array(
		"label" => _L("Job Type Restriction"),
		"control" => array("FormHtml","html" => "<div style='border: 1px dotted;'>$displayjobtypes</div>"),
		"helpstep" => 1
	);
	$formdata["surveytypes"] = array(
		"label" => _L("Survey Type Restriction"),
		"control" => array("FormHtml","html" => "<div style='border: 1px dotted;'>$displaysurveytypes</div>"),
		"helpstep" => 1
	);
} else {
	$formdata["jobtypes"] = array(
		"label" => _L("Job Type Restriction"),
		"value" => $userjobtypeids,
		"validators" => array(),
		"control" => array("MultiCheckBox", "values"=>$jobtypes),
		"helpstep" => 2
	);
	$formdata["surveytypes"] = array(
		"label" => _L("Survey Type Restriction"),
		"value" => $usersurveytypes,
		"validators" => array(),
		"control" => array("MultiCheckBox", "values"=>$surveytypes),
		"helpstep" => 2
	);
}

$formdata[] = _L("Data View");
$rules = cleanObjects($edituser->rules());
if ($readonly) {
	if ($edituser->staffpkey == null || strlen($edituser->staffpkey) == 0) {
		if (count($rules)) {
			$formdata["datarules"] = array(
				"label" => _L("Data Restriction"),
				"value" => json_encode(array_values($rules)),
				"validators" => array(),
				"requires" => array("staffpkey"),
				"control" => array("FormRuleWidget", "readonly" => true),
				"helpstep" => 1
			);
		}
	} else {
		$formdata["staffpkey"] = array(
			"label" => _L("Staff ID"),
			"control" => array("FormHtml","html" => "<div style='border: 1px dotted;'>".$edituser->staffpkey."</div>"),
			"helpstep" => 1
		);
	}
} else {
	if ($hasenrollment) {
		$formdata["staffpkey"] = array(
			"label" => _L("Staff ID"),
			"value" => $edituser->staffpkey,
			"validators" => array(
				array("ValStaffPKey")
			),
			"requires" => array("datarules"),
			"control" => array("TextField","maxlength" => 20, "size" => 12),
			"helpstep" => 1
		);
		$formdata["or"] = array(
			"label" => _L("Or"),
			"control" => array("FormHtml","html" => ""),
			"helpstep" => 1
		);
		$formdata["datarules"] = array(
			"label" => _L("Data Restriction"),
			"value" => json_encode(array_values($rules)),
			"validators" => array(
				array("ValDataRules"),
				array("ValRules")
			),
			"requires" => array("staffpkey"),
			"control" => array("FormRuleWidget"),
			"helpstep" => 1
		);
	} else {
		$formdata["datarules"] = array(
			"label" => _L("Data Restriction"),
			"value" => json_encode(array_values($rules)),
			"validators" => array(
				array("ValRules")
			),
			"control" => array("FormRuleWidget"),
			"helpstep" => 1
		);
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
		
		if ($edituser->id == NULL) {
			$edituser->enabled = 1;
		}
		
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
			$edituser->setSetting("callerid",$userphone);
			
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
			
			// Remove all existing user rules
			$rules = $edituser->rules();
			if (count($rules)) {
				foreach ($rules as $rule)
					$delrules[] = $rule->id;
				Query("BEGIN");
				Query("delete from rule where userid =? and id in (?)", false, array($edituser->id, implode(",", $delrules)));
				Query("delete from userrule where userid =?", false, array($edituser->id));
				Query("COMMIT");
			}
			
			Query("BEGIN");
			$edituser->staffpkey = "";
			if (isset($postdata['staffpkey']) && strlen($postdata['staffpkey'])) {
				// create the c01 rule based on current staffid
				$rule = Rule::initFrom("c01", "multisearch", "and", "in", array(array($postdata['staffpkey'])));
				$rule->create();
				
				Query("insert into userrule values (?, ?)", false, array($edituser->id, $rule->id));
				
				// set current staffid
				$edituser->staffpkey = $postdata['staffpkey'];
			}
			
			if (isset($postdata['datarules'])) {
				$datarules = json_decode($postdata['datarules']);
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
			}
			Query("COMMIT");
			
			// update again for staffid
			$edituser->update();
			
			QuickUpdate("delete from userjobtypes where userid =?", false, array($edituser->id));
			
			Query("BEGIN");
			if(count($postdata['jobtypes']))
				foreach($postdata['jobtypes'] as $type)
					QuickUpdate("insert into userjobtypes values (?, ?)", false, array($edituser->id, $type));
			if(count($postdata['surveytypes']))
				foreach($postdata['surveytypes'] as $type)
					QuickUpdate("insert into userjobtypes values (?, ?)", false, array($edituser->id, $type));
			Query("COMMIT");
		}

		if((!$edituser->ldap && $IS_LDAP) || !$IS_LDAP){
			// If the password is all 0 characters then it was a default form value, so ignore it
			if (!ereg("^0*$", $postdata['password']))
				$edituser->setPassword($postdata['password']);
		}

		// If the pincode is all 0 characters then it was a default form value, so ignore it
		if (!ereg("^0*$", $postdata['pin']))
			$edituser->setPincode($postdata['pin']);
		
		if ($ajax)
			$form->sendTo("users.php");
		else
			redirect("users.php");
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
<? Validator::load_validators(array("ValLogin", "ValPassword", "ValAccesscode", "ValPin", "ValStaffPKey", "ValDataRules", "ValRules")); ?>
</script>
<?

startWindow(_L("User Information"));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>
