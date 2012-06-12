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
// Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class ValRequireApprovedCallerID extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		if(QuickQuery("select count(callerid) from authorizedcallerid")+0 == 0)
			return "List of Approved CallerIDs must contain at least one number";
		return true;
	}
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$formdata = array();
$helpstepnum = 0;

$helpsteps[$helpstepnum++] = _L("The default Caller ID number will be used in the following situations: <br><ul><li> There are no other Caller ID options. <li> The user has no selected a Caller ID from the optional Caller IDs. <li> The user's Access Profile allows him to overwrite the Caller ID.</ul>");
if (getSystemSetting('_hascallback', false)) {
	$formdata["callerid"] = array(
		"label" => _L("Default Caller ID Number"),
		"fieldhelp" => _L("The default Caller ID number is used in certain situations. Please click on the Guide help for more information."),
		"control" => array("FormHtml","html"=>Phone::format(getDefaultCallerID())),
		"helpstep" => $helpstepnum
	);
} else {

	$formdata["callerid"] = array(
		"label" => _L("Default Caller ID Number"),
		"fieldhelp" => _L("The default Caller ID number is used in certain situations. Please click on the Guide help for more information."),
		"value" => Phone::format(getSystemSetting('callerid')),
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 2,"max" => 20),
			array("ValPhone")),
		"control" => array("TextField","maxlength" => 20),
		"helpstep" => $helpstepnum
	);
		$helpsteps[$helpstepnum++] = _L("Select this option to restrict users to the following list of Caller ID numbers. If you do not check this box, but enter numbers below, users will not see the approved Caller ID numbers.<br><br> <b>Note:</b><i> Users with Access Profiles that allow them to override their Caller ID will still have the option to do so.</i>");
	$formdata["requireapprovedcallerid"] = array(
		"label" => _L('Only Allow Approved Caller&nbsp;ID'),
		"fieldhelp" => _L('Restricts users to Caller ID numbers entered in the following field. Please click on the Guide help for more information.'),
		"value" => getSystemSetting("requireapprovedcallerid",false),
		"validators" => array(
			array("ValRequireApprovedCallerID")
		),
		"control" => array("CheckBox"),
		"helpstep" => $helpstepnum
	);
	$helpsteps[$helpstepnum++] = _L('Enter any Caller ID numbers users may choose for their %s. Be sure to check the "Only allow approved Caller ID" checkbox if you enter numbers here.<br><br><b>Note:</b> <i>Users with Access Profiles that allow them to override their Caller ID will still have the option to do so.</i>',getJobsTitle());
	$approvedcallerids = QuickQueryList("select callerid from authorizedcallerid");
	$formattedcallerids = array();
	foreach($approvedcallerids as $callerid) {
		$formattedcallerids[] = escapehtml(Phone::format($callerid));
	}
		
	$formdata["approvedcallerids"] = array(
		"label" => _L('Approved Caller&nbsp;IDs'),
		"fieldhelp" => _L('You may restrict users to Caller ID numbers entered here. Please click on the Guide help for more information.'),
		"control" => array("FormHtml","html"=>'<div style="border:1px solid #CCCCCC;width:140px;height:150px;overflow:auto;float:left;">' . implode("<br />", $formattedcallerids) . '</div>' . icon_button("Edit Approved Caller IDs", "pencil",false,"callerid.php")),
		"helpstep" => $helpstepnum
	);
	
}

if(getSystemSetting('_dmmethod', 'asp') != 'asp'){

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

		if (!getSystemSetting('_hascallback', false)) {
			setSystemSetting('callerid', Phone::parse($postdata['callerid']));
			
			setSystemSetting('requireapprovedcallerid', $postdata['requireapprovedcallerid']==="true"?1:0);
			if ($postdata['requireapprovedcallerid']==="true") {
				QuickUpdate("insert ignore into authorizedusercallerid (userid,callerid) (select userid,value from usersetting where name='callerid')");
			}
		}
		if(getSystemSetting('_dmmethod', 'asp') != 'asp'){
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
<script type="text/javascript">
<? Validator::load_validators(array("ValRequireApprovedCallerID")); ?>
<? if ($datachange) { ?>
	alert("<?=_L("The data on this form has changed. Your changes cannot be saved.")?>")";
	window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';
<? } ?>
</script>
<?

startWindow(_L("Settings"));
echo $form->render();
endWindow();
require_once("navbottom.inc.php");
?>
