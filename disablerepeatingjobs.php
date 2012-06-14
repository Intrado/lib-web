<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
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
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$formdata = array();
$helpstepnum = 1;

$helpsteps = array(_L("Click this option to disable all repeating jobs in the system. This feature can be used to prevent the system from running scheduled jobs during vacations."));
$formdata["disablerepeat"] = array(
	"label" => _L("Disable Repeating %s", getJobsTitle()),
	"fieldhelp" => _L("Use this to disable all repeating %s in the system.",getJobsTitle()),
	"value" => getSystemSetting('disablerepeat'),
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L("Cancel"),"cross",null,"settings.php"));

$form = new Form("disablerepeatingjobs", $formdata, $helpsteps, $buttons);
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

		$postdata['disablerepeat'] ? setSystemSetting('disablerepeat', '1') : setSystemSetting('disablerepeat', '0');
		
		if ($ajax)
			$form->sendTo("settings.php");
		else
			redirect("settings.php");

	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = _L("admin").":"._L("settings");
$TITLE = _L('Enable/Disable Repeating %s',getJobsTitle());

require_once("nav.inc.php");

?>
<script>
<? if ($datachange) { ?>
	alert("<?=_L("The data on this form has changed. You're changes cannot be saved.")?>");
	window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';
<? } ?>
</script>
<?

startWindow(_L("Settings"));
echo $form->render();
endWindow();
require_once("navbottom.inc.php");
?>
