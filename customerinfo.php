<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/JobType.obj.php");
require_once("obj/Setting.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}


///////////////////////////////////////////////////////////////////////////////////
// Custom Validators


class ValAreaCode extends Validator {
	function validate ($value, $args) {
		if ($value == "")
			return true;
		if (strlen($value) <> 3)
			return "$this->label must be a 333 digit area code";
		
		if (($value[0] < 2) || // areacode cannot start with 0 or 1
			($value[1] == 1 && $value[2] == 1) || // areacode cannot be N11
			($value == 555) // areacode cannot be 555
		) {
			return "$this->label is not a valid area code";
		}		
		return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				if (value.length != 3)
					return label + " must be a 3 digit area code";
				if ((value.charAt(0) == "0" || value.charAt(0) == "1") || // areacode cannot start with 0 or 1
					(value.charAt(1) == "1" && value.charAt(2) == "1") || // areacode cannot be N11
					("555" == value)  // areacode cannot be 555
					) {
					return label + " is not a valid area code";
				}
				return true;
			}';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$emaildomain = getSystemSetting('emaildomain');
if ($emaildomain == "")
	$emaildomain = "(no domain configured)";

$formdata = array();
$helpstepnum = 1;

$helpsteps = array(_L("Enter the customer name found in the upper right corner throughout the system."));
$formdata["displayname"] = array(
	"label" => _L("Customer Display Name"),
	"fieldhelp" => _L('This is the customer name found in the upper right corner throughout the system.'),
	"value" => getSystemSetting('displayname'),
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 1,"max" => 50)
	),
	"control" => array("TextField","maxlength" => 50),
	"helpstep" => $helpstepnum
);


$helpsteps[$helpstepnum++] = _L("Enter an email domain to ensure that all emails use this domain.");
$formdata["emaildomain"] = array(
	"label" => _L("Email Domain"),
	"fieldhelp" => _L('All user emails must come from this domain.'),
	"value" => getSystemSetting('emaildomain'),
	"control" => array("FormHtml","html"=>"<div class='domain'>".$emaildomain."</div>"),
	"helpstep" => $helpstepnum
);


$helpsteps[$helpstepnum++] = _L("Set your local area code in this field. If a phone number is entered into the system without an area code, this area code will be used.");
$formdata["defaultareacode"] = array(
	"label" => _L("Local Area Code"),
	"value" => getSystemSetting('defaultareacode'),
	"fieldhelp" => _L('Your local area code which will be prepended to any phone number entered without an area code.'),
	"validators" => array(
		array("ValLength","min" => 3,"max" => 3),
		array("ValNumeric"),
		array("ValAreaCode")
	),
	"control" => array("TextField","maxlength" => 3),
	"helpstep" => $helpstepnum
);

$helpsteps[$helpstepnum++] = _L("Require users have at least one phone number set for High Priority calls.");
$formdata["requireemergency"] = array(
		"label" => _L("Require Emergency Phone"),
        "fieldhelp" => _L("Require at least one phone number for every Emergency %s Type.", getJobTitle()),
        "value" => in_array('1',explode('|',getSystemSetting('priorityenforcement', ''))),
        "validators" => array(
        ),
        "control" => array("CheckBox"),
        "helpstep" => 1
);

$helpsteps[$helpstepnum++] = _L("Require users have at least one phone number set for Emergency calls.");
$formdata["requirehighpriority"] = array(
		"label" => _L("Require High Priority Phone"),
		"fieldhelp" => _L("Require at least one phone number for every High Priority %s Type.", getJobTitle()),
		"value" => in_array('2',explode('|',getSystemSetting('priorityenforcement', ''))),
		"validators" => array(),
		"control" => array("CheckBox"),
		"helpstep" => 1
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L("Cancel"),"cross",null,"settings.php"));

$form = new Form("customerinfo", $formdata, $helpsteps, $buttons);
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
		

		// emergency and high priority data
		$requirepriorities = array();
		if($postdata['requireemergency'])
			$requirepriorities[] = 1;
		if($postdata['requirehighpriority'])
			$requirepriorities[] = 2;
		
		setSystemSetting('priorityenforcement',implode('|',$requirepriorities));

		//save data here
		$custname= $postdata['displayname'];
		if ($custname != "" || $custname != $_SESSION['custname']) {
			setSystemSetting('displayname', $custname);
			$_SESSION['custname'] = $custname;
		}
		
		setSystemSetting('defaultareacode', $postdata['defaultareacode']);

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
$TITLE = _L('Customer Settings');

include_once("nav.inc.php");
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValAreaCode")); ?>
<? if ($datachange) { ?>
	alert("<?=_L("The data on this form has changed. Your changes cannot be saved.")?>")";
	window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';
<? } ?>
</script>
<?

startWindow(_L("Settings"));
echo $form->render();
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" > Contact Support to configure email domain.
</div>
<?
endWindow();
include_once("navbottom.inc.php");
?>
