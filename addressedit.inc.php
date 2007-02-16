<?
// main panel to view/edit an address (aka contact, aka person)

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
include_once("obj/PersonData.obj.php");
include_once("obj/Address.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/ListEntry.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_POST['addperson_x'])) {
	$_SESSION['personid'] = NULL;
}

if (isset($_GET['id'])) {
	setCurrentPerson($_GET['id']);
	$_SESSION['previewreferer'] = $_SERVER['HTTP_REFERER'];
	redirect();
}

// null if new person, otherwise we edit existing
$personid = $_SESSION['personid'];

// Check if the address edit was clicked from the address book (nav) or the list page (manual add)
$fromNav = ($ORIGINTYPE === "nav");

// prepopulate person phone and email lists
if (!$maxphones = getSystemSetting("maxphones"))
	$maxphones = 4;

if (!$maxemails = getSystemSetting("maxemails"))
	$maxemails = 2;

function clearDataFields()
{
	global $maxphones, $maxemails;
	global $data, $address, $phones, $emails;

	$data = new PersonData();
	$address = new Address();

	$phones = array();
	for ($i=0; $i<$maxphones; $i++) {
		$phones[$i] = new Phone();
	}
	$emails = array();
	for ($i=0; $i<$maxemails; $i++) {
		$emails[$i] = new Email();
	}
}

