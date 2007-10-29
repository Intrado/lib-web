<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("parentportalutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$result = portalGetCustomerAssociations();
if($result['result'] == ""){
	$customerlist = $result['custmap'];
	$customeridlist = array_keys($customerlist);
} else {
	$customeridlist = array();
}


$ADDWIZARD = true;
$PERSONID = 0;
if(isset($_SESSION['currentpid'])){
	$PERSONID = $_SESSION['currentpid'];
	$_SESSION['customerid'] = $_SESSION['currentcid'];
	$_SESSION['custname'] = $customerlist[$_SESSION['customerid']];
	$result = portalAccessCustomer($_SESSION['customerid']);
	if($result['result'] != ""){
		error("An error occurred, please try again");
		$error = 1;
	}
	$customerpidlist = $_SESSION['pidlist'][$_SESSION['customerid']];
	$personindex = array_search($PERSONID, $customerpidlist);
	$person = new Person($PERSONID);
} else if(isset($_SESSION['pidlist']) && count($_SESSION['pidlist'])){
	$customerpidlist = end($_SESSION['pidlist']);
	$_SESSION['customerid'] = key($_SESSION['pidlist']);
	
	$PERSONID = end($customerpidlist);
	$personindex = key($customerpidlist);
	
	$_SESSION['custname'] = $customerlist[$_SESSION['customerid']];
	$result = portalAccessCustomer($_SESSION['customerid']);
	
	if($result['result'] != ""){
		error("An error occurred, please try again");
		$error = 1;
	}
	
	$person = new Person($PERSONID);
} else {
	redirect("contactpreferences.php");
}
if($PERSONID){
	$firstnamefield = FieldMap::getFirstNameField();
	$lastnamefield = FieldMap::getLastNameField();
	$jobtypes=DBFindMany("JobType", "from jobtype where not deleted order by systempriority, issurvey, name");
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
					error("You must have at least one phone number that can receive emergency calls");
				} else {
					getsetContactFormData($f, $s, $PERSONID, $phones, $emails, $smses, $jobtypes, $locked);
					unset($_SESSION['pidlist'][$_SESSION['customerid']][$personindex]);
					if(GetFormData($f, $s, "savetoall")){
						//Fetch all person id's associated with this user on this customer
						//then remove the current person id from the list
						$otherContacts = getContactIDs($_SESSION['portaluserid']);
						unset($otherContacts[array_search($PERSONID, $otherContacts)]);
						copyContactData($PERSONID, $otherContacts, $locked);
						unset($_SESSION['pidlist'][$_SESSION['customerid']]);
					}
					unset($_SESSION['currentpid']);
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

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "contacts:contactpreferences";
$TITLE = "Edit Contact Details - " . $person->$firstnamefield . " " . $person->$lastnamefield;

include("nav.inc.php");
startWindow("Preferences");
?>
<table>
	<tr>
		<td>
<?
			include("contactedit.php");
?>
		</td>
	</tr>
</table>
<?
endWindow();
include("navbottom.inc.php");
} else {
	redirect("contactpreferences.php");
}
?>