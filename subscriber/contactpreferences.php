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
require_once("subscriberutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$firstnamefield = FieldMap::getFirstNameField();
$lastnamefield = FieldMap::getLastNameField();
$jobtypes=DBFindMany("JobType", "from jobtype where not deleted order by systempriority, issurvey, name");

$pid = $_SESSION['personid'];
$person = DBFind("Person", "from person where id=".$pid);

	$maxphones = getSystemSetting("maxphones", 3);
	$maxemails = getSystemSetting("maxemails", 2);
	$maxsms = getSystemSetting("maxsms", 2);
	$tempphones = resequence($person->getPhones(), "sequence");
	$phones = array();
	for ($i=0; $i<$maxphones; $i++) {
		if(!isset($tempphones[$i])){
			$phones[$i] = new Phone();
			$phones[$i]->sequence = $i;
			$phones[$i]->personid = $pid;
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
			$emails[$i]->personid = $pid;
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
				$smses[$i]->personid = $pid;
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

	$contactprefs = getContactPrefs($pid);
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
					error("You must have at least one phone number that can receive calls for these job types: " . implode(", ", $error));
				} else {
					getsetContactFormData($f, $s, $pid, $phones, $emails, $smses, $jobtypes, $locked);

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
		putContactPrefFormData($f, $s, $contactprefs, $defaultcontactprefs, $phones, $emails, $smses, $jobtypes, $locked);
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
$TITLE .= " - " . escapehtml($person->$firstnamefield) . " " . escapehtml($person->$lastnamefield);

include_once("nav.inc.php");

?><a name="edit"></a><?
startWindow(escapehtml($person->$firstnamefield) . " " . escapehtml($person->$lastnamefield), 'padding: 3px;');
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

include_once("navbottom.inc.php");
?>