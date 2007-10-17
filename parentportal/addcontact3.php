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

$ADDWIZARD = true;
$jobtypes=DBFindMany("JobType", "from jobtype where not deleted");
$PERSONID = 0;
$firstnamefield = FieldMap::getFirstNameField();
$lastnamefield = FieldMap::getLastNameField();
if(isset($_SESSION['currentpid'])){
	$PERSONID = $_SESSION['currentpid'];
	$customerid = $_SESSION['currentcid'];
	$customerpidlist = $_SESSION['pidlist'][$customerid];
	$personindex = array_search($PERSONID, $customerpidlist);
	$person = new Person($PERSONID);
} else if(isset($_SESSION['pidlist']) && count($_SESSION['pidlist'])){
	$customerpidlist = end($_SESSION['pidlist']);
	$customerid = key($_SESSION['pidlist']);
	$PERSONID = end($customerpidlist);
	$personindex = key($customerpidlist);
	$person = new Person($PERSONID);
} else {
	redirect("contactpreferences.php");
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
	$smses = array_values($person->getSmses());
	for ($i=count($smses); $i<$maxsms; $i++) {
		$smses[$i] = new Sms();
		$smses[$i]->sequence = $i;
		$smses[$i]->personid = $PERSONID;
	}
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
			} else {
				
				getsetContactFormData($f, $s, $PERSONID, $phones, $emails, $smses, $jobtypes);
				unset($_SESSION['pidlist'][$customerid][$personindex]);
				if(GetFormData($f, $s, "savetoall")){
					//Fetch all person id's associated with this user on this customer
					//then remove the current person id from the list
					$otherContacts = getContactIDs($_SESSION['portaluserid']);
					unset($otherContacts[array_search($PERSONID, $otherContacts)]);
					copyContactData($PERSONID, $otherContacts, $accessiblePhones);
					unset($_SESSION['pidlist'][$customerid]);
				}
				unset($_SESSION['currentpid']);
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

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "contacts:contactpreferences";
$TITLE = "Edit Contact Details - " . $person->$firstnamefield . " " . $person->$lastnamefield;

include("nav.inc.php");

include("contactedit.php");
include("navbottom.inc.php");
} else {
	redirect("contactpreferences.php");
}
?>