if (isset($personid) &&
	DBFind("Person", "from person where id=" . $personid)) {
	// editing existing person
	$data = DBFind("PersonData", "from persondata where personid = " . $personid);
	$address = DBFind("Address", "from address where personid = " . $personid);

	// get existing phones from db, then create any additional based on the max allowed
	// TODO what if the max is less than the number they already have?
	$phones = array_values(DBFindMany("Phone", "from phone where personid=" . $personid . " order by sequence"));
	for ($i=count($phones); $i<$maxphones; $i++) {
		$phones[$i] = new Phone();
	}
	$emails = array_values(DBFindMany("Email", "from email where personid=" . $personid . " order by sequence"));
	for ($i=count($emails); $i<$maxemails; $i++) {
		$emails[$i] = new Email();
	}
} else {
	// creating new person
	clearDataFields();
}

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
		} else if (!GetFormData($f,$s,FieldMap::getFirstNameField()) &&
				   !GetFormData($f,$s,FieldMap::getLastNameField())) {
			error('First Name or Last Name is required');
		} else {

			//submit changes
			$person = new Person($personid);
			$person->userid = $fromNav ? $USER->id : (GetFormData($f,$s,"manualsave") ? $USER->id : 0);
			$person->customerid = $USER->customerid;
			$person->deleted = 0;
			$person->update();

			PopulateObject($f,$s,$data,array(FieldMap::getFirstNameField(),
											 FieldMap::getLastNameField(),
											 FieldMap::getLanguageField()
											 ));
			$data->personid = $person->id;
			$data->update();

			PopulateObject($f,$s,$address,array('addr1','addr2','city','state','zip'));
			$address->personid = $person->id;
			$address->update();

			$x = 0;
			foreach ($phones as $phone) {
				$itemname = "phone".($x+1);

				PopulateObject($f,$s,$phone,array($itemname));
				$phone->personid = $person->id;
				$phone->sequence = $x;
				$phone->phone = Phone::parse($phone->$itemname);
				$phone->update();
				$x++;
			}

			$x = 0;
			foreach ($emails as $email) {
				$itemname = "email".($x+1);

				PopulateObject($f,$s,$email,array($itemname));
				$email->personid = $person->id;
				$email->sequence = $x;
				$email->email = $email->$itemname;
				$email->update();
				$x++;
			}

			// if manual add to a list, and entry not found, then create one
			// (otherwise they edit existing contact on the list)
			if (!$fromNav && isset($_SESSION['listid']) &&
				!DBFind("ListEntry", "from listentry where listid=".$_SESSION['listid']." and personid=".$person->id)) {
				$le = new ListEntry();
				$le->listid = $_SESSION['listid'];
				$le->type = "A";
				$le->sequence = 0;
				$le->personid = $person->id;
				$le->create();
			}

			// unset this for next popup edit
			unset($_SESSION['personid']);

			if (CheckFormSubmit($f,'saveanother')) {
				// save and add another
				clearDataFields();
				$reloadform = 1;
			} else if (CheckFormSubmit($f,'savedone')) {
				// save and done
				switch ($ORIGINTYPE) {
					case "nav":
						redirect('addresses.php');
						break;
					case "manuaddbook":
						redirect('addressesmanualadd.php');
						break;
					case "manualadd":
						redirect('list.php');
						break;
					case "preview":
						redirect($_SESSION['previewreferer']);
						break;
					default:
					// TODO yikes!
					break;
				}
			} // else unexpected programmer error
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	if (!$fromNav) {
		PutFormData($f,$s,"manualsave",1,"bool",0,1,false);
	}

	PopulateForm($f,$s,$data,array(array(FieldMap::getFirstNameField(),"text",1,255),
								   array(FieldMap::getLastNameField(),"text",1,255),
								   array(FieldMap::getLanguageField(),"text",1,255))
								   );

	PopulateForm($f,$s,$address,array(array("addr1","text",1,50),
										array("addr2","text",1,50),
										array("city","text",1,50),
										array("state","text",1,2),
										array("zip","text",1,10)));

	$x = 0;
	foreach ($phones as $phone) {
		$itemname = "phone".($x+1);
		$phone->$itemname = Phone::format($phone->phone);
		PopulateForm($f,$s,$phone,array(array($itemname,"phone",1,20)));
		$x++;
	}

	$x = 0;
	foreach ($emails as $email) {
		$itemname = "email".($x+1);
		$email->$itemname = $email->email;
		PopulateForm($f,$s,$email,array(array($itemname,"email",1,20)));
		$x++;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = $fromNav ? "start:addressbook" : "notifications:lists";

$name = GetFormData($f, $s, FieldMap::getFirstNameField()) . ' ' . GetFormData($f, $s, FieldMap::getLastNameField());
if (strlen(trim($name)) == 0) $name = "New Contact";
$TITLE = "Enter Contact Information: " . $name;

include_once("nav.inc.php");
NewForm($f);

if ($ORIGINTYPE === "preview") {
	buttons(submit($f, 'savedone', 'savedone', 'save'),
		button('cancel',NULL,$_SESSION['previewreferer']));
} else {
	$cancelAction = $fromNav ? "addresses.php" : "addressesmanualadd.php";
	if ($ORIGINTYPE === "manualadd") { // return to lists page
		$cancelAction = "list.php";
	}
	buttons(submit($f, 'saveanother', 'saveanother', 'save_add_another'),
		submit($f, 'savedone', 'savedone', 'save_done'),
		button('cancel',NULL,$cancelAction));
}

startWindow("Contact");
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Name:</th>
		<td class="bottomBorder">
			First: <? NewFormItem($f, $s, FieldMap::getFirstNameField(), 'text',20,50); ?>
			Last: <? NewFormItem($f, $s, FieldMap::getLastNameField(), 'text',20,50); ?>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Language Preference:</th>
		<td  class="bottomBorder">
			<?
			NewFormItem($f,$s,FieldMap::getLanguageField(),"selectstart");
			$data = DBFindMany('Language', "from language where customerid='$USER->customerid' order by name");
			foreach($data as $language)
				NewFormItem($f,$s,FieldMap::getLanguageField(),"selectoption",$language->name,$language->name);
			NewFormItem($f,$s,FieldMap::getLanguageField(),"selectend");
			?>
		</td>
	</tr>

<?
	$x = 0;
	foreach ($phones as $phone) {
		$header = "Phone " . ($x+1) . ":";
		if ($x == 0) $header = "Primary Phone:";
		$itemname = "phone".($x+1);
?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder"><?= $header ?></th>
		<td class="bottomBorder"><? NewFormItem($f, $s, $itemname, 'text', 20); ?></td>
	</tr>
<?
		$x++;
	}

	$x = 0;
	foreach ($emails as $email) {
		$header = "Email " . ($x+1) . ":";
		if ($x == 0) $header = "Primary Email:";
		$itemname = "email".($x+1);
?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder"><?= $header ?></th>
		<td class="bottomBorder"><? NewFormItem($f, $s, $itemname, 'text', 50, 100); ?></td>
	</tr>
<?
		$x++;
	}
?>

	<tr>
		<th align="right" valign="top" class="windowRowHeader" style="padding-top: 10px;">Address:</th>
		<td>
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
						Zip: <? NewFormItem($f, $s, 'zip', 'text', 5, 5); ?>&nbsp;
					</td>
				</tr>
			</table>
		</td>
	</tr>

<? if ($ORIGINTYPE === "manualadd") {	?>
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