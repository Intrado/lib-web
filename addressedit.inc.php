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

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	setCurrentPerson($_GET['id']);
	if ((getCurrentPerson() != $_GET['id']) &&
		$_GET['id'] != "new") {
		redirect('unauthorized.php');
	}

	$_SESSION['previewreferer'] = $_SERVER['HTTP_REFERER'];
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
	$maxphones = 4;

if (!$maxemails = getSystemSetting("maxemails"))
	$maxemails = 2;

if ($personid == NULL) {
	// create a new person with empty data
	$data = new Person();
	$f = FieldMap::getLanguageField();
	$data->$f = "English"; // default language, so that first in alphabet is not selected (example, Chinese)
	$address = new Address();

	$phones = array();
	$emails = array();
} else {
	// editing existing person
	$data = DBFind("Person", "from person where id = " . $personid);
	$address = DBFind("Address", "from address where personid = " . $personid);
	if ($address === false) $address = new Address(); // contact was imported/uploaded without any address data, create one now

	// get existing phones from db, then create any additional based on the max allowed
	// what if the max is less than the number they already have? the GUI does not allow to decrease this value, so NO WORRIES :)
	$phones = array_values(DBFindMany("Phone", "from phone where personid=" . $personid . " order by sequence"));
	$emails = array_values(DBFindMany("Email", "from email where personid=" . $personid . " order by sequence"));
}

for ($i=count($phones); $i<$maxphones; $i++) {
	$phones[$i] = new Phone();
}
for ($i=count($emails); $i<$maxemails; $i++) {
	$emails[$i] = new Email();
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
				$phone->personid = $person->id;
				$phone->sequence = $x;
				$phone->phone = Phone::parse(GetFormData($f,$s,$itemname));
				$phone->update();
				$x++;
			}

			$x = 0;
			foreach ($emails as $email) {
				$itemname = "email".($x+1);
				$email->personid = $person->id;
				$email->sequence = $x;
				$email->email = GetFormData($f,$s,$itemname);
				$email->update();
				$x++;
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
		PutFormData($f,$s,$itemname,Phone::format($phone->phone),"phone",10,10);
		$x++;
	}

	$x = 0;
	foreach ($emails as $email) {
		$itemname = "email".($x+1);
		PutFormData($f,$s,$itemname,$email->email,"email",5,100);
		$x++;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = ($ORIGINTYPE == "nav") ? "start:addressbook" : "notifications:lists";

$name = GetFormData($f, $s, FieldMap::getFirstNameField()) . ' ' . GetFormData($f, $s, FieldMap::getLastNameField());
if (!$personid) $name = "New Contact";
$TITLE = "Enter Contact Information: " . $name;

include_once("nav.inc.php");
NewForm($f);

if (!isset($personid)) {
	buttons(submit($f, 'saveanother', 'saveanother', 'save_add_another'),
		submit($f, 'savedone', 'savedone', 'save'),
		button('cancel',NULL,$redirectPage));
} else {
	buttons(submit($f, 'savedone', 'savedone', 'save'),
		button('cancel',NULL,$redirectPage));
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
			$data = DBFindMany('Language', "from language order by name");
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
		<td class="bottomBorder"><? NewFormItem($f, $s, $itemname, 'text', 20, 20); ?></td>
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
						Zip: <? NewFormItem($f, $s, 'zip', 'text', 5, 10); ?>&nbsp;
					</td>
				</tr>
			</table>
		</td>
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