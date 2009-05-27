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


class SubscriberWiz_choosefield extends WizStep {
	function getForm($postdata, $curstep) {

		$formdata = array();
	
		if (isset($_GET['id'])) { // edit mode
			$id = 0 + $_GET['id'];
			
    		$formdata["hiddenid"] = array(
        		"label" => "Hidden",
        		"value" => $id,
        		"validators" => array(    
        		),
        		"control" => array("HiddenField"),
        		"helpstep" => 1
    		);
    		$formhtml = "<div>Editing Field ID ".$id."</div>";
    		$formdata["formhtml"] = array(
        		"label" => "Field",
        		"value" => "",
        		"validators" => array(    
        		),
        		"control" => array("FormHtml", "html"=>$formhtml),
        		"helpstep" => 1
    		);
			
			$helpsteps = array (
				"Welcome",
				"blah blah"
			);
		} else { // add mode
			$addfields = QuickQueryList("select id, name from fieldmap where options not like '%subscribe%' and options not like '%language%' and options not like '%staff%' and (options like '%text%' or options like '%multisearch%') order by fieldnum", true);
	
    		$formdata["addfield"] = array(
        		"label" => "Field",
        		"value" => "",
        		"validators" => array(    
	            	array("ValRequired")
        		),
        		"control" => array("SelectMenu","values" => $addfields),
        		"helpstep" => 1
    		);
		
			$helpsteps = array (
				"Welcome",
				"blah blah"
			);
		}
		
		return new Form("choosefield", $formdata, $helpsteps);
	}
}

class SubscriberWiz_choosevaltype extends WizStep {
	function getForm($postdata, $curstep) {
	
		$value = "";
		if (isset($postdata['/choosefield']['hiddenid'])) {
			$id = $postdata['/choosefield']['hiddenid'];
			$fieldmap = new FieldMap($id);
			if ($fieldmap->isOptionEnabled('static')) {
				$value = 1;
			} else {
				$value = 2;
			}
		} else {
			$id = $postdata['/choosefield']['addfield'];
		}
	
		// text or multitext
		$options = QuickQuery("select options from fieldmap where id=?", null, array($id));
		$multi = strpos($options, "multisearch");
		// multitext values
		$values = array(1 => "Static List defined by Admin", 2 => "Dynamic List built by import data");
		if ($multi === false) {
			// textfield values
			$values = array(1 => "Static defined by Admin", 2 => "Dynamic supplied by Subscriber");
		}
		
		$formdata = array();

		$formdata["valtype"] = array(
        	"label" => "Value Type",
        	"value" => $value,
        	"validators" => array(    
	            array("ValRequired")
        	),
        	"control" => array("RadioButton","values" => $values),
        	"helpstep" => 1
		);
		
		$helpsteps = array (
			"Welcome",
			"Static Text field is not displayed to subscriber.<br>" .
			"Dynamic Text field is for subscriber to enter their name.<br>" .
			"Static List field is a list defined here.<br>" .
			"Dynamic List field is list values from imports.<br>"
		);
		
		return new Form("choosevaltype", $formdata, $helpsteps);
	}
}


