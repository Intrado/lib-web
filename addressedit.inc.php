<?
// main panel to view/edit an address (aka person)

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Person.obj.php");
include_once("obj/Address.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/ListEntry.obj.php");
include_once("obj/Sms.obj.php");
include_once("obj/JobType.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	setCurrentPerson($_GET['id']);
	if ((getCurrentPerson() != $_GET['id']) &&
		$_GET['id'] != "new") {
		redirect('unauthorized.php');
	}

	$_SESSION['previewreferer'] = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL);
	redirect();
}

// null if new person, otherwise we edit existing
$personid = getCurrentPerson();

// set the redirect page, used by done or cancel button
switch ($ORIGINTYPE) {
case "nav":
	$redirectPage = "addresses.php";
	break;
case "manualaddbook":
	$redirectPage = "addressesmanualadd.php";
	break;
case "manualadd":
	$redirectPage = "list.php";
	break;
case "preview":
	$redirectPage =  $_SESSION['previewreferer'];
	break;
default:
	// TODO yikes! programmer error
	redirect('unauthorized.php');
	break;
}

// prepopulate person phone and email lists
if (!$maxphones = getSystemSetting("maxphones"))
	$maxphones = 3;

if (!$maxemails = getSystemSetting("maxemails"))
	$maxemails = 2;

if (!$maxsms = getSystemSetting("maxsms"))
	$maxsms = 2;

if ($personid == NULL) {
	// create a new person with empty data
	$person = new Person();
	$f = FieldMap::getLanguageField();
	$person->$f = "English"; // default language, so that first in alphabet is not selected (example, Chinese)
	$address = new Address();
} else {
	// editing existing person
	$person = DBFind("Person", "from person where id = " . $personid);
	$address = DBFind("Address", "from address where personid = " . $personid);
	if ($address === false) $address = new Address(); // contact was imported/uploaded without any address data, create one now

	// get existing phones from db, then create any additional based on the max allowed
	// what if the max is less than the number they already have? the GUI does not allow to decrease this value, so NO WORRIES :)
	$tempphones = resequence(DBFindMany("Phone", "from phone where personid=" . $personid . " order by sequence"),"sequence");
	$tempemails = resequence(DBFindMany("Email", "from email where personid=" . $personid . " order by sequence"),"sequence");
	$tempsmses = resequence(DBFindMany("Sms", "from sms where personid=" . $personid . " order by sequence"),"sequence");
}

$phones = array();
$emails = array();
$smses = array();


for ($i=0; $i<$maxphones; $i++) {
	if(!isset($tempphones[$i])){
		$phones[$i] = new Phone();
		$phones[$i]->sequence = $i;
	} else {
		$phones[$i] = $tempphones[$i];
	}
}

for ($i=0; $i<$maxemails; $i++) {
	if(!isset($tempemails[$i])){
		$emails[$i] = new Email();
		$emails[$i]->sequence = $i;
	} else {
		$emails[$i] = $tempemails[$i];
	}
}
for ($i=0; $i<$maxsms; $i++) {
	if(!isset($tempsmses[$i])){
		$smses[$i] = new Sms();
		$smses[$i]->sequence = $i;
	} else {
		$smses[$i] = $tempsmses[$i];
	}
}

$contactprefs = $personid ? getContactPrefs($personid) : array();
$defaultcontactprefs = getDefaultContactPrefs();
$contacttypes = array();
$types = array();
if($USER->authorize('sendphone')){
	$contacttypes[] = "phone";
	$types["phone"] = $phones;
}
if($USER->authorize('sendemail')){
	$contacttypes[] = "email";
	$types["email"] = $emails;
}
if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms')){
	$contacttypes[] = "sms";
	$types["sms"] = $smses;
}
$jobtypes = JobType::getUserJobTypes(false);
if (getSystemSetting('_hassurvey', true) && $USER->authorize('survey'))
	$jobtypes = array_merge($jobtypes, JobType::getUserJobTypes(true));



/****************** main message section ******************/

