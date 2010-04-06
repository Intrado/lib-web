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
	QuickUpdate("delete from persondatavalues where fieldnum=? and editlock=1 and refcount=0", false, array($fieldmap->fieldnum));
	// clear static subscriber values, reset editlock
	QuickUpdate("update persondatavalues set editlock=0 where fieldnum=?", false, array($fieldmap->fieldnum));

	QuickUpdate("commit");
	redirect();
}

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();
$languageField = FieldMap::getLanguageField();

// all subscriber fields already added
$data = DBFindMany("FieldMap","from fieldmap where options like '%subscribe%'");
// add the special case Organization field, required
$dummyorganizationfieldmap = FieldMap::getSubscriberOrganizationFieldMap();
$data = array_merge($data, array($dummyorganizationfieldmap));

// all fields available to add
$addfields = QuickQueryList("select id, name from fieldmap where options not like '%subscribe%' and options not like '%language%' and (options like '%text%' or options like '%multisearch%') order by fieldnum", true);


$titles = array(	"name" => "Field",
					"valtype" => "Value Type",
					"values" => "Values",
					"Actions" => "Actions"
					);

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
	
	$action_links = "";
	if ("oid" == $obj->fieldnum) {
		if (QuickQuery("select 1 from organization where not deleted limit 1"))
			$action_links = action_link("Edit Values", "pencil", "subscriberfieldedit.php?id='oid'");
	} else {		
		$action_links = action_link("Delete", "cross", "subscriberfields.php?delete=$obj->id", "return confirmDelete();");
		if ($obj->isOptionEnabled('static'))
			$action_links .= '&nbsp|&nbsp' . action_link("Edit Values", "pencil", "subscriberfieldedit.php?id=$obj->id");
	}
	return $action_links;
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
			return "English,Spanish"; // TODO
		} else {
			// if organization, must lookup display names (persondatavalues.value stores the id)
			if ($obj->fieldnum == "oid") {
				$values = QuickQueryList("select o.orgkey from persondatavalues pdv join organization o on (pdv.value = o.id) where pdv.fieldnum='oid' and pdv.editlock=1");
			} else {
				$values = QuickQueryList("select value from persondatavalues where fieldnum=? and editlock=1", false, false, array($obj->fieldnum));
			}
			if (count($values) == 0)
				return "";
			$valcsv = implode(",", $values);
			if (strlen($valcsv) > 25)
				return escapehtml(substr($valcsv, 0, 25) . "...");
			else
				return escapehtml($valcsv);
		}
	} else
		return "";
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Self-Signup Fields';

include_once("nav.inc.php");

startWindow('Subscriber Available Fields');
showObjects($data, $titles, array("valtype" => "fmt_valtype", "values" => "fmt_values", "Actions" => "fmt_actions"), false, true);
if (count($addfields) > 0) {
	buttons(button("Add Field",null,"subscriberfieldwiz.php?new"));
} else {
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" > To configure additional self-signup fields you must first define a new 'Text' or 'List' system field from the Admin->Settings page.
</div>
<?
}
endWindow();

include_once("navbottom.inc.php");
?>
