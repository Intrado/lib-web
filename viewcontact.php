<?
// read-only view of an imported contact with all their metadata

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
// Authorization
////////////////////////////////////////////////////////////////////////////////
/*
//TODO
if (!$USER->authorize('viewcontacts')) {
	redirect('unauthorized.php');
}
*/

if (isset($_GET['id'])) {
	$personid = $_GET['id'];

	// validate user has rights to view this contact
	$usersql = $USER->userSQL("p", "pd");

	$query = "
		select p.id
		from 		person p
					left join	persondata pd on
							(p.id=pd.personid)
		where p.id='$personid' and $usersql
	";

	if (!($personid = QuickQuery($query))) {
		// bad
		redirect('unauthorized.php');
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

// prepopulate person phone and email lists
if (!$maxphones = getSystemSetting("maxphones"))
	$maxphones = 4;

if (!$maxemails = getSystemSetting("maxemails"))
	$maxemails = 2;

if (isset($personid)) {
	// editing existing person
	$data = DBFind("PersonData", "from persondata where personid = " . $personid);
	$address = DBFind("Address", "from address where personid = " . $personid);

	// get existing phones from db, then create any additional based on the max allowed
	// what if the max is less than the number they already have? the GUI does not allow to decrease this value, so NO WORRIES :)
	$phones = array_values(DBFindMany("Phone", "from phone where personid=" . $personid . " order by sequence"));
	for ($i=count($phones); $i<$maxphones; $i++) {
		$phones[$i] = new Phone();
	}
	$emails = array_values(DBFindMany("Email", "from email where personid=" . $personid . " order by sequence"));
	for ($i=count($emails); $i<$maxemails; $i++) {
		$emails[$i] = new Email();
	}
} else {
	// error, person should always be set, this is a viewing page!
	redirect('unauthorized.php');
}

/****************** main message section ******************/

$f = "person";
$s = "main";
$reloadform = 1;

if( $reloadform )
{
	ClearFormData($f);

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
		PopulateForm($f,$s,$phone,array(array($itemname,"phone",10,10)));
		$x++;
	}

	$x = 0;
	foreach ($emails as $email) {
		$itemname = "email".($x+1);
		$email->$itemname = $email->email;
		PopulateForm($f,$s,$email,array(array($itemname,"email",1,20)));
		$x++;
	}

$fieldmaps = FieldMap::getAuthorizedFieldMaps();
foreach ($fieldmaps as $map) {
	$fname = $map->fieldnum;
	$header = $map->name;
	$fval = $data->$fname;

	if (!strcmp($fname, FieldMap::getFirstNameField()) ||
		!strcmp($fname, FieldMap::getLastNameField()) ||
		!strcmp($fname, FieldMap::getLanguageField())) {
			continue; // skip field, it was in layout above
	}
	PopulateForm($f,$s,$data,array(array($fname,"text",1,20)));
}

}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


$PAGE = "notifications:lists";

$name = GetFormData($f, $s, FieldMap::getFirstNameField()) . ' ' . GetFormData($f, $s, FieldMap::getLastNameField());
if (strlen(trim($name)) == 0) $name = "";
$TITLE = "View Contact Information: " . $name;

include_once("nav.inc.php");

NewForm($f);

button_bar(button('done', NULL,$_SERVER['HTTP_REFERER']));

startWindow('Contact', 'padding: 3px;');

?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Name:</th>
		<td class="bottomBorder">
			First: <? NewFormItem($f, $s, FieldMap::getFirstNameField(), 'text',20,50,"disabled"); ?>
			Last: <? NewFormItem($f, $s, FieldMap::getLastNameField(), 'text',20,50,"disabled"); ?>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Language Preference:</th>
		<td  class="bottomBorder">
			<?
			NewFormItem($f,$s,FieldMap::getLanguageField(),"selectstart", 40, "nooption", "disabled");
			$langdata = DBFindMany('Language', "from language where customerid='$USER->customerid' order by name");
			foreach($langdata as $language)
				NewFormItem($f,$s,FieldMap::getLanguageField(),"selectoption",$language->name,$language->name, 40, "nooption", "disabled");
			NewFormItem($f,$s,FieldMap::getLanguageField(),"selectend", 40, "nooption", "disabled");
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
		<td class="bottomBorder"><? NewFormItem($f, $s, $itemname, 'text', 20, "nooption", "disabled"); ?></td>
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
		<td class="bottomBorder"><? NewFormItem($f, $s, $itemname, 'text', 50, 100, "disabled"); ?></td>
	</tr>
<?
		$x++;
	}
?>

	<tr>
		<th align="right" valign="top" class="windowRowHeader bottomBorder" style="padding-top: 10px;">Address:</th>
		<td class="bottomBorder">
			<table border="0">
				<tr>
					<td align="right">Line 1:</td>
					<td><? NewFormItem($f, $s, 'addr1', 'text',33,50, "disabled"); ?></td>
				</tr>
				<tr>
					<td align="right">Line 2:</td>
					<td><? NewFormItem($f, $s, 'addr2', 'text',33,50, "disabled"); ?></td>
				</tr>
				<tr>
					<td align="right">City:</td>
					<td>
						<? NewFormItem($f, $s, 'city', 'text',8,50, "disabled"); ?>&nbsp;
						State: <? NewFormItem($f, $s, 'state', 'text',2, "nooption", "disabled"); ?>&nbsp;
						Zip: <? NewFormItem($f, $s, 'zip', 'text', 5, 5, "disabled"); ?>&nbsp;
					</td>
				</tr>
			</table>
		</td>
	</tr>

<?


$fieldmaps = FieldMap::getAuthorizedFieldMaps();
foreach ($fieldmaps as $map) {
	$fname = $map->fieldnum;
	$header = $map->name;
	$fval = $data->$fname;

	if (!strcmp($fname, FieldMap::getFirstNameField()) ||
		!strcmp($fname, FieldMap::getLastNameField()) ||
		!strcmp($fname, FieldMap::getLanguageField())) {
			continue; // skip field, it was in layout above
	}

?>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder"><?= $header ?></th>
		<td class="bottomBorder"><? NewFormItem($f, $s, $fname, 'text', 50, 100, "disabled"); ?></td>
	</tr>
<?
}
?>
</table>
<?


endWindow();
EndForm();

include_once("navbottom.inc.php");

