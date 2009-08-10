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
include_once("obj/FieldMap.obj.php");

require_once("obj/Wizard.obj.php");

require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata') || !getSystemSetting("_hasselfsignup", false)) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$formdata = array();
	
if (isset($_GET['id'])) { // edit mode
	$id = 0 + $_GET['id'];
	$fieldmap = new FieldMap($id);
			
   	$formhtml = "<div>".$fieldmap->name."</div>";
   	$formdata["formhtml"] = array(
   		"label" => _L("Field Name"),
   		"value" => "",
   		"validators" => array(    
   		),
   		"control" => array("FormHtml", "html"=>$formhtml),
   		"helpstep" => 1
   	);
}

$staticvalues = QuickQueryList("select value from persondatavalues where fieldnum=? and editlock=1", false, false, array($fieldmap->fieldnum));
$value = implode("\n", $staticvalues);

// text or multitext
if ($fieldmap->isOptionEnabled("multisearch")) {
	// text area
	$formdata["values"] = array(
   		"label" => _L("Field Value(s)"),
   		"value" => $value,
   		"validators" => array(
   			// TODO each line item separated by comma, max 255
   		),
   		"control" => array("TextArea","rows" => 10),
   		"helpstep" => 1
	);
} else {
	// text field
	$formdata["values"] = array(
   		"label" => _L("Field Value"),
   		"value" => $value,
   		"validators" => array(
            array("ValLength","max" => 255)
   		),
   		"control" => array("TextField","maxlength" => 255),
   		"helpstep" => 1
	);
}

$buttons = array(submit_button("Done","submit","accept"),
                icon_button("Cancel","cross",null,"subscriberfields.php"));
                
$form = new Form("subscriberfieldedit",$formdata,null,$buttons);
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

			// if static text field
			if ($fieldmap->isOptionEnabled('text')) {
				$value = trim($postdata['values']);
				QuickUpdate("update person set ".$fieldmap->fieldnum."=? where importid is null and type='system'", false, array($value));
			}

		QuickUpdate("delete from persondatavalues where fieldnum=? and editlock=1", false, array($fieldmap->fieldnum));

		$insertstmt = "insert into persondatavalues (fieldnum, value, refcount, editlock) values ";
		$insertvalues = array();
	
		$datavalues = explode("\n", $postdata['values']);
		foreach ($datavalues as $value) {
			$value = trim($value);
			if (strlen($value) == 0) continue; // skip blank lines
			$insertstmt .= "(?, ?, 0, 1),";
			$insertvalues[] = $fieldmap->fieldnum;
			$insertvalues[] = $value;
		}
		if (count($insertvalues)) {
			$insertstmt = substr($insertstmt, 0, strlen($insertstmt)-1); // strip trailing comma
			QuickUpdate($insertstmt, false, $insertvalues);
		} 
				
        if ($ajax)
            $form->sendTo("subscriberfields.php");
        else
            redirect("subscriberfields.php");
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