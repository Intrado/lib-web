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
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/FieldMap.obj.php");


require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$USER->authorize('managesystem') || !getSystemSetting("_hasselfsignup", false)) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

class SubscriberExpirationField extends FormItem {
	// @param $valueJSON = [reminder1:,reminder2:,reminder3:,expiredays]
	function render ($valueJSON) {
		$n = $this->form->name."_".$this->name;
		
		$data = json_decode($valueJSON, true);
		if (!is_array($data) || empty($data)) {
			$data = array('reminder1' => '30', 'reminder2' => '15', 'reminder3' => '2', 'expiredays' => '180');
		}

		// Hidden input item to store values in
		$str  = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($valueJSON).'" />';
		// GUI table
		$str .= "<div class='signup_settings'>";
		$str .= "<div><label>Automatic Close After</label>";
		$str .= '<input id="'.$n.'expiredays" name="'.$n.'expiredays" type="text" value="'.$data['expiredays'].'" size="5" onchange="storeValue();" />';
		$str .= "days since last login</div>";
		$str .= "<div><label>First Reminder</label>";
		$str .= '<input id="'.$n.'reminder1" name="'.$n.'reminder1" type="text" value="'.$data['reminder1'].'" size="5" onchange="storeValue();" />';
		$str .= "days before automatic closure</div>";
		$str .= "<div><label>Second Reminder</label>";
		$str .= '<input id="'.$n.'reminder2" name="'.$n.'reminder2" type="text" value="'.$data['reminder2'].'" size="5" onchange="storeValue();" />';
		$str .= "days before automatic closure</div>";
		$str .= "<div><label>Final Reminder</label>";
		$str .= '<input id="'.$n.'reminder3" name="'.$n.'reminder3" type="text" value="'.$data['reminder3'].'" size="5" onchange="storeValue();" />';
		$str .= "days before automatic closure</div>";
		$str .= "</div>";
		// javascript
		$str .= '<script type="text/javascript">
			themeformname = "'.$this->form->name.'";
			themeformitem = "'.$n.'";

			// Save changes in the hidden field
			function storeValue() {
				$(themeformitem).value = Object.toJSON({
					"expiredays": $(themeformitem+"expiredays").value,
					"reminder1": $(themeformitem+"reminder1").value,
					"reminder2": $(themeformitem+"reminder2").value,
					"reminder3": $(themeformitem+"reminder3").value
				});
				form_do_validation($(themeformname), $(themeformitem));
			}
			</script>';
					
		
		return $str;
	}
}

class ValSubscriberExpiration extends Validator {
	function validate ($value, $args) {
		$checkval = json_decode($value);
		$errortext = "";
			
		// validate values are in range
		if (!is_numeric($checkval->expiredays) || $checkval->expiredays < 0 || $checkval->expiredays > 365)
			$errortext .= " " . _L("Automatic Close After days must be between 0 and 365.");
		if (!is_numeric($checkval->reminder1) || $checkval->reminder1 < 0 || $checkval->reminder1 > 60)
			$errortext .= " " . _L("First Reminder days must be between 0 and 60.");
		if (!is_numeric($checkval->reminder2) || $checkval->reminder2 < 0 || $checkval->reminder2 > 60)
			$errortext .= " " . _L("Second Reminder days must be between 0 and 60.");
		if (!is_numeric($checkval->reminder3) || $checkval->reminder3 < 0 || $checkval->reminder3 > 60)
			$errortext .= " " . _L("Final Reminder days must be between 0 and 60.");
		// validate values are valid relative to one another !!!! this is the entire purpose of the custom formitem
		if ($checkval->expiredays > 0) {
			if ($checkval->reminder1 != 0 && $checkval->expiredays <= $checkval->reminder1)
				$errortext .= " " . _L("First Reminder must be less than Automatic Close After.");
			if ($checkval->reminder2 != 0 && $checkval->reminder1 <= $checkval->reminder2)
				$errortext .= " " . _L("Second Reminder must be less than First Reminder.");
			if ($checkval->reminder3 != 0 && $checkval->reminder2 <= $checkval->reminder3)
				$errortext .= " " . _L("Final Reminder must be less than Second Reminder.");
		}
		
		if ($errortext)
			return $this->label . $errortext;
		else
			return true;
	}

	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				vals = value.evalJSON();
				var errortext = "";
				
