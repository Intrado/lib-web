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

class FinishSubscriberFieldWizard extends WizFinish {
	
	function finish ($postdata) {
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
	}
	
	function getFinishPage ($postdata) {
		return "<h1>Thanks your field has been added.</h1>" . icon_button("Return to Settings", "fugue/arrow_180", "", "subscriberfields.php");
	}
}


$wizdata = array(
	"choosefield" => new SubscriberWiz_choosefield(_L("Select Field")),
	"choosevaltype" => new SubscriberWiz_choosevaltype(_L("Select Value Type")),
	"staticvalues" => new SubscriberWiz_staticvalues(_L("Enter Static Values"))
	);

$wizard = new Wizard("subscriberwiz", $wizdata, new FinishSubscriberFieldWizard("Finish"));
$wizard->handleRequest();


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