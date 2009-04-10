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

if (isset($_GET['delete'])) {
	$id = 0 + $_GET['delete'];
	QuickUpdate("update fieldmap set options = replace(options, ',subscribe', '') where id=?", false, array($id));
	redirect();
}
// else TODO error handling

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();


$data = DBFindMany("FieldMap","from fieldmap where options like '%subscribe%'");

$titles = array(	"name" => "Field Definition",
					"valtype" => "Value Type",
					"values" => "Values",
					"Actions" => "Actions"
					);

$addfields = QuickQueryList("select id, name from fieldmap where options not like '%subscribe%' and options not like '%staff%' and (options like '%text%' or options like '%multisearch%')", true);

$formdata = array(
    "addfield" => array(
        "label" => "Field Definition:",
        "value" => "",
        "validators" => array(    
            array("ValRequired")
        ),
        "control" => array("SelectMenu","values" => $addfields),
        "helpstep" => 1
    )
);

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"Select a field to add"
);

$buttons = array(submit_button("Add","submit","tick"));
                
$form = new Form("addsubscriberfieldform",$formdata,$helpsteps,$buttons);
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
        
		$id = $postdata['addfield'][0];
		
        $query = "update fieldmap set options = concat(options, ',subscribe,dynamic') where id=?";
        QuickUpdate($query, false, array($id));


        if ($ajax)
            $form->sendTo("subscriberfieldvalue.php?id=".$id);
        else
            redirect("subscriberfieldvalue.php?id=".$id);
    }
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($obj, $name) {
	global $firstnameField, $lastnameField;
	
	// first and last name fields have no actions
	if ($obj->fieldnum == $firstnameField ||
		$obj->fieldnum == $lastnameField)
			return '';
			
	return action_links (
		action_link("Edit", "pencil", "subscriberfieldvalue.php?id=$obj->id"),
		action_link("Delete", "cross", "subscribersettings.php?delete=$obj->id", "return confirmDelete();")
	);
}

function fmt_valtype ($obj, $name) {
	if ($obj->isOptionEnabled('static'))
		return "Static";
	else
		return "Dynamic";
}

function fmt_values ($obj, $name) {
	if ($obj->isOptionEnabled('static')) {
		$values = QuickQueryList("select value from persondatavalues where fieldnum=? and editlock=1", false, false, array($obj->fieldnum));
		if (count($values) == 0)
			return "";
		$valcsv = implode(",", $values);
		if (strlen($valcsv) > 25)
			return substr($valcsv, 0, 25) . "...";
		else
			return $valcsv;
	} else
		return "";
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Subscriber Self-Signup Settings';

include_once("nav.inc.php");

startWindow('Subscriber Field Values', null, true);
showObjects($data, $titles, array("valtype" => "fmt_valtype", "values" => "fmt_values", "Actions" => "fmt_actions"), false, true);
endWindow();

startWindow('Add Field', null, true);
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>