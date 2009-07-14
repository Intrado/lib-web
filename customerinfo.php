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
require_once("inc/themes.inc.php");
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

if ($IS_COMMSUITE) {
$helpsteps[$helpstepnum++] = _L("Enter an email domain to ensure that all emails use this domain.");
$formdata["emaildomain"] = array(
	"label" => _L("Email Domain"),
	"fieldhelp" => _L('All user emails must come from this domain.'),
	"value" => getSystemSetting('emaildomain'),
	"validators" => array(
		array("ValLength","max" => 50),
		array("ValDomainList")
	),
	"control" => array("TextField","maxlength" => 50),
	"helpstep" => $helpstepnum
);
} else {
$helpsteps[$helpstepnum++] = _L("Enter an email domain to ensure that all emails use this domain.");
$formdata["emaildomain"] = array(
	"label" => _L("Email Domain"),
	"fieldhelp" => _L('All user emails must come from this domain.'),
	"value" => getSystemSetting('emaildomain'),
	"validators" => array(
	),
	"control" => array("FormHtml","html"=>"<div>".$emaildomain."</div>"),
	"helpstep" => $helpstepnum
);
}


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

if ($IS_COMMSUITE) {
	$helpsteps[$helpstepnum++] = _L("URL to include as email survey links.");
	$formdata["surveyurl"] = array(
		"label" => _L("Survey URL"),
		"fieldhelp" => _L('All user emails must come from this domain.'),
		"value" => getSystemSetting('surveyurl'),
		"validators" => array(
			array("ValRequired"),
			array("ValLength","max" => 200)
		),
		"control" => array("TextField","maxlength" => 200),
		"helpstep" => $helpstepnum
	);
	
	$helpsteps[$helpstepnum++] = _L("Phone number that users should call for support.");
	$formdata["supportphone"] = array(
		"label" => _L("Support Phone Number"),
		"value" => getSystemSetting('_supportphone'),
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 4,"max" => 14)
		),
		"control" => array("TextField","maxlength" => 14),
		"helpstep" => $helpstepnum
	);
	
	$helpsteps[$helpstepnum++] = _L("Email address that users should use if they have questions about basic usage.");
	$formdata["supportemail"] = array(
		"label" => _L("Support Email Address"),
		"value" => getSystemSetting('_supportemail'),
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 1,"max" => 250)
		),
		"control" => array("TextField","maxlength" => 250),
		"helpstep" => $helpstepnum
	);
}

$buttons = array(submit_button(_L("Done"),"submit","accept"),
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
		
		//save data here
		$custname= $postdata['displayname'];
		if ($custname != "" || $custname != $_SESSION['custname']) {
			setSystemSetting('displayname', $custname);
			$_SESSION['custname'] = $custname;
		}
		
		setSystemSetting('defaultareacode', $postdata['defaultareacode']);

		if ($IS_COMMSUITE) {
			setSystemSetting('emaildomain', $postdata['emaildomain']);
			setSystemSetting('surveyurl', $postdata['surveyurl']);
			setSystemSetting('_supportphone', Phone::parse($postdata['supportphone']));
			setSystemSetting('_supportemail', $postdata['supportemail']);
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
$TITLE = _L('Customer Information');

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
if (!$IS_COMMSUITE) {
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" > Contact Support to configure email domain.
</div>
<?
}
endWindow();
include_once("navbottom.inc.php");
?>
