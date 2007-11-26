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
	unset($_SESSION['currentpersonpkey']);
	redirect();
}

if(isset($_SESSION['customerid']) && $_SESSION['customerid']){
	$jobtypes=DBFindMany("JobType", "from jobtype where not deleted order by systempriority, issurvey, name");
	$contactList = getContacts($_SESSION['portaluserid']);
	$firstnamefield = FieldMap::getFirstNameField();
	$lastnamefield = FieldMap::getLastNameField();
	if(isset($_GET['id'])){
		$personpkey = DBSafe($_GET['id']);
		if(!isset($contactList[$personpkey])){
			redirect("unauthorized.php");
		}
		$_SESSION['currentpersonpkey'] = $personpkey;
		redirect();
	}
	if(isset($_SESSION['currentpersonpkey'])){
		$personpkey = $_SESSION['currentpersonpkey'];
	}
	if(isset($contactList[$personpkey])){
		$person = $contactList[$personpkey];
		$PERSONID = $person->id;
	}
}

if($PERSONID){
	
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
				if(getSystemSetting('priorityenforcement') && $error = checkPriorityPhone($f, $s, $phones)){
					error("You must have at least one phone number that can receive calls for these job types", $error);
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

function contact_actions($obj, $index){
	return "<a href='contactpreferences.php?id=" . $obj->pkey . "#edit'>Edit</a>";
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Contact Preferences";
if($PERSONID){
	$TITLE .= " - " . $person->$firstnamefield . " " . $person->$lastnamefield;
}
include_once("nav.inc.php");
startWindow("Contacts" . help("Contactpreferences"), 'padding: 3px;');

if(isset($contactList) && $contactList){
	buttons(button("Add A Contact", null, "addcontact1.php"));

	$titles = array("pkey" => "ID#",
					$firstnamefield => "First Name",
					$lastnamefield => "Last Name",
					"Actions" => "Actions");
	$formatters = array("Actions" => "contact_actions");
	
	
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
	echo '<tr class="listHeader">';
	foreach ($titles as $title) {

		echo '<th align="left">';
		echo htmlentities($title) . '</th>';
	}
	echo "</tr>\n";

	$alt = 0;;
	foreach ($contactList as $obj) {
		$alt++;
		if($obj->id == $PERSONID){
			echo '<tr style="color:white; background-color:red">';
		} else if($alt % 2){
			echo '<tr>';
		} else {
			echo '<tr class="listAlt">';
		}

		//only show cels with titles
		foreach ($titles as $index => $title) {
			//echo the td first so if fn outputs directly and returns empty string, it will still display correctly
			echo "<td>";
			if (isset($formatters[$index])) {
				$fn = $formatters[$index];
				$cel = $fn($obj,$index);
			} else {
				$cel = htmlentities($obj->$index);
			}
			echo $cel . "</td>";
		}

		echo "</tr>\n";
	}
	echo "</table>";
	
} else {
?>
	<div style="margin:5px">
		<img src="img/bug_important.gif" >You are not associated with any contacts.  If you would like to add a contact, <a href="addcontact1.php"/>Click Here</a>
	</div>
<?
}
endWindow();

if($PERSONID){
?><a name="edit"></a><?
	startWindow($person->$firstnamefield . " " . $person->$lastnamefield, 'padding: 3px;');
?>
	<table width="100%">
		<tr>
			<td>
<?
	include_once("contactedit.php");
?>
			</td>
		</tr>
	</table>
<?
	endWindow();
}
include_once("navbottom.inc.php");
?>