				var re = /^[0-9]*$/;

				// verify numeric
				if (!re.test(vals.expiredays))
					errortext += " "+ "'.addslashes(_L("Automatic Close After must be between 0 and 365.")).'";
				if (!re.test(vals.reminder1))
					errortext += " "+ "'.addslashes(_L("Reminder must be between 0 and 60.")).'";
				if (!re.test(vals.reminder2))
					errortext += " "+ "'.addslashes(_L("Reminder must be between 0 and 60.")).'";
				if (!re.test(vals.reminder3))
					errortext += " "+ "'.addslashes(_L("Reminder must be between 0 and 60.")).'";
					
				// number within range
				if (parseFloat(vals.expiredays) === false || parseFloat(vals.expiredays) > 365 || parseFloat(vals.expiredays) < 0)
					errortext += " "+ "'.addslashes(_L("Automatic Close After must be between 0 and 365.")).'";
				if (parseFloat(vals.reminder1) === false || parseFloat(vals.reminder1) > 60 || parseFloat(vals.reminder1) < 0)
					errortext += " "+ "'.addslashes(_L("Reminder must be between 0 and 60.")).'";
				if (parseFloat(vals.reminder2) === false || parseFloat(vals.reminder2) > 60 || parseFloat(vals.reminder2) < 0)
					errortext += " "+ "'.addslashes(_L("Reminder must be between 0 and 60.")).'";
				if (parseFloat(vals.reminder3) === false || parseFloat(vals.reminder3) > 60 || parseFloat(vals.reminder3) < 0)
					errortext += " "+ "'.addslashes(_L("Reminder must be between 0 and 60.")).'";
				
				// r1>r2>r3
				if (parseFloat(vals.expiredays) > 0) {
					if (parseFloat(vals.reminder1) > 0 && parseFloat(vals.expiredays) <= parseFloat(vals.reminder1))
						errortext += " " + "'.addslashes(_L("First Reminder must be less than Automatic Close After.")).'";
					if (parseFloat(vals.reminder2) > 0 && parseFloat(vals.reminder1) <= parseFloat(vals.reminder2))
						errortext += " " + "'.addslashes(_L("Second Reminder must be less than First Reminder.")).'";
					if (parseFloat(vals.reminder3) > 0 && parseFloat(vals.reminder2) <= parseFloat(vals.reminder3))
						errortext += " " + "'.addslashes(_L("Final Reminder must be less than Second Reminder.")).'";
				}
				
				if (errortext)
					return label + errortext;
				else
					return true;
			}';
	}

}

$emaildomain = getSystemSetting('emaildomain');
if ($emaildomain == "")
	$emaildomain = "(no domain configured)";

$accountexpirationdata = array();
$accountexpirationdata['expiredays'] = getSystemSetting("subscriber.expiredays", "180");
$accountexpirationdata['reminder1'] = getSystemSetting("subscriber.reminder.1", "30");
$accountexpirationdata['reminder2'] = getSystemSetting("subscriber.reminder.2", "15");
$accountexpirationdata['reminder3'] = getSystemSetting("subscriber.reminder.3", "2");

$formdata = array();

$formdata['signupsection'] = _L('Account Creation');

$formdata["restrictdomain"] = array(
        "label" => _L("Restrict to Domain"),
        "fieldhelp" => _L('Select this option to restrict which email domains a new subscriber may use.  Account may only be created with an email in one of the domains or subdomains listed.'),
        "value" => getSystemSetting("subscriberauthdomain", "0") ? true : false,
        "validators" => array(    
        ),
        "control" => array("CheckBox"),
        "helpstep" => 1
    );
