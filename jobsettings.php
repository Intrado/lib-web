<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
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

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$formdata = array();
$helpstepnum = 0;

$helpsteps[$helpstepnum++] = _L("This specifies the default Caller ID to use for new Jobs. If a user has access rights, they may override this with a new setting.");
if (getSystemSetting('_hascallback', false)) {
	$formdata["callerid"] = array(
		"label" => _L("Default Caller ID Number"),
		"fieldhelp" => _L("This is the default Caller ID for all jobs."),
		"control" => array("FormHtml","html"=>Phone::format(getDefaultCallerID())),
		"helpstep" => $helpstepnum
	);
} else {
	$formdata["callerid"] = array(
		"label" => _L("Default Caller ID Number"),
		"fieldhelp" => _L("This is the default Caller ID for all jobs."),
		"value" => Phone::format(getSystemSetting('callerid')),
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 2,"max" => 20),
			array("ValPhone")),
		"control" => array("TextField","maxlength" => 20),
		"helpstep" => $helpstepnum
	);
}

$helpsteps[$helpstepnum++] = _L("The Autoreport email address and name is used by the system when sending Autoreports. The reports will come from this address and name.");
$formdata["autoreportreplyemail"] = array(
	"label" => _L("Autoreport Email Address"),
	"fieldhelp" => "Autoreports will originate from this email address.",
	"value" => getSystemSetting('autoreport_replyemail'),
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 0,"max" => 255),
		array("ValEmail")
	),
	"control" => array("TextField","maxlength" => 255),
	"helpstep" => $helpstepnum
);

$formdata["autoreportreplyname"] = array(
	"label" => _L("Autoreport Email Name"),
	"fieldhelp" => "This is the name associated with the email address above.",
	"value" => getSystemSetting('autoreport_replyname'),
	"validators" => array(
		array("ValRequired"),
		array("ValLength","min" => 1,"max" => 100)),
	"control" => array("TextField","maxlength" => 100),
	"helpstep" => $helpstepnum
);

if($IS_COMMSUITE || getSystemSetting('_dmmethod', 'asp') != 'asp'){

	$helpsteps[$helpstepnum++] = _L("If you use a SmartCall appliance or have a licensed solution, enter the minimum and maximum length of extensions on your local network. For example, to have the system call  four digit extensions on your local network, set these values to 4.");
	$formdata["easycallmin"] = array(
		"label" => _L("Minimum Extensions Length"),
		"fieldhelp" => _L("This is the minimum length of an extension on your local network which your SmartCall appliance or licensed solution may call."),
		"value" => getSystemSetting('easycallmin',10),
		"validators" => array(
			array("ValRequired"),
			array("ValNumber","min" => 1,"max" => 10)),
		"control" => array("SelectMenu","values"=>array_combine(range(1,10),range(1,10))),
		"helpstep" => $helpstepnum
	);

	$formdata["easycallmax"] = array(
		"label" => _L("Maximum Extensions Length"),
		"fieldhelp" => _L("This is the maximum length of an extension on your local network which your SmartCall appliance or licensed solution may call."),
		"value" => getSystemSetting('easycallmax',10),
		"fieldhelp" => _L("This is the maximum length of an extension on your local network which your SmartCall appliance or licensed solution may call."),
		"validators" => array(
			array("ValRequired"),
			array("ValNumber","min" => 1,"max" => 10)),
		"control" => array("SelectMenu","values"=>array_combine(range(1,10),range(1,10))),
		"helpstep" => $helpstepnum
	);

}
$buttons = array(submit_button(_L("Done"),"submit","accept"),
				icon_button(_L("Cancel"),"cross",null,"settings.php"));

$form = new Form("jobsettings", $formdata, $helpsteps, $buttons);
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

		if (isset($postdata['callerid']))
			setSystemSetting('callerid', Phone::parse($postdata['callerid']));

		setSystemSetting('autoreport_replyemail', $postdata['autoreportreplyemail']);
		setSystemSetting('autoreport_replyname', $postdata['autoreportreplyname']);

		if($IS_COMMSUITE || getSystemSetting('_dmmethod', 'asp') != 'asp'){
			setSystemSetting('easycallmin', $postdata['easycallmin']);
			setSystemSetting('easycallmax', $postdata['easycallmax']);
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

$PAGE = _L("admin").":"._L("settings");
$TITLE = _L('Systemwide Job Settings');

require_once("nav.inc.php");

?>
<script>
<? if ($datachange) { ?>
	alert("<?=_L("The data on this form has changed. You're changes cannot be saved.")?>")";
	window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';
<? } ?>
</script>
<?

startWindow(_L("Settings"));
echo $form->render();
endWindow();
require_once("navbottom.inc.php");
?>
