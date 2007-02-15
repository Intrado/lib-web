<?
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

if (isset($_GET['id'])) {
	setCurrentPerson($_GET['id']);
	redirect();
}

// Check if the address edit was clicked from the address book (nav) or the list page (manual add)

if (isset($_GET['origin']) && $_GET['origin'] == 'nav') {
	$fromNav = true;
	$_SESSION['addressOrigin'] = 'nav';
} else if (isset($_GET['origin']) && $_GET['origin'] == 'list') {
	$fromNav = false;
	$_SESSION['addressOrigin'] = 'list';
	if (isset($_GET['listid'])) {
		$_SESSION['listid'] = $_GET['listid'];
	}
} else {
	$fromNav = ($_SESSION['addressOrigin'] == 'nav');
}



/****************** main message section ******************/

$f = "person";
$s = "main";
$reloadform = 0;

echo(getSystemSetting("maxphones"));
if (!$maxphones = getSystemSetting("maxphones"))
	$maxphones = 4;

if (!$maxemails = getSystemSetting("maxemails"))
	$maxemails = 2;


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
			global $fromNav;

			//submit changes
			$person = new Person($_SESSION['personid']);
			$person->userid = $fromNav ? $USER->id : GetFormData($f,$s,"manualsave") ? $USER->id : 0; //$USER->id;
			$person->customerid = $USER->customerid;
			$person->deleted = 0;
			$person->update();

			$data = getChildObject($person->id, 'PersonData', 'persondata');
			PopulateObject($f,$s,$data,array(FieldMap::getFirstNameField(),
											 FieldMap::getLastNameField(),
											 FieldMap::getLanguageField()
											 ));
			$data->personid = $person->id;
			$data->update();

			$address = getChildObject($person->id, 'Address', 'address');
			PopulateObject($f,$s,$address,array('addr1','addr2','city','state','zip'));
			$address->personid = $person->id;
			$address->update();

			$phone = getChildObject($person->id, 'Phone', 'phone');
			PopulateObject($f,$s,$phone,array('phone'));
			$phone->personid = $person->id;
			$phone->sequence = 0;
			$phone->phone = Phone::parse($phone->phone);
			$phone->update();

			global $maxphones;
			for ($x = 1; $x < $maxphones; $x++) {
				$itemname = "phone".($x+1);
				$phone2 = getChildObjectSeq($person->id, 'Phone', 'phone', $x);
				PopulateObject($f,$s,$phone2,array($itemname));
				$phone2->personid = $person->id;
				$phone2->sequence = $x;
				$phone2->phone = Phone::parse($phone2->$itemname);
				$phone2->update();
			}

			$email = getChildObject($person->id, 'Email', 'email');
			PopulateObject($f,$s,$email,array('email'));
			$email->personid = $person->id;
			$email->sequence = 0;
			$email->update();

			global $maxemails;
			for ($x = 1; $x < $maxemails; $x++) {
				$itemname = "email".($x+1);
				$email2 = getChildObjectSeq($person->id, 'Email', 'email', $x);
				PopulateObject($f,$s,$email2,array($itemname));
				$email2->personid = $person->id;
				$email2->sequence = $x;
				$email2->email = $email2->$itemname;
				$email2->update();
			}

			if (!$fromNav && isset($_SESSION['listid'])) {
				$le = new ListEntry();
				$le->listid = $_SESSION['listid'];
				$le->type = "A";
				$le->personid = $person->id;
				$le->create();
			}

			if (CheckFormSubmit($f,'saveanother')) {
				// save and add another
				$reloadform = 1;
			} else if (CheckFormSubmit($f,'savedone')) {
				// save and done
				if ($fromNav) {
					redirect('addresses.php');
				} else {
?>
<script>
					window.opener.document.location.reload();
					window.close();
</script>
<?
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

	$person = new Person($_SESSION['personid']);
	$data = getChildObject($person->id, 'PersonData', 'persondata');
	PopulateForm($f,$s,$data,array(array(FieldMap::getFirstNameField(),"text",1,255),
								   array(FieldMap::getLastNameField(),"text",1,255),
								   array(FieldMap::getLanguageField(),"text",1,255))
								   );

	$address = getChildObject($person->id, 'Address', 'address');
	PopulateForm($f,$s,$address,array(array("addr1","text",1,50),
										array("addr2","text",1,50),
										array("city","text",1,50),
										array("state","text",1,2),
										array("zip","text",1,10)));

	$phone = getChildObject($person->id, 'Phone', 'phone');
	$phone->phone = Phone::format($phone->phone);
	PopulateForm($f,$s,$phone,array(array("phone","phone",1,20)));

	global $maxphones;
	for ($x = 1; $x < $maxphones; $x++) {
		$itemname = "phone".($x+1);
		$phone2 = getChildObjectSeq($person->id, 'Phone', 'phone', $x);
		$phone2->$itemname = Phone::format($phone2->phone);  // TODO formatting does not work
		PopulateForm($f,$s,$phone2,array(array($itemname,"phone",1,20)));
	}

	$email = getChildObject($person->id, 'Email', 'email');
	PopulateForm($f,$s,$email,array(array("email","email",1,100)));

	global $maxemails;
	for ($x = 1; $x < $maxemails; $x++) {
		$itemname = "email".($x+1);
		$email2 = getChildObjectSeq($person->id, 'Email', 'email', $x);
		$email2->$itemname = $email2->email;
		PopulateForm($f,$s,$email2,array(array($itemname,"email",1,100)));
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$name = GetFormData($f, $s, FieldMap::getFirstNameField()) . ' ' . GetFormData($f, $s, FieldMap::getLastNameField());
$TITLE = "Enter Contact Information: " . $name;

include_once("popup.inc.php");
NewForm($f);
if ($fromNav) {
	buttons(submit($f, 'saveanother', 'saveanother', 'save_add_another'), submit($f, 'savedone', 'done', 'save_done'), button('cancel','window.history.go(-window.history.length); '));
} else {
	// TODO need new button images
	buttons(submit($f, 'saveanother', 'save', 'save_add_another'), submit($f, 'savedone', 'done', 'save_done'), button('cancel','window.opener.document.location.reload(); window.close(); '));
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
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Primary Phone:</th>
		<td class="bottomBorder"><? NewFormItem($f, $s, 'phone', 'text', 20); ?></td>
	</tr>

<?
global $maxphones;
for ($x = 1; $x < $maxphones; $x++) {
	$itemname = "phone".($x+1);
?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Phone <?= $x+1; ?>:</th>
		<td class="bottomBorder"><? NewFormItem($f, $s, $itemname, 'text', 20); ?></td>
	</tr>
<? } ?>

	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Primary Email:</th>
		<td class="bottomBorder"><? NewFormItem($f, $s, 'email', 'text', 50, 100); ?></td>
	</tr>

<?
global $maxemails;
for ($x = 1; $x < $maxemails; $x++) {
	$itemname = "email".($x+1);
?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Email <?= $x+1; ?>:</th>
		<td class="bottomBorder"><? NewFormItem($f, $s, $itemname, 'text', 50, 100); ?></td>
	</tr>
<? } ?>

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

<? if (!$fromNav) {	?>
	<tr>
		<td colspan="6"><div><? NewFormItem($f,$s,"manualsave","checkbox"); ?>Save to My Address Book <?= help('List_AddressBookAdd',NULL,"small"); ?></div></td>
	</tr>
<? } ?>

</table>
<?
endWindow();
buttons();
EndForm();
include_once("popupbottom.inc.php");
?>