$f = "person";
$s = "main";
$reloadform = 0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'saveanother') || CheckFormSubmit($f,'savedone'))
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
		} else if (!TrimFormData($f,$s,FieldMap::getFirstNameField()) &&
				   !TrimFormData($f,$s,FieldMap::getLastNameField())) {
			error('First Name or Last Name is required');
		} else {

			//submit changes
			$person->userid = $USER->id;
			if (!isset($personid)) {
				switch ($ORIGINTYPE) {
					case "nav":
						$person->type="addressbook";
						break;
					case "manualaddbook":
					case "manualadd":
						$person->type = GetFormData($f,$s,"manualsave") ? "addressbook" : "manualadd";
						break;
					case "preview":
					default:
					// TODO yikes! how did we get here?
					break;
				}
			}
			$person->deleted = 0;


			PopulateObject($f,$s,$person,array(FieldMap::getFirstNameField(),
											 FieldMap::getLastNameField(),
											 FieldMap::getLanguageField()
											 ));
			$person->update();

			PopulateObject($f,$s,$address,array('addr1','addr2','city','state','zip'));
			$address->personid = $person->id;
			$address->update();

			if($USER->authorize('sendphone')){
				$x = 0;
				foreach ($phones as $phone) {
					$itemname = "phone".($x+1);
					$phone->personid = $person->id;
					$phone->sequence = $x;
					$phone->phone = Phone::parse(TrimFormData($f,$s,$itemname));
					$phone->update();
					$x++;
				}
			}

			if($USER->authorize('sendemail')){
				$x = 0;
				foreach ($emails as $email) {
					$itemname = "email".($x+1);
					$email->personid = $person->id;
					$email->sequence = $x;
					$email->email = TrimFormData($f,$s,$itemname);
					$email->update();
					$x++;
				}
			}
			if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms')){
				$x = 0;
				foreach ($smses as $sms) {
					$itemname = "sms".($x+1);
					$sms->personid = $person->id;
					$sms->sequence = $x;
					$sms->sms = Phone::parse(TrimFormData($f,$s,$itemname));
					$sms->update();
					$x++;
				}
			}
			// if manual add to a list, and entry not found, then create one
			// (otherwise they edit existing contact on the list)

			if (!isset($personid) && ($ORIGINTYPE != "nav") && isset($_SESSION['listid']) &&
				!DBFind("ListEntry", "from listentry where listid=".$_SESSION['listid']." and personid=".$person->id)) {

				$le = new ListEntry();
				$le->listid = $_SESSION['listid'];
				$le->type = "A";
				$le->sequence = 0;
				$le->personid = $person->id;
				$le->create();
			}

			// unset this for next popup edit
			setCurrentPerson("new");
			if($person->id){
				$personid=$person->id;
				foreach($contacttypes as $type){
					if(!isset($types[$type])) continue;
					foreach($types[$type] as $item){
						foreach($jobtypes as $jobtype){
							if((!isset($contactprefs[$type][$item->sequence][$jobtype->id]) && !isset($defaultcontactprefs[$type][$item->sequence][$jobtype->id]) &&
											GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id))
								||
								(!isset($contactprefs[$type][$item->sequence][$jobtype->id]) && isset($defaultcontactprefs[$type][$item->sequence][$jobtype->id]) &&
											GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id) != $defaultcontactprefs[$type][$item->sequence][$jobtype->id])){
									QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
												values ('" . $personid . "','" . $jobtype->id . "','$type','" . $item->sequence . "','"
												. DBSafe(GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id)) . "')");
								} else if(isset($contactprefs[$type][$item->sequence][$jobtype->id]) &&
											GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id) != $contactprefs[$type][$item->sequence][$jobtype->id]){
									QuickUpdate("update contactpref set enabled = '" . DBSafe(GetFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id)) . "'
													where personid = '" . $personid . "' and jobtypeid = '" . $jobtype->id . "' and sequence = '" . $item->sequence . "'");
							}
						}
					}
				}
			}

			if (CheckFormSubmit($f,'saveanother')) {
				// save and add another
				$reloadform = 1;
				redirect();
			} else if (CheckFormSubmit($f,'savedone')) {
				// save and done
				redirect($redirectPage);
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	if ($ORIGINTYPE != "nav") {
		// add to addressbook checkbox
		PutFormData($f,$s,"manualsave",1,"bool",0,1,false);
	}

	PopulateForm($f,$s,$person,array(array(FieldMap::getFirstNameField(),"text",1,255),
								   array(FieldMap::getLastNameField(),"text",1,255),
								   array(FieldMap::getLanguageField(),"text",1,255))
								   );

	PopulateForm($f,$s,$address,array(array("addr1","text",1,50),
										array("addr2","text",1,50),
										array("city","text",1,50),
										array("state","alpha",2,2),
										array("zip","number",10000,99999)));

	if($USER->authorize('sendphone')){
		$x = 0;
		foreach ($phones as $phone) {
			$itemname = "phone".($x+1);
			PutFormData($f,$s,$itemname,Phone::format($phone->phone),"phone",10,10);
			$x++;
		}
	}
	if($USER->authorize('sendemail')){
		$x = 0;
		foreach ($emails as $email) {
			$itemname = "email".($x+1);
			PutFormData($f,$s,$itemname,$email->email,"email",5,100);
			$x++;
		}
	}
	if (getSystemSetting('_hassms', false) && $USER->authorize('sendsms')){
		$x = 0;
		foreach ($smses as $sms) {
			$itemname = "sms".($x+1);
			PutFormData($f,$s,$itemname,Phone::format($sms->sms),"phone",10,10);
			$x++;
		}
	}

	foreach($contacttypes as $type){
		if(!isset($types[$type])) continue;
		foreach($types[$type] as $item){
			foreach($jobtypes as $jobtype){
				$contactpref = 0;
				if(isset($contactprefs[$type][$item->sequence][$jobtype->id]))
					$contactpref = $contactprefs[$type][$item->sequence][$jobtype->id];
				else if(isset($defaultcontactprefs[$type][$item->sequence][$jobtype->id]))
					$contactpref = $defaultcontactprefs[$type][$item->sequence][$jobtype->id];
				PutFormData($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id, $contactpref, "bool", 0, 1);
			}
		}
	}

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = ($ORIGINTYPE == "nav") ? "start:addressbook" : "notifications:lists";

$name = TrimFormData($f, $s, FieldMap::getFirstNameField()) . ' ' . TrimFormData($f, $s, FieldMap::getLastNameField());
if (!$personid) $name = "New Contact";
$TITLE = "Enter Contact Information: " . escapehtml($name);

include_once("nav.inc.php");
NewForm($f);

if (!isset($personid)) {
	buttons(submit($f, 'saveanother', 'Save &amp Add Another'),
		submit($f, 'savedone', 'Save'),
		button('Cancel',NULL,$redirectPage));
} else {
	buttons(submit($f, 'savedone', 'Save'),
		button('Cancel',NULL,$redirectPage));
}

startWindow("Contact");
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Name:</th>
		<td class="bottomBorder">
			First: <? NewFormItem($f, $s, FieldMap::getFirstNameField(), 'text',20,255); ?>
			Last: <? NewFormItem($f, $s, FieldMap::getLastNameField(), 'text',20,255); ?>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Language Preference:</th>
		<td  class="bottomBorder">
			<?
			NewFormItem($f,$s,FieldMap::getLanguageField(),"selectstart");
			NewFormItem($f,$s,FieldMap::getLanguageField(),"selectoption"," - ","");
			$data = DBFindMany('Language', "from language order by name");
			foreach($data as $language)
				NewFormItem($f,$s,FieldMap::getLanguageField(),"selectoption",$language->name,$language->name);
			NewFormItem($f,$s,FieldMap::getLanguageField(),"selectend");
			?>
		</td>
	</tr>
	<tr>
		<th align="right" valign="top" class="windowRowHeader bottomBorder" style="padding-top: 10px;">Address:</th>
		<td class="bottomBorder">
			<table border="0">
				<tr>
					<td align="right">Line 1:</td>
					<td><? NewFormItem($f, $s, 'addr1', 'text',33,50); ?></td>
				</tr>
				<tr>
					<td align="right">Line 2:</td>
					<td><? NewFormItem($f, $s, 'addr2', 'text',33,50); ?></td>
				</tr>
				<tr>
					<td align="right">City:</td>
					<td>
						<? NewFormItem($f, $s, 'city', 'text',8,50); ?>&nbsp;
						State: <? NewFormItem($f, $s, 'state', 'text',2); ?>&nbsp;
						Zip: <? NewFormItem($f, $s, 'zip', 'text', 5, 10); ?>&nbsp;
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader">Contact Details: </th>
		<td>
			<table  cellpadding="2" cellspacing="1">

<?
	foreach($contacttypes as $type){
		if(!isset($types[$type])) continue;
?>
		<tr class="listHeader">
			<th align="left" colspan="<?=count($jobtypes)+3; ?>"><?=format_delivery_type($type); ?></th>
		</tr>
		<tr class="windowRowHeader">
			<th align="left">Contact&nbsp;Type</th>
			<th align="left">Destination</th>
<?
			foreach($jobtypes as $jobtype){
				?><th><?=jobtype_info($jobtype)?></th><?
			}
?>
		</tr>
<?
		foreach($types[$type] as $item){
			$header = destination_label($type, $item->sequence);
?>
			<tr>
				<td class="bottomBorder"><?= $header ?></td>
<?
				?><td class="bottomBorder"><?
				if($type == "email"){
					NewFormItem($f, $s, $type . ($item->sequence + 1), "text", 30, 100, "id='" . $type . $item->sequence . "'");
				} else {
					NewFormItem($f, $s, $type . ($item->sequence + 1), "text", 14, null, "id='" . $type . $item->sequence . "'");
				}
				?></td><?
				foreach($jobtypes as $jobtype){
?>
					<td align="center"  class="bottomBorder">
<?
						if($type != "sms" || ($type == "sms" && !$jobtype->issurvey)){
							echo NewFormItem($f, $s, $type . $item->sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1);
						} else {
							echo "&nbsp;";
						}
?>
					</td>
<?
				}
?>
			</tr>
<?
		}
	}
?>
			</table>
		<td>
	</tr>


<? if ($ORIGINTYPE == "manualadd") {	?>
	<tr>
		<td colspan="6"><div><? NewFormItem($f,$s,"manualsave","checkbox"); ?>Save to My Address Book <?= help('List_AddressBookAdd',NULL,"small"); ?></div></td>
	</tr>
<? } ?>

</table>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>