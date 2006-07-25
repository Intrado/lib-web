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

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

setCurrentPerson($_GET['id']);

/****************** main message section ******************/

$f = "person";
$s = "main";
$reloadform = 0;

if(CheckFormSubmit($f,$s))
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

			//submit changes
			$person = new Person($_SESSION['personid']);
			$person->userid = $USER->id;
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

			$email = getChildObject($person->id, 'Email', 'email');
			PopulateObject($f,$s,$email,array('email'));
			$email->personid = $person->id;
			$email->sequence = 0;
			$email->update();

			redirect('addresses.php');
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	if($_POST['addperson'] || $_POST['addperson_x']) {
		$_SESSION['personid'] = NULL;
		$person = new Person();

		PutFormData($f, $s, FieldMap::getFirstNameField(), get_magic_quotes_gpc() ? stripslashes($_POST['firstname']) : $_POST['firstname'], "text", 1, 255, true);
		PutFormData($f, $s, FieldMap::getLastNameField(), get_magic_quotes_gpc() ? stripslashes($_POST['lastname']) : $_POST['lastname'], "text", 1, 255, true);
	} else {
		$person = new Person($_SESSION['personid']);
		$data = getChildObject($person->id, 'PersonData', 'persondata');
		PopulateForm($f,$s,$data,array(array(FieldMap::getFirstNameField(),"text",1,255),
									   array(FieldMap::getLastNameField(),"text",1,255),
									   array(FieldMap::getLanguageField(),"text",1,255))
									   );
	}

	$address = getChildObject($person->id, 'Address', 'address');
	PopulateForm($f,$s,$address,array(array("addr1","text",1,50),
										array("addr2","text",1,50),
										array("city","text",1,50),
										array("state","text",1,2),
										array("zip","text",1,10)));

	$phone = getChildObject($person->id, 'Phone', 'phone');
	$phone->phone = Phone::format($phone->phone);
	PopulateForm($f,$s,$phone,array(array("phone","phone",1,20)));

	$email = getChildObject($person->id, 'Email', 'email');
	PopulateForm($f,$s,$email,array(array("email","email",1,100)));
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$name = GetFormData($f, $s, FieldMap::getFirstNameField()) . ' ' . GetFormData($f, $s, FieldMap::getLastNameField());
$TITLE = "Edit Address: " . $name;

include_once("popup.inc.php");
NewForm($f);
buttons(submit($f, $s, 'save', 'save'));
startWindow("Address Information");
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
		<th align="right" class="windowRowHeader bottomBorder">Phone:</th>
		<td class="bottomBorder"><? NewFormItem($f, $s, 'phone', 'text', 20); ?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Email:</th>
		<td class="bottomBorder"><? NewFormItem($f, $s, 'email', 'text', 50, 100); ?></td>
	</tr>
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
</table>
<?
endWindow();
buttons();
EndForm();
include_once("popupbottom.inc.php");
?>