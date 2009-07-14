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

if (!$USER->authorize('managesystem') && !getSystemSetting("_hasselfsignup", false)) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$emaildomain = getSystemSetting('emaildomain');
if ($emaildomain == "")
	$emaildomain = "(no domain configured)";
	
	
$formdata = array();

$formdata["restrictdomain"] = array(
        "label" => _L("Restrict Account Email to Domain and Subdomains"),
        "value" => getSystemSetting("subscriberauthdomain", "0") ? true : false,
        "validators" => array(    
        ),
        "control" => array("CheckBox"),
        "helpstep" => 1
    );
$formdata["domain"] = array(
        "label" => _L("Email Domain"),
        "value" => "",
        "validators" => array(    
        ),
        "control" => array("FormHtml","html"=>"<div>".$emaildomain."</div>"),
        "helpstep" => 1
    );
$formdata["requiresitecode"] = array(
        "label" => _L("Require Site Access Code to Register"),
        "value" => getSystemSetting("subscriberauthcode", "0") ? true : false,
        "validators" => array(    
        ),
        "control" => array("CheckBox"),
        "helpstep" => 1
    );
$formdata["sitecode"] = array(
        "label" => _L("Site Access Code"),
        "value" => getSystemSetting("subscribersitecode", ""),
        "validators" => array(
            array("ValLength","min" => 3,"max" => 255)
        ),
        "control" => array("TextField","maxlength" => 255),
        "helpstep" => 1
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
        
		$postdata['restrictdomain'] ? setSystemSetting("subscriberauthdomain", "1") : setSystemSetting("subscriberauthdomain", "0");
		$postdata['requiresitecode'] ? setSystemSetting("subscriberauthcode", "1") : setSystemSetting("subscriberauthcode", "0");
		setSystemSetting("subscribersitecode", $postdata['sitecode']);
				
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
$TITLE = 'Self-Signup Settings';

include_once("nav.inc.php");

startWindow('Subscriber Options');
echo $form->render();
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" > Contact Support to configure email domain.
</div>
<?
endWindow();

include_once("navbottom.inc.php");
?>