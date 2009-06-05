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
$helpsteps = array(_L("Security adjustments are made on this page."));

$helpstepnum = 1;

$formdata["usernamelength"] = array(
	"label" => _L("Minimum Username Length"),
	"value" => getSystemSetting('usernamelength',5),
	"validators" => array(
		array("ValRequired"),
		array("ValNumber","min" => 4,"max" => 10)
	),
	"control" => array("SelectMenu","values"=>array_combine(range(4,10),range(4,10))),
	"helpstep" => $helpstepnum
);
$helpsteps[$helpstepnum++] = _L("Enter the Minimum number of characters acceptable for a username.")."<br>"._L("Allowed values are between 4 and 10 characters");

$formdata["passwordlength"] = array(
	"label" => _L("Minimum Password Length"),
	"value" => getSystemSetting('passwordlength',5),
	"validators" => array(
		array("ValRequired"),
		array("ValNumber","min" => 4,"max" => 10)
	),
	"control" => array("SelectMenu","values"=>array_combine(range(4,10),range(4,10))),
	"helpstep" => $helpstepnum
);
$helpsteps[$helpstepnum++] = _L("Enter the Minimum number of characters acceptable for a password.")."<br>"._L("Allowed values are between 4 and 10 characters");

$formdata["loginlockoutattempts"] = array(
	"label" => _L("Invalid Login Lockout"),
	"value" => getSystemSetting('loginlockoutattempts'),
	"validators" => array(
		array("ValRequired"),
		array("ValNumber","min" => 0,"max" => 15)
	),
	"control" => array("SelectMenu","values"=>array_combine(range(0,15),range(0,15))),
	"helpstep" => $helpstepnum
);
$helpsteps[$helpstepnum++] = _L("This setting controls the number of invalid attempts a user has before a temporary lock is placed on their account.")."<br>"._L("Select zero to disable.");

$formdata["loginlockouttime"] = array(
	"label" => _L("Invalid Login Lockout Period"),
	"value" => getSystemSetting('loginlockouttime'),
	"validators" => array(
		array("ValRequired"),
		array("ValNumber","min" => 1,"max" => 60)
	),
	"control" => array("SelectMenu","values"=>array_combine(range(1,60),range(1,60))),
	"helpstep" => $helpstepnum
);
$helpsteps[$helpstepnum++] = _L("This setting controls the amount of time a user is temporarily locked out.");

$formdata["logindisableattempts"] = array(
	"label" => _L("Invalid Login Disable Account"),
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
$helpsteps[$helpstepnum++] = _L("This setting controls the number of invalid attempts a user has before their account is disabled. This must be greater than the Invalid Login Lockout Attempts.")."<br>"._L("Select zero to disable.");

$formdata["msgcallbackrequireid"] = array(
	"label" => _L("Require Student ID on Call Back"),
	"value" => getSystemSetting('msgcallbackrequireid'),
	"validators" => array(
	),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);
$helpsteps[$helpstepnum++] = _L("This setting will require the entry of a valid student id when calling back to listen to messages.");

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
		setSystemSetting('msgcallbackrequireid', $postdata['msgcallbackrequireid']);
		
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

?>
<script>
<? Validator::load_validators(array("Vallogindisableattempts")); ?>
</script>
<?

require_once("nav.inc.php");

?>
<script>
<? if ($datachange) { ?>
	alert("<?=_L("The data on this form has changed. You're changes cannot be saved.")?>")";
	window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';
<? } ?>
</script>
<?

startWindow(_L("Settings"));
echo $form->render();
endWindow();
require_once("navbottom.inc.php");
?>
