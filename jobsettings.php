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
$helpsteps = array(_L("Security adjustments are made on this page."));

$helpstepnum = 1;

$formdata["retry"] = array(
	"label" => _L("Retry Setting"),
	"value" => getSystemSetting('retry'),
	"validators" => array(),
	"control" => array("SelectMenu", "values"=>array_combine(array(5,10,15,30,60,90,120),array(5,10,15,30,60,90,120))),
	"helpstep" => $helpstepnum
);
$helpsteps[$helpstepnum++] = _L("The Retry Setting specifies the minimum number of minutes the system must wait prior to retrying any busy or unanswered phone number.");

// TODO: if (getSystemSetting('_hascallback', false)) {	echo Phone::format(getSystemSetting('callerid'));
if (getSystemSetting('_hascallback', false)) {
	$formdata["callerid"] = array(
		"label" => _L("Default Caller ID Number"),
		"control" => array("FormHtml","html"=>Phone::format(getSystemSetting('callerid'))),
		"helpstep" => $helpstepnum
	);
} else {	
		$formdata["callerid"] = array(
		"label" => _L("Default Caller ID Number"),
		"value" => Phone::format(getSystemSetting('callerid')),
		"validators" => array(
	            array("ValLength","min" => 2,"max" => 20),
	            array("ValPhone")),
		"control" => array("TextField","maxlength" => 20),
		"helpstep" => $helpstepnum
	);
}
$helpsteps[$helpstepnum++] = _L("This specifies the default Caller ID to use for new Jobs. If a user has access rights, they may override this with a new setting.");

$formdata["autoreportreplyemail"] = array(
	"label" => _L("Autoreport Email Address"),
	"value" => getSystemSetting('autoreport_replyemail'),
	"validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 3,"max" => 255),
            array("ValEmail")),
	"control" => array("TextField","maxlength" => 255),
	"helpstep" => $helpstepnum
);

$formdata["autoreportreplyname"] = array(
	"label" => _L("Autoreport Email Name"),
	"value" => getSystemSetting('autoreport_replyname'),
	"validators" => array(
            array("ValRequired"),
            array("ValLength","min" => 1,"max" => 100)),
	"control" => array("TextField","maxlength" => 100),
	"helpstep" => $helpstepnum
);
$helpsteps[$helpstepnum++] = _L("Enter the reply-to email address and name for your auto reports.");

if($IS_COMMSUITE || getSystemSetting('_dmmethod', 'asp') != 'asp'){
				
	$formdata["easycallmin"] = array(
		"label" => _L("Minimum Extensions Length"),
		"value" => getSystemSetting('easycallmin',10),
		"validators" => array(
	            array("ValRequired"),
	            array("ValNumber","min" => 1,"max" => 10)),
		"control" => array("SelectMenu","values"=>array_combine(range(1,10),range(1,10))),
		"helpstep" => $helpstepnum
	);
	
	$formdata["easycallmax"] = array(
		"label" => _L("Maximum Estensions Length"),
		"value" => getSystemSetting('easycallmax',10),
		"validators" => array(
	            array("ValRequired"),
	            array("ValNumber","min" => 1,"max" => 10)),
		"control" => array("SelectMenu","values"=>array_combine(range(1,10),range(1,10))),
		"helpstep" => $helpstepnum
	);
	$helpsteps[$helpstepnum++] = _L("Indicates the maximum/minimum number of digits that must be entered when using the EasyCall or Call Me to Record features.");

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

		setSystemSetting('retry', $postdata['retry']);
		if (isset($postdata['callerid']))
			setSystemSetting('callerid', $postdata['callerid']);

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

echo dataChangeAlert($datachange, $_SERVER['REQUEST_URI']);

require_once("nav.inc.php");
startWindow(_L("Settings"));
echo $form->render();
endWindow();
require_once("navbottom.inc.php");
?>