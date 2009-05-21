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
include_once("inc/text.inc.php");
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/FieldMap.obj.php");

require_once("obj/Wizard.obj.php");

require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata') && !getSystemSetting("_hasselfsignup", false)) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$valtext = "";
if (isset($_GET['id'])) {
	$id = 0 + $_GET['id'];
	$fieldmap = new FieldMap($id);
	if ($fieldmap->isOptionEnabled('static')) {
		$value = 1;
		$values = QuickQueryList("select value from persondatavalues where fieldnum=? and editlock=1", false, false, array($fieldmap->fieldnum));
		$valtext = implode("\n", $values);
		
	} else if ($fieldmap->isOptionEnabled('dynamic')) {
		$value = 2;
	} else
		$value = 0;
}
// else TODO error handling


class SubscriberWiz_whattype extends WizStep {
	function getForm($postdata, $curstep) {
		global $id, $value;

		if (isset($id)) {
			error_log('global id is set to '.$id);
		} else {
			$id = $postdata['/whattype']['id'];
			error_log('postdata id already set to '.$id);
		}
		
		$formdata = array();

		$formdata["id"] = array(
        	"label" => "hidden",
        	"value" => $id,
        	"validators" => array(    
        	),
        	"control" => array("HiddenField"),
        	"helpstep" => 1
		);
		
		$formdata["valtype"] = array(
        	"label" => "Value Type",
        	"value" => $value,
        	"validators" => array(    
	            array("ValRequired")
        	),
        	"control" => array("RadioButton","values" => array(1 => "Static", 2 => "Dynamic")),
        	"helpstep" => 1
		);
		
		$helpsteps = array (
			"Welcome",
			"blah, blah"
		);
		
		return new Form("whattype", $formdata, $helpsteps);
	}
}

$wizdata = array(
	"whattype" => new SubscriberWiz_whattype(_L("Select Type"))
	);

$wizard = new Wizard("subscriberwiz", $wizdata);
$wizard->handleRequest();

if ($wizard->isDone()) {
	$postdata = $_SESSION['subscriberwiz']['data'];

	$id = $postdata['/whattype']['id'];
	$fieldmap = new FieldMap($id);
	
error_log("DONE this is the field ".$fieldmap->name);

	$fieldmap->addOption("subscribe");
	
	$fieldmap->removeOption("static");
	$fieldmap->removeOption("dynamic");
	if ($postdata['/whattype']['valtype'] == 1)
		$fieldmap->addOption("static");
	else
		$fieldmap->addOption("dynamic");
        	
	$fieldmap->update();

	/*        
	QuickUpdate("delete from persondatavalues where fieldnum=? and editlock=1", false, array($fieldmap->fieldnum));
        
	$datavalues = explode("\n", $postdata['values']);
	foreach ($datavalues as $value) {
		QuickUpdate("insert into persondatavalues (fieldnum, value, refcount, editlock) values (?, ?, 0, 1)", false, array($fieldmap->fieldnum, $value));
	} 
	*/
	
	$_SESSION['subscriberwiz'] = null; // clear out the old data
	redirect("subscribersettings.php");
}


/*
$formdata = array(
    "valtype" => array(
        "label" => "Value Type:",
        "value" => $value,
        "validators" => array(    
            array("ValRequired")
        ),
        "control" => array("RadioButton","values" => array(1 => "Static", 2 => "Dynamic")),
        "helpstep" => 1
    ),
    "values" => array(
        "label" => "Static Value(s):",
        "value" => $valtext,
        "validators" => array(
            array("ValLength","max" => 255)
        ),
        "control" => array("TextArea","rows" => 10),
        "helpstep" => 2
    )
);
*/
/*
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
    $ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response    
    
    if ($form->checkForDataChange()) {
        $datachange = true;
    } else if (($errors = $form->validate()) === false) { //checks all of the items in this form
        $postdata = $form->getData(); //gets assoc array of all values {name:value,...}
        
        $fieldmap->removeOption("static");
        $fieldmap->removeOption("dynamic");

        if ($postdata['valtype'] == 1)
        	$fieldmap->addOption("static");
        else
        	$fieldmap->addOption("dynamic");
        	
        $fieldmap->update();
        
        QuickUpdate("delete from persondatavalues where fieldnum=? and editlock=1", false, array($fieldmap->fieldnum));
        
        $datavalues = explode("\n", $postdata['values']);
        foreach ($datavalues as $value) {
			QuickUpdate("insert into persondatavalues (fieldnum, value, refcount, editlock) values (?, ?, 0, 1)", false, array($fieldmap->fieldnum, $value));
        } 
        
        if ($ajax)
            $form->sendTo("subscribersettings.php");
        else
            redirect("subscribersettings.php");
    }
}
*/



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Subscriber Field Value : ' . $fieldmap->name;

include_once("nav.inc.php");

startWindow('Field Value', null, true);

echo $wizard->render();

endWindow();

include_once("navbottom.inc.php");
?>