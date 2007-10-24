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
	$phones = array_values($person->getPhones());
	for ($i=count($phones); $i<$maxphones; $i++) {
		$phones[$i] = new Phone();
		$phones[$i]->sequence = $i;
		$phones[$i]->personid = $PERSONID;
	}
	$emails = array_values($person->getEmails());
	for ($i=count($emails); $i<$maxemails; $i++) {
		$emails[$i] = new Email();
		$emails[$i]->sequence = $i;
		$emails[$i]->personid = $PERSONID;
	}
	if(getSystemSetting("_hassms")){
		$smses = array_values($person->getSmses());
		for ($i=count($smses); $i<$maxsms; $i++) {
			$smses[$i] = new Sms();
			$smses[$i]->sequence = $i;
			$smses[$i]->personid = $PERSONID;
		}
	} else {
		$smses = array();
	}
	$lockedphones= array();
	for($i=0; $i < $maxphones; $i++){
		$lockedphones[$i] = getSystemSetting("lockedphone" . $i);
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
			} else {
				$emergencyjobtypeid = QuickQuery("select id from jobtype where systempriority = '1'");
				$hasemergency = false;
				for($i=0; $i < $maxphones; $i++){
					if(GetFormData($f, $s, "phone" . $i . "jobtype" . $emergencyjobtypeid)){
						$hasemergency=true;
						break;
					}
				}
				if(!$hasemergency){
					error("You must have at least one phone number that can receive emergency calls");
				} else {
					getsetContactFormData($f, $s, $PERSONID, $phones, $emails, $smses, $jobtypes);
					
					if(GetFormData($f, $s, "savetoall")){
						//Fetch all person id's associated with this user on this customer
						//then remove the current person id from the list
						$otherContacts = getContactIDs($_SESSION['portaluserid']);
						unset($otherContacts[array_search($PERSONID, $otherContacts)]);
						copyContactData($PERSONID, $otherContacts, $lockedphones);
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