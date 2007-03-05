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
	$personid = DBSafe($_GET['id']);
	if ($personid == "") {
		// bad
		redirect('unauthorized.php');
	}

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

function displayValue($s) {
	echo($s."&nbsp;");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

// where did we come from, list preview or contact tab
$PAGE = "notifications:lists";
if (strpos($_SERVER['HTTP_REFERER'],"contacts.php") !== false) $PAGE = "system:contacts";

$contactFullName = "";
$f = FieldMap::getFirstNameField();
$contactFullName .= $data->$f;
$f = FieldMap::getLastNameField();
$contactFullName .= " ".$data->$f;

$TITLE = "View Contact Information: " . $contactFullName;

include_once("nav.inc.php");

button_bar(button('done', NULL,$_SERVER['HTTP_REFERER']));

startWindow('Contact', 'padding: 3px;');

?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Name:</th>
		<td class="bottomBorder">
			<? displayValue($contactFullName); ?>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Language Preference:</th>
		<td  class="bottomBorder">
			<? $f=FieldMap::getLanguageField(); displayValue($data->$f); ?>
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
		<td class="bottomBorder"><? displayValue(Phone::format($phone->phone)); ?></td>
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
		<td class="bottomBorder"><? displayValue($email->email); ?></td>
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
					<td><? displayValue($address->addr1); ?></td>
				</tr>
				<tr>
					<td><? displayValue($address->addr2); ?></td>
				</tr>
				<tr>
					<td>
						<?
							if (strlen(trim($address->city)) == 0 &&
								strlen(trim($address->state)) == 0) {
									displayValue($address->zip);
								} else {
						 			displayValue($address->city.",");
						 			displayValue($address->state." ");
						 			displayValue($address->zip);
								}
						 ?>
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
		<td class="bottomBorder"><? displayValue($fval); ?></td>
	</tr>
<?
}
?>
</table>
<?


endWindow();

include_once("navbottom.inc.php");

