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
if (!$USER->authorize('managesystem') || !getSystemSetting("_hasinfocenter", false)) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$formdata = array();
$formdata["requireemergency"] = array(
		"label" => _L("Require Emergency Phone"),
        "fieldhelp" => _L("Require at least one phone number for every Emergency %s Type.", getJobTitle()),
        "value" => in_array('1',explode('|',getSystemSetting('priorityenforcement', ''))),
        "validators" => array(
        ),
        "control" => array("CheckBox"),
        "helpstep" => 1
);

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

$form = new Form("icsettings", $formdata, null, $buttons);
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
        
		$requirepriorities = array();
		if($postdata['requireemergency'])
			$requirepriorities[] = 1;
		if($postdata['requirehighpriority'])
			$requirepriorities[] = 2;
		
		setSystemSetting('priorityenforcement',implode('|',$requirepriorities));
		
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
$TITLE = _L('InfoCenter Settings');

include_once("nav.inc.php");

?>
<script type="text/javascript">

<? if ($datachange) { ?>

alert("data has changed on this form!");
window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';

<? } ?>

</script>
<?

startWindow(_L("InfoCenter Settings"));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>
