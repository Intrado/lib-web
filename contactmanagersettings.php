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
        "label" => _L("Code Lifetime"),
        "fieldhelp" => _L("The number of days Activation Code is valid after generation, before it is expired. 1-365 days."),
        "value" => getSystemSetting('tokenlife', 30),
        "validators" => array(
        	array("ValRequired"),
            array("ValNumber","min"=>1,"max"=>365)
        ),
        "control" => array("TextField","maxlength" => 3),
        "helpstep" => 1
);
$formdata["portalphoneactivation"] = array(
		"label" => _L("Allow Activation via Phone"),
        "fieldhelp" => _L("Contact Manager users may use their phone to dial into the system to associate contacts.  Does not require Activation Code generation."),
        "value" => getSystemSetting('portalphoneactivation', 0),
        "validators" => array(
        ),
        "control" => array("Checkbox"),
        "helpstep" => 1
);
$formdata["priorityenforcement"] = array(
		"label" => _L("Require Emergency Phone"),
        "fieldhelp" => _L("Require at least one phone number for every Emergency Job Type."),
        "value" => getSystemSetting('priorityenforcement', 0),
        "validators" => array(
        ),
        "control" => array("CheckBox"),
        "helpstep" => 1
);

$phonelabels = QuickQueryList("select sequence, label from destlabel where type='phone'", true);
$emaillabels = QuickQueryList("select sequence, label from destlabel where type='email'", true);

$restrictedfields = array(); // all fields
$selectedvalues = array(); // selected fields
for ($i=0; $i<$maxphones; $i++) {
	$destlabel = "";
	if (isset($phonelabels[$i]) && mb_strlen($phonelabels[$i]) > 0) {
		$destlabel = " (" . $phonelabels[$i] . ")";
	}
	$restrictedfields["lockedphone".$i] = "Phone ".($i+1) . $destlabel;
	if (getSystemSetting("lockedphone".$i, 0)) $selectedvalues[] = "lockedphone".$i;
}
for ($i=0; $i<$maxemails; $i++) {
	$destlabel = "";
	if (isset($emaillabels[$i]) && mb_strlen($emaillabels[$i]) > 0) {
		$destlabel = " (" . $emaillabels[$i] . ")";
	}
	$restrictedfields["lockedemail".$i] = "Email ".($i+1) . $destlabel;
	if (getSystemSetting("lockedemail".$i, 0)) $selectedvalues[] = "lockedemail".$i;
}
if (getSystemSetting("_hassms", false)) {
	$smslabels = QuickQueryList("select sequence, label from destlabel where type='sms'", true);
	for ($i=0; $i<$maxsms; $i++) {
		$destlabel = "";
		if (isset($smslabels[$i]) && mb_strlen($smslabels[$i]) > 0) {
			$destlabel = " (" . $smslabels[$i] . ")";
		}
		$restrictedfields["lockedsms".$i] = "SMS ".($i+1) . $destlabel;
		if (getSystemSetting("lockedsms".$i, 0)) $selectedvalues[] = "lockedsms".$i;
	}
}
$formdata["multicheckbox"] = array(
        "label" => _L("Restricted Fields"),
        "fieldhelp" => _L("Destination Fields that Contact Manager users are not allowed to edit."),
        "value" => $selectedvalues,
        "validators" => array(
        ),
        "control" => array("MultiCheckbox", "height" => "100px", "values" => $restrictedfields),
        "helpstep" => 1
);


$buttons = array(submit_button(_L("Done"),"submit","accept"),
				icon_button(_L("Cancel"),"cross",null,"settings.php"));

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
