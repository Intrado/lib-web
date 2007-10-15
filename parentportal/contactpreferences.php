<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/JobType.obj.php");
require_once("parentportalutils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$PERSONID = 0;


if($_SESSION['customerid']){
	$jobtypes=DBFindMany("JobType", "from jobtype where not deleted");
	$contactList = getContacts($_SESSION['portaluserid']);
	$firstnamefield = FieldMap::getFirstNameField();
	$lastnamefield = FieldMap::getLastNameField();
	if(isset($_GET['id'])){
		$PERSONID = $_GET['id'] + 0;
		$person = new Person($PERSONID);
	}
}
	



////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Contact Preferences";
if($PERSONID){
	$TITLE .= " - " . $person->$firstnamefield . " " . $person->$lastnamefield;
}
include_once("nav.inc.php");
startWindow("Preferences", 'padding: 3px;');

?>
<table border="1" width="100%" cellpadding="3" cellspacing="1" >
<?
	if(isset($contactList)){
?>
		<tr>
			<td width="25%">
				<table>
<?
				foreach($contactList as $person){
?>
					<tr><td><a href="contactpreferences.php?id=<?=$person->id?>"/><?=$person->pkey?> <?=$person->$firstnamefield?> <?=$person->$lastnamefield?></a></td></tr>
<?
				}

?>
				</table>
<?
			buttons(button("Add A Contact", null, "addcontact.php"));
?>
			</td>

			<td>
<?
				include("contactedit.php");
?>				
			</td>
		</tr>
<?
} else {
	?><tr><td>You are not associated with any contacts.  If you would like to add a contact, <a href="addcontact1.php"/>Click Here</a></td></tr><?
}
?>
</table>
<?
endWindow();
include_once("navbottom.inc.php");
?>