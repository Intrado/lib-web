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
		
	} else
		$value = 2;
}
// else TODO error handling

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

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"Static textfield, defined list of values, or Dynamic subscriber entered or imported values",
	"static values"
);


$buttons = array(submit_button("Submit","submit","tick"),
                icon_button("Cancel","cross",null,"subscribersettings.php"));
                
$form = new Form("subscriberfieldvalueform",$formdata,$helpsteps,$buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;

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



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Subscriber Field Value : ' . $fieldmap->name;

include_once("nav.inc.php");

startWindow('Field Value', null, true);

echo $form->render();

endWindow();

include_once("navbottom.inc.php");
?>