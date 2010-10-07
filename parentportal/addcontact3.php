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

unset($_SESSION['phoneactivationcode']); // clear for next wizard, doubleclick


$result = portalGetCustomerAssociations();
if($result['result'] == ""){
	$customerlist = $result['custmap'];
	$customeridlist = array_keys($customerlist);
} else {
	$customeridlist = array();
}

// if no contacts, return to single start page, no tabs
if (!isset($contactList) || !$contactList)
	redirect("start.php");

$ADDWIZARD = true;
$PERSONID = 0;
if(isset($_SESSION['currentpid'])){
	$PERSONID = $_SESSION['currentpid'];
	$_SESSION['customerid'] = $_SESSION['currentcid'];
	$_SESSION['custname'] = $customerlist[$_SESSION['customerid']];
	$result = portalAccessCustomer($_SESSION['customerid']);
	if($result['result'] != ""){
		error(_L("An error occurred, please try again"));
		$error = 1;
	}
	$customerpidlist = $_SESSION['pidlist'][$_SESSION['customerid']];
	$personindex = array_search($PERSONID, $customerpidlist);
	$person = new Person($PERSONID);
	$_SESSION['savetoall'] = true;
} else if(isset($_SESSION['pidlist']) && count($_SESSION['pidlist'])){
	$_SESSION['savetoall'] = false;
	$customerpidlist = end($_SESSION['pidlist']);
	if(key($_SESSION['pidlist']) != $_SESSION['customerid']){
		$_SESSION['savetoall'] = true;
		$_SESSION['customerid'] = key($_SESSION['pidlist']);
	}
	$PERSONID = end($customerpidlist);
	$personindex = key($customerpidlist);

	$_SESSION['custname'] = $customerlist[$_SESSION['customerid']];
	$result = portalAccessCustomer($_SESSION['customerid']);

	if($result['result'] != ""){
		error(_L("An error occurred, please try again"));
		$error = 1;
	}

	$person = new Person($PERSONID);
} else {
	redirect("contactpreferences.php?clear=1");
}
if($PERSONID){
	$firstnamefield = FieldMap::getFirstNameField();
	$lastnamefield = FieldMap::getLastNameField();
	if (getSystemSetting('_hassurvey', true))
		$jobtypes=DBFindMany("JobType", "from jobtype where not deleted order by systempriority, issurvey, name");
	else
		$jobtypes=DBFindMany("JobType", "from jobtype where not issurvey and not deleted order by systempriority, issurvey, name");
	$maxphones = getSystemSetting("maxphones", 3);
	$maxemails = getSystemSetting("maxemails", 2);
	$maxsms = getSystemSetting("maxsms", 2);
	$tempphones = resequence($person->getPhones(), "sequence");
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
	$tempemails = resequence($person->getEmails(), "sequence");
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
		$tempsmses = resequence($person->getSmses(), "sequence");
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
			error(_L('Form was edited in another window, reloading data'));
			$reloadform = 1;
		}
		else
		{
			MergeSectionFormData($f, $s);

			//do check
			if( CheckFormSection($f, $s) ) {
				error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
			} else {
				if ($error = checkPriorityPhone($f, $s, $phones)) {
					error(_L("You must have at least one phone number that can receive calls for these job types: ") . implode(", ", $error));
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
					$_SESSION['savetoall'] = false;
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
		$savetoall = 0;
		if(isset($_SESSION['savetoall']) && $_SESSION['savetoall'] )
			$savetoall = 1;
		PutFormData($f, $s, "savetoall", $savetoall, "bool", 0, 1);
		putContactPrefFormData($f, $s, $contactprefs, $defaultcontactprefs, $phones, $emails, $smses, $jobtypes, $locked);
	}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "contacts:contactpreferences";
$TITLE = _L('Edit Contact Details - %1$s %2$s', escapehtml($person->$firstnamefield), escapehtml($person->$lastnamefield));

include("nav.inc.php");
startWindow(escapehtml($person->$firstnamefield) . " " . escapehtml($person->$lastnamefield));
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