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
	
	QuickUpdate("begin");
	
	// get fieldmap obj to remove options
	$fieldmap = new FieldMap($id);
	$fieldmap->removeOption("subscribe");
	$fieldmap->removeOption("static");
	$fieldmap->removeOptions("dynamic");
	$fieldmap->update();

	// clear static subscriber values
	QuickUpdate("delete from persondatavalues where fieldnum=? and editlock=1", false, array($fieldmap->fieldnum));
	
	// TODO should we cleanup person ffield and groupdata?
	// need to be certain to only clean subscriber persons (not imported)
	/*
	// clear person field values
	if (strpos($fieldmap->fieldnum, "f") === 0) {
		// TODO should we clean up person ffield values?
	} else { // assume starts with "g"
		// cleanup groupdata
		if ($fieldmap->fieldnum === "g10") {
			$gnum = "10"; // assume no g11, g12, ...
		} else {
			$gnum = substr($fieldmap->fieldnum, 2); // strip prepended 'g0'
		}
		QuickUpdate("delete from groupdata where fieldnum=? and importid=0", false, array($gnum));
	}
	*/
	
	QuickUpdate("commit");
	redirect();
}
// else TODO error handling

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

$formdata = array(
    "addfield" => array(
        "label" => "Field Definition:",
        "value" => "",
        "validators" => array(    
        ),
        "control" => array("SelectMenu","values" => $addfields),
        "helpstep" => 1
    )
);

$buttons = array(submit_button("Add","submit","tick"));
                
$form = new Form("addsubscriberfieldform",$formdata,null,$buttons);
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
        
		$id = $postdata['addfield'];

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

startWindow('Subscriber Field Values', null, true);
showObjects($data, $titles, array("valtype" => "fmt_valtype", "values" => "fmt_values", "Actions" => "fmt_actions"), false, true);
if (count($addfields) > 0) {
?>
	<table cellpadding="3"><tr><td><a href="subscriberfieldvalue.php"><?=_L("Add Another")?></a></td></tr></table>
<?
} else {
	echo "<BR>There are no remaining 'text' or 'list' field definitions.  To create more, return to the Admin Settings page.<BR><BR>";
}
endWindow();

/*
startWindow('Add Field', null, true);
if (count($addfields) > 0) {
	echo $form->render();
} else {
	echo "<BR>There are no remaining 'text' or 'list' field definitions.  To create more, return to the Admin Settings page.<BR><BR>";
}
endWindow();
*/

include_once("navbottom.inc.php");
?>