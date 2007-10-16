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
	portalAccessCustomer(session_id(), $_SESSION['currentcid']);
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
?>
<div> You have successfully added <?=$person->$firstnamefield?> <?=$person->$lastnamefield?>.
<div> Would you like to add another contact?
<?
buttons(button("Yes", NULL, "addcontact1.php"), button("No", NULL, "addcontact3.php"));


include("navbottom.inc.php");
?>