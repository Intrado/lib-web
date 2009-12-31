<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////

class Vallogindisableattempts extends Validator {
	
	function validate ($value, $args, $requiredvalues) {
		if ($requiredvalues[$args['field']] >= $value && $value !== "0")
			return _L("%s must be greater than the invalid login lockout attempts", $this->label);
		
		return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args, requiredvalues) {
				if (parseInt(requiredvalues[args["field"]]) >= parseInt(value) && parseInt(value) !== 0)
					return "' . _L("%s must be greater than the invalid login lockout attempts", $this->label) . '";
				return true;
			}';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$formdata = array();
$helpstepnum = 1;

$helpsteps = array(_L("Select the minimum number of characters a username must contain."));
$formdata["usernamelength"] = array(
	"label" => _L("Minimum Username Length"),
	"fieldhelp" => _L("This is the minimal number of characters a username can contain."),
	"value" => getSystemSetting('usernamelength',5),
	"validators" => array(
		array("ValRequired"),
		array("ValNumber","min" => 4,"max" => 10)
	),
	"control" => array("SelectMenu","values"=>array_combine(range(4,10),range(4,10))),
	"helpstep" => $helpstepnum
);

$helpsteps[$helpstepnum++] = _L("Choose the minimum number of characters a password must contain.");
$formdata["passwordlength"] = array(
	"label" => _L("Minimum Password Length"),
	"fieldhelp" => "This is the minimal number of characters a password can contain.",
	"value" => getSystemSetting('passwordlength',5),
	"validators" => array(
		array("ValRequired"),
		array("ValNumber","min" => 4,"max" => 10)
	),
	"control" => array("SelectMenu","values"=>array_combine(range(4,10),range(4,10))),
	"helpstep" => $helpstepnum
);

$helpsteps[$helpstepnum++] = _L("Select the number of times a user can attempt to log in incorrectly before being temporarily locked out of their account. Select 0 if you would like to disable this feature.");
$formdata["loginlockoutattempts"] = array(
	"label" => _L("Invalid Login Lockout"),
	"fieldhelp" => "Choose how many failed login attempts a user has before being temporarily locked out.",
	"value" => getSystemSetting('loginlockoutattempts'),
	"validators" => array(
		array("ValRequired"),
		array("ValNumber","min" => 0,"max" => 15)
	),
	"control" => array("SelectMenu","values"=>array_combine(range(0,15),range(0,15))),
	"helpstep" => $helpstepnum
);

$helpsteps[$helpstepnum++] = _L("This setting controls the number of hours a user is temporarily locked out of their account if they have triggered an invalid login lockout.");
$formdata["loginlockouttime"] = array(
	"label" => _L("Invalid Login Lockout Period"),
	"fieldhelp" => _L("Controls the number of hours a temporary lockout lasts."),
	"value" => getSystemSetting('loginlockouttime'),
	"validators" => array(
		array("ValRequired"),
		array("ValNumber","min" => 1,"max" => 60)
	),
	"control" => array("SelectMenu","values"=>array_combine(range(1,60),range(1,60))),
	"helpstep" => $helpstepnum
);

$helpsteps[$helpstepnum++] = _L("This setting controls the number of times a user can unsuccessfully log in before their account is disabled.").'<br><br>'._L("Select 0 to disable this feature.");
$formdata["logindisableattempts"] = array(
	"label" => _L("Invalid Login Disable Account"),
	"fieldhelp" => _L("Select the number of unsuccessful login attempts before a user's account is disabled."),
	"value" => getSystemSetting('logindisableattempts'),
	"validators" => array(
		array("ValRequired"),
		array("Vallogindisableattempts","field" => "loginlockoutattempts"),
		array("ValNumber","min" => 0,"max" => 15)
	),
	"requires" => array("loginlockoutattempts"),
	"control" => array("SelectMenu","values"=>array_combine(range(0,15),range(0,15))),
	"helpstep" => $helpstepnum
);

if (getSystemSetting('_hascallback', '0') && !getSystemSetting('_hasselfsignup', '0')) {
	$helpsteps[$helpstepnum++] = _L("This setting will require the entry of a valid student id when calling back to listen to messages.");
	$formdata["msgcallbackrequireid"] = array(
		"label" => _L("Require Student ID on Call Back"),
		"fieldhelp" => _L("Check this to require recipients to enter a valid student ID when retrieving messages."),
		"value" => getSystemSetting('msgcallbackrequireid'),
		"validators" => array(
		),
		"control" => array("CheckBox"),
		"helpstep" => $helpstepnum
	);
}

if (getSystemSetting('hasldap', '0')) {
	$helpsteps[$helpstepnum++] = array(_L("Enter the hostname or IP address of your LDAP server."));
	$formdata["ldaphost"] = array(
		"label" => _L("LDAP Host"),
		"fieldhelp" => _L("LDAP Server hostname"),
		"value" => getSystemSetting('ldaphost', ''),
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("TextField","maxlength" => 50),
		"helpstep" => $helpstepnum
	);

	$helpsteps[$helpstepnum++] = array(_L("Enter the port number that your LDAP server listens on."));
	$formdata["ldapport"] = array(
		"label" => _L("LDAP Port"),
		"fieldhelp" => _L("LDAP Server port, default is 389"),
		"value" => getSystemSetting('ldapport', '389'),
		"validators" => array(
			array("ValRequired"),
			array("ValNumeric"),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("TextField","maxlength" => 50),
		"helpstep" => $helpstepnum
	);
	
	$helpsteps[$helpstepnum++] = array(_L("Enter a binding username for lookups to your LDAP server."));
	$formdata["ldapuser"] = array(
		"label" => _L("LDAP Username"),
		"fieldhelp" => _L("LDAP binding username"),
		"value" => getSystemSetting('ldapuser', ''),
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("TextField","maxlength" => 50),
		"helpstep" => $helpstepnum
	);

	$helpsteps[$helpstepnum++] = array(_L("Enter the password for the binding user to your LDAP server."));
	$formdata["ldappass"] = array(
		"label" => _L("LDAP Password"),
		"fieldhelp" => _L("LDAP binding user's password"),
		"value" => getSystemSetting('ldappass', ''),
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("PasswordField","maxlength" => 50),
		"helpstep" => $helpstepnum
	);

	$helpsteps[$helpstepnum++] = array(_L("Enter the domain used by your LDAP server."));
	$formdata["ldapdomain"] = array(
		"label" => _L("LDAP Domain"),
		"fieldhelp" => _L("LDAP domain"),
		"value" => getSystemSetting('ldapdomain', ''),
		"validators" => array(
			array("ValRequired"),
			array("ValDomain"),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("TextField","maxlength" => 50),
		"helpstep" => $helpstepnum
	);

}

$buttons = array(submit_button(_L("Done"),"submit","accept"),
				icon_button(_L("Cancel"),"cross",null,"settings.php"));

$form = new Form("securitysettings", $formdata, $helpsteps, $buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;

if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response    

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}

		setSystemSetting('usernamelength', $postdata['usernamelength']);
		setSystemSetting('passwordlength', $postdata['passwordlength']);
		setSystemSetting('loginlockoutattempts', $postdata['loginlockoutattempts']);
		setSystemSetting('loginlockouttime', $postdata['loginlockouttime']);
		setSystemSetting('logindisableattempts', $postdata['logindisableattempts']);

		if (getSystemSetting('_hascallback', '0') && !getSystemSetting('_hasselfsignup', '0')) {
			$postdata['msgcallbackrequireid'] ? setSystemSetting('msgcallbackrequireid', '1') : setSystemSetting('msgcallbackrequireid', '0');
		} else {
			setSystemSetting('msgcallbackrequireid', '0');
		}
		
		if (getSystemSetting('hasldap', '0')) {
			setSystemSetting('ldaphost', $postdata['ldaphost']);
			setSystemSetting('ldapport', $postdata['ldapport']);
			setSystemSetting('ldapuser', $postdata['ldapuser']);
			setSystemSetting('ldappass', $postdata['ldappass']);
			setSystemSetting('ldapdomain', $postdata['ldapdomain']);
		}
		
		if ($ajax)
			$form->sendTo("settings.php");
		else
			redirect("settings.php");

	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = _L('Systemwide Security');

require_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("Vallogindisableattempts")); ?>
<? if ($datachange) { ?>
	alert("<?=_L("The data on this form has changed. Your changes cannot be saved.")?>")";
	window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';
<? } ?>
</script>
<?

startWindow(_L("Settings"));
echo $form->render();
endWindow();
require_once("navbottom.inc.php");
?>