class SubscriberWiz_staticvalues extends WizStep {
	function getForm($postdata, $curstep) {
		$formdata = array();
		
		$value = "";
		if (isset($postdata['/choosefield']['hiddenid'])) {
			$id = $postdata['/choosefield']['hiddenid'];
			$fieldmap = new FieldMap($id);
			$staticvalues = QuickQueryList("select value from persondatavalues where fieldnum=? and editlock=1", false, false, array($fieldmap->fieldnum));
			$value = implode("\n", $staticvalues);
		} else {
			$id = $postdata['/choosefield']['addfield'];
		}

		// text or multitext
		$options = QuickQuery("select options from fieldmap where id=?", null, array($id));
		$multi = strpos($options, "multisearch");
		if ($multi === false) {
			// text field
    		$formdata["values"] = array(
        		"label" => "Static Value",
        		"value" => $value,
        		"validators" => array(
		            array("ValLength","max" => 255)
        		),
        		"control" => array("TextField","maxlength" => 255),
        		"helpstep" => 1
    		);
		} else {
			// text area
    		$formdata["values"] = array(
        		"label" => "Static Value(s)",
        		"value" => $value,
        		"validators" => array(
        			// TODO each line item separated by comma, max 255
        		),
        		"control" => array("TextArea","rows" => 10),
        		"helpstep" => 1
    		);
		}

		$helpsteps = array (
			"Welcome",
			"blah, blah"
		);
		
		return new Form("staticvalues", $formdata, $helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/choosevaltype']))
			return ($postdata['/choosevaltype']['valtype'] == "1");
		return true;
	}
}

class SubscriberWiz_confirm extends WizStep {
	function getForm($postdata, $curstep) {
		$formhtml = "<div>Thanks your field will be added.</div>";
		
		$formdata = array();

    	$formdata["review"] = array(
        	"label" => "Confirmation",
        	"control" => array("FormHtml","html" => $formhtml),
			"helpstep" => 1
		);
		
		$helpsteps = array (
			"Welcome",
			"blah, blah"
		);
		
		return new Form("confirm", $formdata, $helpsteps);
	}
}

class SubscriberWiz_finish extends WizStep {
	function getForm($postdata, $curstep) {
		$formhtml = "<div>Thanks your field has been added.</div>";
		
		$formdata = array();

    	$formdata["review"] = array(
        	"label" => "Confirmation",
        	"control" => array("FormHtml","html" => $formhtml),
			"helpstep" => 1
		);
		
		$helpsteps = array (
			"Welcome",
			"blah, blah"
		);
		
		return new Form("finish", $formdata, $helpsteps);
	}
}


$wizdata = array(
	"choosefield" => new SubscriberWiz_choosefield(_L("Select Field")),
	"choosevaltype" => new SubscriberWiz_choosevaltype(_L("Select Value Type")),
	"staticvalues" => new SubscriberWiz_staticvalues(_L("Enter Static Values")),
	"confirm" => new SubscriberWiz_confirm(_L("Confirm")),
	"finish" => new SubscriberWiz_finish(_L("Final"))
	);

$wizard = new Wizard("subscriberwiz", $wizdata);
$wizard->handleRequest();

if ($wizard->isDone()) {
	$postdata = $_SESSION['subscriberwiz']['data'];

	if (isset($postdata['/choosefield']['hiddenid'])) {
		$id = $postdata['/choosefield']['hiddenid'];
	} else {
		$id = $postdata['/choosefield']['addfield'];
	}
	$fieldmap = new FieldMap($id);
	
	$fieldmap->addOption("subscribe");
	
	$fieldmap->removeOption("static");
	$fieldmap->removeOption("dynamic");
	if ($postdata['/choosevaltype']['valtype'] == 1)
		$fieldmap->addOption("static");
	else
		$fieldmap->addOption("dynamic");
        	
	$fieldmap->update();

	QuickUpdate("delete from persondatavalues where fieldnum=? and editlock=1", false, array($fieldmap->fieldnum));

	$insertstmt = "insert into persondatavalues (fieldnum, value, refcount, editlock) values ";
	$insertvalues = array();
	
	if (isset($postdata['/staticvalues'])) {
		$datavalues = explode("\n", $postdata['/staticvalues']['values']);
		foreach ($datavalues as $value) {
			if (strlen($value) == 0) continue; // skip blank lines
			$insertstmt .= "(?, ?, 0, 1),";
			$insertvalues[] = $fieldmap->fieldnum;
			$insertvalues[] = $value;
		}
		if (count($insertvalues)) {
			$insertstmt = substr($insertstmt, 0, strlen($insertstmt)-1); // strip trailing comma
			QuickUpdate($insertstmt, false, $insertvalues);
		} 
	}
	
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
$editname = isset($fieldmap) ? ' : ' . $fieldmap->name : '';
$TITLE = 'Subscriber Field Value' . $editname;

include_once("nav.inc.php");

startWindow('Field Value', null, true);

echo $wizard->render();

endWindow();

include_once("navbottom.inc.php");
?>