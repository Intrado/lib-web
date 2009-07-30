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
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem') || !getSystemSetting("_hasportal", false) || !$USER->authorize('portalaccess')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$maxphones = getSystemSetting("maxphones", 3);
$maxemails = getSystemSetting("maxemails", 2);
$maxsms = getSystemSetting("maxsms", 2);

$formdata = array();
$formdata["tokenlife"] = array(
        "label" => _L("Activation Code Lifetime (1-365 days)"),
        "value" => getSystemSetting('tokenlife', 30),
        "validators" => array(
            array("ValLength","min" => 1,"max" => 3),
            array("ValNumeric","min"=>1,"max"=>365)
        ),
        "control" => array("TextField","maxlength" => 3),
        "helpstep" => 1
);
$checked = "false";
if (getSystemSetting('portalphoneactivation', 0))
	$checked = "true";
$formdata["portalphoneactivation"] = array(
		"label" => _L("Allow Activation via Phone"),
        "value" => $checked,
        "validators" => array(
        ),
        "control" => array("Checkbox"),
        "helpstep" => 1
);
$checked = "false";
if (getSystemSetting('priorityenforcement', 0))
	$checked = "true";
$formdata["priorityenforcement"] = array(
		"label" => _L("Require Phone Numbers for Emergency and High Priority Job Types"),
        "value" => $checked,
        "validators" => array(
        ),
        "control" => array("CheckBox"),
        "helpstep" => 1
);
$restrictedfields = array(); // all fields
$selectedvalues = array(); // selected fields
for ($i=0; $i<$maxphones; $i++) {
	$restrictedfields["lockedphone".$i] = "Phone ".($i+1); // TODO add phone label
	if (getSystemSetting("lockedphone".$i, 0)) $selectedvalues[] = "lockedphone".$i;
}
for ($i=0; $i<$maxemails; $i++) {
	$restrictedfields["lockedemail".$i] = "Email ".($i+1); // TODO add phone label
	if (getSystemSetting("lockedemail".$i, 0)) $selectedvalues[] = "lockedemail".$i;
}
if (getSystemSetting("_hassms", false)) {
	for ($i=0; $i<$maxsms; $i++) {
		$restrictedfields["lockedsms".$i] = "SMS ".($i+1); // TODO add phone label
		if (getSystemSetting("lockedsms".$i, 0)) $selectedvalues[] = "lockedsms".$i;
	}
}
$formdata["multicheckbox"] = array(
        "label" => _L("Restricted Destination Fields"),
        "value" => $selectedvalues,
        "validators" => array(
            array("ValRequired")
        ),
        "control" => array("MultiCheckbox", "height" => "100px", "values" => $restrictedfields),
        "helpstep" => 1
);


$helpsteps = array (
	_L("Contact Manager Settings."),
	_L("yes you can")
);

$buttons = array(submit_button(_L("Done"),"submit","accept"),
				icon_button(_L("Cancel"),"cross",null,"settings.php"));

$form = new Form("account", $formdata, $helpsteps, $buttons);
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
        
        //save data here
        
        setSystemSetting('tokenlife', $postdata['tokenlife']);

        if ($postdata['portalphoneactivation'])
        	setSystemSetting('portalphoneactivation', '1');
        else
        	setSystemSetting('portalphoneactivation', '0');
        
        if ($postdata['priorityenforcement'])
        	setSystemSetting('priorityenforcement', '1');
        else
        	setSystemSetting('priorityenforcement', '0');
        
        $fields = $postdata['multicheckbox'];
		// unset them all
		for ($i = 0; $i < $maxphones; $i++) {
			setSystemSetting('lockedphone' . $i, '0');
		}
		for ($i = 0; $i < $maxemails; $i++) {
			setSystemSetting('lockedemail' . $i, '0');
		}
		for ($i = 0; $i < $maxsms; $i++) {
			setSystemSetting('lockedsms' . $i, '0');
		}
		// now set the ones selected
        foreach ($fields as $k => $v)
			setSystemSetting($v, '1');
		
        	
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
$TITLE = _L('Contact Manager Settings');

include_once("nav.inc.php");

?>
<script type="text/javascript">

<? if ($datachange) { ?>

alert("data has changed on this form!");
window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';

<? } ?>

</script>
<?


echo $form->render();
include_once("navbottom.inc.php");
?>