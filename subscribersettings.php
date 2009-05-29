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
// TODO show options, but show/hide metadata
if (!$USER->authorize('metadata') && !getSystemSetting("_hasselfsignup", false)) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$id = 0 + $_GET['delete'];
	
	QuickUpdate("begin");
	
	// get fieldmap obj to remove options
	$fieldmap = new FieldMap($id);
	$fieldmap->removeOption("subscribe");
	$fieldmap->removeOption("static");
	$fieldmap->removeOption("dynamic");
	$fieldmap->update();

	// clear static subscriber values
	QuickUpdate("delete from persondatavalues where fieldnum=? and editlock=1", false, array($fieldmap->fieldnum));
	
	QuickUpdate("commit");
	redirect();
}

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();
$languageField = FieldMap::getLanguageField();

$data = DBFindMany("FieldMap","from fieldmap where options like '%subscribe%'");

$titles = array(	"name" => "Field",
					"valtype" => "Value Type",
					"values" => "Values",
					"Actions" => "Actions"
					);

$addfields = QuickQueryList("select id, name from fieldmap where options not like '%subscribe%' and options not like '%language%' and options not like '%staff%' and (options like '%text%' or options like '%multisearch%') order by fieldnum", true);

$emaildomain = QuickQuery("select value from setting where name='emaildomain'");

$formdata = array();

$formdata["restrictdomain"] = array(
        "label" => _L("Restrict Account Email to Domain"),
        "value" => getSystemSetting("subscriberauthdomain", "0") ? true : false,
        "validators" => array(    
        ),
        "control" => array("CheckBox"),
        "helpstep" => 1
    );
$formdata["domain"] = array(
        "label" => _L("Email Domain"),
        "value" => "",
        "validators" => array(    
        ),
        "control" => array("FormHtml","html"=>"<div>".$emaildomain."</div>"),
        "helpstep" => 1
    );
$formdata["requiresitecode"] = array(
        "label" => _L("Require Site Access Code"),
        "value" => getSystemSetting("subscriberauthcode", "0") ? true : false,
        "validators" => array(    
        ),
        "control" => array("CheckBox"),
        "helpstep" => 1
    );
$formdata["sitecode"] = array(
        "label" => _L("Site Access Code"),
        "value" => getSystemSetting("subscribersitecode", ""),
        "validators" => array(
            array("ValLength","min" => 3,"max" => 255)
        ),
        "control" => array("TextField","maxlength" => 255),
        "helpstep" => 1
    );


$buttons = array(submit_button("Save","submit","tick"),
                icon_button("Cancel","cross",null,"subscribersettings.php"));
                
$form = new Form("subscriberoptions",$formdata,null,$buttons);
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
        
		$postdata['restrictdomain'] ? setSystemSetting("subscriberauthdomain", "1") : setSystemSetting("subscriberauthdomain", "0");
		$postdata['requiresitecode'] ? setSystemSetting("subscriberauthcode", "1") : setSystemSetting("subscriberauthcode", "0");
		setSystemSetting("subscribersitecode", $postdata['sitecode']);
				
        if ($ajax)
            $form->sendTo("subscribersettings.php");
        else
            redirect("subscribersettings.php");
    }
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($obj, $name) {
	global $firstnameField, $lastnameField, $languageField;
	
	// first, last name, language fields have no actions
	if ($obj->fieldnum == $firstnameField ||
		$obj->fieldnum == $lastnameField ||
		$obj->fieldnum == $languageField)
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
	global $languageField;
	
	if ($obj->isOptionEnabled('static')) {
		// TODO special case language
		if ($obj->fieldnum == $languageField) {
			return "English,Spanish,French"; // TODO
		} else {
			$values = QuickQueryList("select value from persondatavalues where fieldnum=? and editlock=1", false, false, array($obj->fieldnum));
			if (count($values) == 0)
				return "";
			$valcsv = implode(",", $values);
			if (strlen($valcsv) > 25)
				return substr($valcsv, 0, 25) . "...";
			else
				return $valcsv;
		}
	} else
		return "";
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Subscriber Self-Signup Settings';

include_once("nav.inc.php");

startWindow('Account Options', null, true);
echo $form->render();
endWindow();

startWindow('Subscriber Field Values', null, true);
showObjects($data, $titles, array("valtype" => "fmt_valtype", "values" => "fmt_values", "Actions" => "fmt_actions"), false, true);
if (count($addfields) > 0) {
	buttons(icon_button("Add Another",null,null,"subscriberfieldvalue.php"));
} else {
	echo "<BR>There are no remaining 'text' or 'list' field definitions.  To create more, return to the Admin Settings page.<BR><BR>";
}
endWindow();

include_once("navbottom.inc.php");
?>