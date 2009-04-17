<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");

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

$formdata = array(
	"alert" => array(
		"label" => _L("Systemwide Alert Message:"),
		"value" => getSystemSetting('alertmessage'),
		"validators" => array(
			array("ValLength","min" => 0,"max" => 255)
		),
		"control" => array("TextArea","maxlength" => 255),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L("The systemwide alert message appears at the top of every page for every user in a big red border."),
	_L("Enter the text to display, or delete all text to remove the alert.")
);

$buttons = array(submit_button(_L("Submit"),"submit","tick"),
				icon_button(_L("Cancel"),"cross",null,"settings.php"));

$form = new Form("alertform", $formdata, $helpsteps, $buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response        

        //save data here
		setSystemSetting('alertmessage', $postdata['alert']);

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
$TITLE = _L('Systemwide Alert Message');

include_once("nav.inc.php");

startWindow(_L("Settings"));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>
