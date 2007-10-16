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

if(isset($_GET['clear'])){
	unset($_SESSION['currentpersonid']);
	redirect();
}

if(isset($_SESSION['customerid'])){
	$jobtypes=DBFindMany("JobType", "from jobtype where not deleted order by systempriority, name");
	$contactList = getContacts($_SESSION['portaluserid']);
	$firstnamefield = FieldMap::getFirstNameField();
	$lastnamefield = FieldMap::getLastNameField();
	if(isset($_GET['id'])){
		$PERSONID = $_GET['id'] + 0;
		$_SESSION['currentpersonid'] = $PERSONID;
		$person = new Person($PERSONID);
	} else if(isset($_SESSION['currentpersonid'])){
		$PERSONID = $_SESSION['currentpersonid'];
		$person = new Person($PERSONID);
	}
}

if($PERSONID){
	
	$phones = $person->getPhones();
	$emails = $person->getEmails();
	$smses = null;
	//TODO: uncomment and delete null
	//$smses = $person->getSmses();
	$maxphones = getSystemSetting("maxphones");
	$accessiblePhones= array();
	for($i=0; $i < $maxphones; $i++){
		$accessiblePhones[$i] = getSystemSetting("accessiblePhone" . $i);
	}

	$contactprefs = getContactPrefs($PERSONID);
	$defaultcontactprefs = getDefaultContactPrefs();

	/****************** main message section ******************/

	$f = "contactpreferences";
	$s = "main";
	$reloadform = 0;


	if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, "all"))
	{
		//check to see if formdata is valid
		if(CheckFormInvalid($f))
		{
			error('Form was edited in another window, reloading data');
			$reloadform = 1;
		}
		else
		{
			MergeSectionFormData($f, $s);

			//do check
			if( CheckFormSection($f, $s) ) {
				error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
			} else if(checkPhones($f, $s, $phones)){
				error('There is a problem with one of your phone numbers.  Please validate that it has 10 digits');
			} else {
				
				getsetContactFormData($f, $s, $PERSONID, $phones, $emails, $smses, $jobtypes);
				
				if(GetFormData($f, $s, "savetoall")){
					//Fetch all person id's associated with this user on this customer
					//then remove the current person id from the list
					$otherContacts = getContactIDs($_SESSION['portaluserid']);
					unset($otherContacts[array_search($PERSONID, $otherContacts)]);
					copyContactData($PERSONID, $otherContacts, $accessiblePhones);
				}
				redirect();
			}
		}
	} else {
		$reloadform = 1;
	}

	if( $reloadform )
	{
		ClearFormData($f);
		PutFormData($f, $s, "savetoall", "1", "bool", 0, 1);
		putContactPrefFormData($f, $s, $contactprefs, $defaultcontactprefs, $phones, $emails, $smses, $jobtypes);
	}
}



///////////////////////////////////////////////////////////////////
// Functions
///////////////////////////////////////////////////////////////////

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
			buttons(button("Add A Contact", null, "addcontact1.php"));
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