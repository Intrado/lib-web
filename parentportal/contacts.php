<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("parentportalutils.inc.php");



////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$data = null;

if($_SESSION['customerid']){

	$data = getContacts($_SESSION['portaluserid']);
	
	$firstname = FieldMap::getFirstNameField();
	$lastname = FieldMap::getLastNameField();
	
	$titles = array("pkey" => "ID#",
					$firstname => "First Name",
					$lastname => "Last Name",
					"Actions" => "Actions"
					);
	
	$formatters = array("Actions" => "fmt_contact_actions");
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_contact_actions($obj){
	$actions = '<a href="contactpreferences.php?id=' . $obj->id . '">' . _L("Edit Contact Preferences") . '</a>';
	return $actions;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE="contacts:contacts";
$TITLE=_L("Your Contacts");
include_once("nav.inc.php");

startWindow(_L("Contacts"),'padding: 3px;');
button_bar(button(_L("Add A Contact"), null, "addcontact.php"));
if($data){
	showObjects($data, $titles, $formatters);
} else {
	?>
	<table>
		<tr><td><?=_L("You are not associated with any contacts.  If you would like to add a contact,")?> <a href="addcontact.php"/><?=_L("Click Here")?></a></td></tr>
	</table>
<?
}
endWindow();
include_once("navbottom.inc.php");
?>