$formdata["domain"] = array(
        "label" => _L("Email Domain"),
        "fieldhelp" => _L('Displays the permitted email domains for new subscribers.  Subdomains are also allowed.'),
        "control" => array("FormHtml","html"=>"<div>".$emaildomain."</div>"),
        "helpstep" => 1
    );
$formdata["requiresitecode"] = array(
        "label" => _L("Require Site Code"),
        "fieldhelp" => _L('Requires new subscribers to enter a special code when subscribing.  You must provide this code to create a new subscriber account.'),
        "value" => getSystemSetting("subscriberauthcode", "0") ? true : false,
        "validators" => array(    
        ),
        "control" => array("CheckBox"),
        "helpstep" => 2
    );
$formdata["sitecode"] = array(
        "label" => _L("Site Access Code"),
        "fieldhelp" => _L('A special code that new subscribers will need to enter to sign up for the system if required to do so.'),
        "value" => getSystemSetting("subscribersitecode", ""),
        "validators" => array(
            array("ValLength","min" => 3,"max" => 255)
        ),
        "control" => array("TextField","maxlength" => 255),
        "helpstep" => 2
    );

$formdata['accountwarningoptions'] = _L('Account Expiration');
$formdata["expirationdata"] = array(
        "label" => _L("Expiration Settings"),
        "fieldhelp" => _L('These settings control how long a subscriber account may be inactive before the account automatically expires. Login reminder emails may also be sent at the intervals specified here.'),
        "value" => json_encode($accountexpirationdata),
        "validators" => array(
            array("ValSubscriberExpiration")
        ),
        "control" => array("SubscriberExpirationField"),
        "helpstep" => 3
    );

$helpsteps = array (
	_L('If you would like to restrict subscribers to only using email addresses from a specific domain, check the Restrict to Domain option. To add a domain, please contact technical support.'),
	_L('A site code is a special code new subscribers would need to enter when creating their account. If you would like to use this option, simply check the Require Site Code checkbox and enter a code in the field below.'),
	_L('These settings control how long a subscriber\'s account may remain inactive before expiring. <br><br>The first field controls the length of time before expiration, a setting of zero will disable automatic account expiration, or set between 1 and 365 days.
	<br><br>The reminder fields control when an automatically generated	reminder email will be sent to the account owner. The values in the reminder fields are relative to the number of days prior to account expiration, and must be between 0 and 60. For example, a value of 2 in the \'Final Reminder\' field would send an email two days prior to account expiration.
	<br><br>Reminders are optional, to disable one or more set them to zero. For example you may want only one reminder a week before, set First Reminder to 7, Second to 0, Final to 0.' )
);

$buttons = array(submit_button("Done","submit","accept"),
                icon_button("Cancel","cross",null,"settings.php"));

$form = new Form("subscriberoptions",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

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
        
		// account creation settings
		$postdata['restrictdomain'] ? setSystemSetting("subscriberauthdomain", "1") : setSystemSetting("subscriberauthdomain", "0");
		$postdata['requiresitecode'] ? setSystemSetting("subscriberauthcode", "1") : setSystemSetting("subscriberauthcode", "0");
		setSystemSetting("subscribersitecode", $postdata['sitecode']);
		// account expiration settings
		$expirationdata = json_decode($postdata['expirationdata']);
		setSystemSetting("subscriber.reminder.1", $expirationdata->reminder1);
		setSystemSetting("subscriber.reminder.2", $expirationdata->reminder2);
		setSystemSetting("subscriber.reminder.3", $expirationdata->reminder3);
		setSystemSetting("subscriber.expiredays", $expirationdata->expiredays);
		
		// return to settings page
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
$TITLE = _L('Self-Signup Settings');

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValLength", "ValSubscriberExpiration")); ?>
</script>
<?

startWindow(_L('Subscriber Options'));
echo $form->render();
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" > Contact Support to configure email domain.
</div>
<?
endWindow();

include_once("navbottom.inc.php");
?>