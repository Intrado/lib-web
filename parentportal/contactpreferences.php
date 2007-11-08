<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
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

if(isset($_SESSION['customerid']) && $_SESSION['customerid']){
	$jobtypes=DBFindMany("JobType", "from jobtype where not deleted order by systempriority, issurvey, name");
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
	
	$maxphones = getSystemSetting("maxphones");
	$maxemails = getSystemSetting("maxemails");
	$maxsms = getSystemSetting("maxsms");
	$tempphones = resequence($person->getPhones());
	$phones = array();
	for ($i=0; $i<$maxphones; $i++) {
		if(!isset($tempphones[$i])){
			$phones[$i] = new Phone();
			$phones[$i]->sequence = $i;
			$phones[$i]->personid = $PERSONID;
		} else {
			$phones[$i] = $tempphones[$i];
		}
	}
	$tempemails = resequence($person->getEmails());
	$emails = array();
	for ($i=0; $i<$maxemails; $i++) {
		if(!isset($tempemails[$i])){
			$emails[$i] = new Email();
			$emails[$i]->sequence = $i;
			$emails[$i]->personid = $PERSONID;
		} else {
			$emails[$i] = $tempemails[$i];
		}
	}
	if(getSystemSetting("_hassms")){
		$tempsmses = resequence($person->getSmses());
		$smses = array();
		for ($i=0; $i<$maxsms; $i++) {
			if(!isset($tempsmses[$i])){
				$smses[$i] = new Sms();
				$smses[$i]->sequence = $i;
				$smses[$i]->personid = $PERSONID;
			} else {
				$smses[$i] = $tempsmses[$i];
			}
		}
	} else {
		$smses = array();
	}
	$locked = getLockedDestinations($maxphones, $maxemails, $maxsms);
	$lockedphones = $locked['phones'];
	$lockedemails = $locked['emails'];
	$lockedsms = $locked['sms'];

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
			} else {
				if(!checkEmergencyPhone($f, $s, $phones)){
					error("You must have at least one valid phone number that can receive emergency calls");
				} else {
					getsetContactFormData($f, $s, $PERSONID, $phones, $emails, $smses, $jobtypes, $locked);
					
					if(GetFormData($f, $s, "savetoall")){
						//Fetch all person id's associated with this user on this customer
						//then remove the current person id from the list
						$otherContacts = getContactIDs($_SESSION['portaluserid']);
						unset($otherContacts[array_search($PERSONID, $otherContacts)]);
						copyContactData($PERSONID, $otherContacts, $locked);
					}
					redirect();
				}
			}
		}
	} else {
		$reloadform = 1;
	}

	if( $reloadform )
	{
		ClearFormData($f);
		PutFormData($f, $s, "savetoall", "1", "bool", 0, 1);
		putContactPrefFormData($f, $s, $contactprefs, $defaultcontactprefs, $phones, $emails, $smses, $jobtypes, $locked);
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
startWindow("Preferences" . help("Contactpreferences"), 'padding: 3px;');

?>
<table width="100%" cellpadding="3" cellspacing="1" >
<?
	if(isset($contactList)){
?>
		<tr>
			<td valign="top">
				<table>
<?
				foreach($contactList as $person){
?>
					<tr><td><a href="contactpreferences.php?id=<?=$person->id?>"><?=$person->pkey . "&nbsp;" . $person->$firstnamefield . "&nbsp;" . $person->$lastnamefield?></a></td></tr>
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
	?><tr><td><img src="img/bug_important.gif" >You are not associated with any contacts.  If you would like to add a contact, <a href="addcontact1.php"/>Click Here</a></td></tr><?
}
?>
</table>
<?
endWindow();
include_once("navbottom.inc.php");
?>