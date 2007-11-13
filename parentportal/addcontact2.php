<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Person.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(!isset($_SESSION['currentpid'])){
	redirect("unauthorized.php");
}

if($_SESSION['customerid'] != $_SESSION['currentcid']){
	portalAccessCustomer($_SESSION['currentcid']);
}

$person = new Person($_SESSION['currentpid']);
$firstnamefield = FieldMap::getFirstNameField();
$lastnamefield = FieldMap::getLastNameField();

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Add A Contact";

include("nav.inc.php");
startWindow("Add Successful");
?>
	<div style="margin:5px">
		You have successfully added <?=$person->$firstnamefield?> <?=$person->$lastnamefield?>.
	<br>
		Would you like to add another contact?
	</div>
<?
buttons(button("Yes", NULL, "addcontact1.php"), button("No", NULL, "addcontact3.php"));
endWindow();

include("navbottom.inc.php");
?>