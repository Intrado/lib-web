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

// FUNCTIONS

function trimStaticValue($value) {
	return substr(trim($value), 0, 255);
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


class SubscriberWiz_choosefield extends WizStep {
	function getForm($postdata, $curstep) {

		$formdata = array();
	
		$addfields = array("" => "-- Choose a Field --");
		$tmp = QuickQueryList("select id, name from fieldmap where fieldnum not like '%c%' and options not like '%subscribe%' and options not like '%language%' and options not like '%staff%' and (options like '%text%' or options like '%multisearch%') order by fieldnum", true);
		foreach ($tmp as $k => $v)
			$addfields[$k] = $v;
	
   		$formdata["addfield"] = array(
       		"label" => _L("Available Fields"),
       		"fieldhelp" => _L('Use the menu to select an additional field new subscribers will need to use when signing up. Additional fields can be created using Field Definitions on the Admin>Settings page. They must be of the list or text type.'),
       		"value" => "",
       		"validators" => array(    
            	array("ValRequired")
       		),
       		"control" => array("SelectMenu","values" => $addfields),
       		"helpstep" => 1
   		);
		
		return new Form("choosefield", $formdata, null);
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
		$values = array(1 => _L("Manually Entered List of Values"), 2 => _L("Dynamically Generated by Imported Data"));
		if ($multi === false) {
			// textfield values
			$values = array(1 => _L("Set by Admin"), 2 => _L("Set by Subscriber"));
		}
		
		$formdata = array();

		$formdata["valtype"] = array(
        	"label" => _L("Data Source"),
        	"fieldhelp" => _L('If the field type is a list, a Manually Entered List of Values contains values which you can enter in the following screen. Dynamically generated values are determined by imported data.<br><br>If the field is a text type, you may set the value in the following screen by choosing Set by Admin, or select Set by Subscriber, to allow new subscribers to enter any value.'),
        	"value" => $value,
        	"validators" => array(    
	            array("ValRequired")
        	),
        	"control" => array("RadioButton","values" => $values),
        	"helpstep" => 1
		);
		
		return new Form("choosevaltype", $formdata, null);
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
        		"label" => _L("Field Value"),
        		"fieldhelp" => _L('Enter a value which will be associated with all subscribers.'),
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
        		"label" => _L("Field Value(s)"),
        		"fieldhelp" => _L('Enter a list of values, with a line return after each entry. New subscribers will have these options to choose from after they sign up.'),
        		"value" => $value,
        		"validators" => array(
        			// TODO each line item separated by comma, max 255
        		),
        		"control" => array("TextArea","rows" => 10),
        		"helpstep" => 1
    		);
		}

		return new Form("staticvalues", $formdata, null);
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

		if (isset($postdata['/staticvalues'])) {
			// if static text field
			if ($fieldmap->isOptionEnabled('text')) {
				$value = trimStaticValue($postdata['/staticvalues']['values']);
				QuickUpdate("update person set ".$fieldmap->fieldnum."=? where importid is null and type='system'", false, array($value));
			}
			$datavalues = explode("\n", $postdata['/staticvalues']['values']);
			// if static list field, with single value
			if ($fieldmap->isOptionEnabled('multisearch') && count($datavalues) === 1 && strpos($fieldmap->fieldnum, 'f') === 0) {
				$value = trimStaticValue($datavalues[0]);
				QuickUpdate("update person set ".$fieldmap->fieldnum."=? where importid is null and type='system'", false, array($value));
			}

			// NOTE subscriber static values and import person data values may share fields
			// example, static values grade 8, 9, 10.  import values grade 10, 11, 12.  careful not to duplicate grade 10.
		
			// clear any non-imported, static values (will be recreated in next step)
			QuickUpdate("delete from persondatavalues where fieldnum=? and refcount=0 and editlock=1", false, array($fieldmap->fieldnum));

			// clear any previous static values, will be recreated in next step
			QuickUpdate("update persondatavalues set editlock=0 where fieldnum=?", false, array($fieldmap->fieldnum));
		
			// find remaining values
			$importfieldvalues = QuickQueryList("select value from persondatavalues where fieldnum=?", false, false, array($fieldmap->fieldnum));

			// create new static values (careful to update any duplicates from import data)
			$insertstmt = "insert into persondatavalues (fieldnum, value, refcount, editlock) values ";
			$insertvalues = array();
			foreach ($datavalues as $value) {
				$value = trimStaticValue($value);
				if (strlen($value) == 0) continue; // skip blank lines
				if (in_array($value, $importfieldvalues)) {
					QuickUpdate("update persondatavalues set editlock=1 where fieldnum=? and value=?", false, array($fieldmap->fieldnum, $value));
				} else {
					$insertstmt .= "(?, ?, 0, 1),";
					$insertvalues[] = $fieldmap->fieldnum;
					$insertvalues[] = $value;
				}
			}
			if (count($insertvalues)) {
				$insertstmt = substr($insertstmt, 0, strlen($insertstmt)-1); // strip trailing comma
				QuickUpdate($insertstmt, false, $insertvalues);
			} 
		}
	}
	
	function getFinishPage ($postdata) {
		return "<h1>Success. Your new field has been added.</h1>";
	}
}


$wizdata = array(
	"choosefield" => new SubscriberWiz_choosefield(_L("Select Field")),
	"choosevaltype" => new SubscriberWiz_choosevaltype(_L("Define Data Source")),
	"staticvalues" => new SubscriberWiz_staticvalues(_L("Enter Field Values"))
	);

$wizard = new Wizard("subscriberwiz", $wizdata, new FinishSubscriberFieldWizard(_L("Finish")));
$wizard->doneurl = "subscriberfields.php";
$wizard->handleRequest();


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Subscriber Field Configuration';

include_once("nav.inc.php");

startWindow($wizard->getStepData()->title);

echo $wizard->render();

endWindow();

include_once("navbottom.inc.php");
?>
