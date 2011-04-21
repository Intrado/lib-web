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

$emaildomain = getSystemSetting('emaildomain');
if ($emaildomain == "")
	$emaildomain = "(no domain configured)";
	
	
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
        "value" => "",
        "validators" => array(    
        ),
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
        "helpstep" => 1
    );
$formdata["sitecode"] = array(
        "label" => _L("Site Access Code"),
        "fieldhelp" => _L('A special code that new subscribers will need to enter to sign up for the system if required to do so.'),
        "value" => getSystemSetting("subscribersitecode", ""),
        "validators" => array(
            array("ValLength","min" => 3,"max" => 255)
        ),
        "control" => array("TextField","maxlength" => 255),
        "helpstep" => 1
    );

$formdata['accountwarningoptions'] = _L('Account Expiration');
$formdata["reminder1"] = array(
        "label" => _L("First Reminder"),
        "fieldhelp" => _L('Subscribers who have not logged in will receive their first email warning this many days prior to account expiration. Entering 0 disables this feature.'),
        "value" => getSystemSetting("subscriber.reminder.1", "30"),
        "validators" => array(
            array("ValNumber","min" => 0,"max" => 60)
        ),
        "control" => array("TextField","maxlength" => 5),
        "helpstep" => 2
    );
$formdata["reminder2"] = array(
        "label" => _L("Second Reminder"),
        "fieldhelp" => _L('Subscribers who have not logged in will receive their second email warning this many days prior to account expiration. Entering 0 disables this feature.'),
        "value" => getSystemSetting("subscriber.reminder.2", "15"),
        "validators" => array(
            array("ValNumber","min" => 0,"max" => 60)
        ),
        "control" => array("TextField","maxlength" => 5),
        "helpstep" => 2
    );
$formdata["reminder3"] = array(
        "label" => _L("Final Reminder"),
        "fieldhelp" => _L('Subscribers who have not logged in will receive their final email warning this many days prior to account expiration. Entering 0 disables this feature.'),
        "value" => getSystemSetting("subscriber.reminder.3", "2"),
        "validators" => array(
            array("ValNumber","min" => 0,"max" => 60)
        ),
        "control" => array("TextField","maxlength" => 5),
        "helpstep" => 2
    );
$formdata["expiredays"] = array(
        "label" => _L("Automatic Close After"),
        "fieldhelp" => _L('A subscriber\'s account will automatically expire after not logging in for this many days. Entering 0 disables this feature.'),
        "value" => getSystemSetting("subscriber.expiredays", "180"),
        "validators" => array(
            array("ValNumber","min" => 0,"max" => 365)
        ),
        "control" => array("TextField","maxlength" => 5),
        "helpstep" => 2
    );


$buttons = array(submit_button("Done","submit","accept"),
                icon_button("Cancel","cross",null,"settings.php"));
                
$form = new Form("subscriberoptions",$formdata,null,$buttons);

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
		setSystemSetting("subscriber.reminder.1", $postdata['reminder1']);
		setSystemSetting("subscriber.reminder.2", $postdata['reminder2']);
		setSystemSetting("subscriber.reminder.3", $postdata['reminder3']);
		setSystemSetting("subscriber.expiredays", $postdata['expiredays']);
		
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