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


$formdata = array();
	
if (isset($_GET['id'])) { // edit mode
	$id = 0 + $_GET['id'];
	if ($id == 0) {
		$fieldmap = FieldMap::getSubscriberOrganizationFieldMap();
	} else {
		$fieldmap = new FieldMap($id);
	}
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

// if organization
if ("oid" == $fieldmap->fieldnum) {
	$organizations = QuickQueryList("select id, orgkey from organization where not deleted", true);
	$formdata["values"] = array(
   		"label" => _L("Field Value(s)"),
   		"fieldhelp" => _L('Select the organizations that subscribers may belong.'),
   		"value" => $staticvalues,
   		"validators" => array(
			array("ValRequired"),
            array("ValInArray", 'values'=>array_keys($organizations))
      		),
      	"control" => array("MultiCheckbox","values" => $organizations),
      	"helpstep" => 1
	);

} else {
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

		// special case, organization field
		if ("oid" == $fieldmap->fieldnum) {
			// always clear old values, rebuild if 'static'
			$query = "delete from persondatavalues where fieldnum='oid'";
			QuickUpdate($query);

			$datavalues = $postdata['values'];
			$numvalues = count($datavalues);
			if ($numvalues > 0) {
				$query = "insert into persondatavalues (fieldnum, value, editlock) values " . repeatWithSeparator("('oid', ?, 1)", ",", $numvalues);
				QuickUpdate($query, false, $datavalues);

				// find all subscriber persons
				$query = "select id from person where importid is null and type='system'";
				$pids = QuickQueryList($query);
				
				// remove any obsolete values from personassocation
				$query = "delete from personassociation where personid in (". repeatWithSeparator("?", ",", count($pids)) .") and type='organization' and organizationid not in (". repeatWithSeparator("?", ",", $numvalues) .")";
				QuickUpdate($query, false, array_merge($pids, $datavalues));
				
				// if single value, update all subscriber's personassociation to the one organization
				if ($numvalues == 1) {
					// remove any associations they may have had
					$query = "delete from personassociation where personid in (". repeatWithSeparator("?", ",", count($pids)) .") and type='organization'";
					QuickUpdate($query, false, array_merge($pids, $pids));
					
					// insert the single association
					$oid = $datavalues[0];
					$query = "insert into personassociation (personid, type, organizationid) values " . repeatWithSeparator("(?, 'organization', ".$oid.")", ",", count($pids));
					QuickUpdate($query, false, $pids);
				}
			}
			
		} else {
		// if static text field
		if ($fieldmap->isOptionEnabled('text')) {
			$value = trimStaticValue($postdata['values']);
			QuickUpdate("update person set ".$fieldmap->fieldnum."=? where importid is null and type='system'", false, array($value));
		}
		// get values and trim
		$datavalues = explode("\n", $postdata['values']);
		for ($i=0; $i<count($datavalues); $i++) {
			$datavalues[$i] = trimStaticValue($datavalues[$i]);
		}
		$numvalues = count($datavalues);
		// if static list Ffield
		if ($fieldmap->isOptionEnabled('multisearch') && strpos($fieldmap->fieldnum, 'f') === 0) {
			// single value, update all person subscribers with new value
			if ($numvalues == 1) {
				QuickUpdate("update person set ".$fieldmap->fieldnum."=? where importid is null and type='system'", false, array($datavalues[0]));
			}
			// no values, update all person subscribers with NULL
			if ($numvalues == 0) {
				QuickUpdate("update person set ".$fieldmap->fieldnum."=NULL where importid is null and type='system'");
			}
			// new values, update all person subscribers with invalid value to NULL
			if ($numvalues > 1) {
				$args = repeatWithSeparator("?", ",", $numvalues);
				QuickUpdate("update person set ".$fieldmap->fieldnum."=NULL where importid is null and type='system' and ".$fieldmap->fieldnum." not in (".$args.")", false, $datavalues);
			}
		}
		// Gfield, cleanup old values
		if (strpos($fieldmap->fieldnum, 'g') === 0) {
			$fn = substr($fieldmap->fieldnum, 1);
			if ($numvalues == 0) {
				QuickUpdate("delete from groupdata where fieldnum=? and importid=0", false, array($fn));
			} else {
				$args = repeatWithSeparator("?", ",", $numvalues);
				QuickUpdate("delete from groupdata where fieldnum=".$fn." and importid=0 and value not in (".$args.")", false, $datavalues);
			}
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
