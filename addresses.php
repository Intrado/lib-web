<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
include_once("obj/Person.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/Address.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/FieldMap.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['origin'])) {
	$_SESSION['addressesorigin'] = $_GET['origin'];
	redirect();
}

if (isset($_GET['delete'])) {
	$id = DBSafe($_GET['delete']);
	if (userOwns("person",$id)) {
		$person = new Person($id);

		Query("BEGIN");
			$person->deleted = 1;
			$person->update();
			QuickUpdate("delete from listentry where personid='$id'");
		Query("COMMIT");
		notice(_L("%s is now deleted from your address book.", escapehtml(Person::getFullName($person))));
	}
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions($row, $name) {
	return action_links( array (
		action_link ("Edit", "pencil", "addressedit.php?id=".$row[0]."&origin=".$_SESSION['addressesorigin']),
		action_link ("Delete", "cross", "?delete=".$row[0],"return confirmDelete();"),
	));
}

function fmt_checkbox_addrbook($row,$index) {
	global $inlistids;

	$result = '<div align="center">';
	//see if it is in add show +, otherwise show blank
	if (in_array($row[0],$inlistids)) {
		$result .= '<img src="img/checkbox-add.png"';
		$checked = true;
	} else {
		$result .= '<img src="img/checkbox-clear.png"';
		$checked = false;
	}

	$result .= " onclick=\"dolistbox(this,";

	$result .=  (($checked) ? "true":"false") . "," . $row[0] . ');" />';
	return $result . '</div>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = ($_SESSION['addressesorigin'] == "nav") ? "start:addressbook" : "notifications:lists";
$TITLE = _L("My Address Book");

include_once("nav.inc.php");


?>
<script langauge="javascript">

function dolistbox (img, init, id) {
	if (!img.toggleset) {
		img.toggleset = true;
		img.toggle = init;
	}
	img.toggle = !img.toggle;
	img.src = "checkbox.png.php?toggle=" + img.toggle + "&id=" + id + "&foo=" + new Date();
}
</script>

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="padding-bottom: 5px;">
	<tr>
		<td align="left"><? buttons(button('Done', NULL, ($_SESSION['addressesorigin'] == "nav") ? 'start.php' : 'list.php')); ?></td>
		<td align="right" valign="bottom">
			<?= ($_SESSION['addressesorigin'] == "nav") ? '' : 'Select the individuals you want to add to your list.'?>
		</td>
	</tr>
</table>
<?
$help = (($_SESSION['addressesorigin'] == "nav") ? '' : help('AddressBook_MyAddressBook'));
startWindow('Contacts ' . $help);
?>
<table border="0" cellpadding="0" cellspacing="0" style="margin-top: 3px;">
	<tr>
		<td><? button_bar(button('Add Contact', NULL,"addressedit.php?id=new&origin=".($_SESSION['addressesorigin'] == "manualadd" ? "manualaddbook" : "nav"))); ?></td>
	</tr>
</table>

<?

$first = FieldMap::getFirstNameField();
$last = FieldMap::getLastNameField();
$lang = FieldMap::getLanguageField();

$table = Query("select person.id, pkey, $first, $last, $lang, phone, email, sms,
								concat(
									coalesce(addr1,''), ' ',
									coalesce(addr2,''), ' ',
									coalesce(city,''), ' ',
									coalesce(state,''), ' ',
									coalesce(zip,'')
								) as address,
								addr1, addr2, city, state, zip
				from person
					left join phone on (person.id = phone.personid and phone.sequence=0)
					left join email on (person.id = email.personid and email.sequence=0)
					left join sms on (sms.personid = person.id and sms.sequence=0)
					left join address on person.id = address.personid
				where userid = $USER->id and type = 'addressbook' and not deleted
				order by $last, $first
				");

$data = array();
$onpageids = array();
while($row = DBGetRow($table)) {
	$data[] = $row;
	$onpageids[] = $row[0];
}

echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';

// strange condition for listid to be null if origin is manualadd, user may have typed into url
// check listid anyway for sql query to function properly
if ($_SESSION['addressesorigin'] == "manualadd" && $_SESSION['listid'] != null) {
	//fmt_checkbox_addrbook needs $inlistids to be set
	$listid = $_SESSION['listid'];
	$inlistids = array();
	if (count($onpageids) > 0) {
		$query = "
		select le.personid
		from listentry le
		where le.listid = $listid and le.type='add'
		and (le.personid = " . implode(" or le.personid =", $onpageids) . ")
		";
		$inlistids = QuickQueryList($query);
	}

	$titles = array(1 => "In List",
					2 => "First Name",
					3 => "Last Name",
					4 => "Language");
	$formatters = array(1 => "fmt_checkbox_addrbook",
						4 => "fmt_languagecode",
						5 => "fmt_phone",
						6 => "fmt_email",
						7 => "fmt_phone",
						8 => "fmt_null",
						9 => "fmt_actions");
} else {
	$titles = array(2 => "First Name",
					3 => "Last Name",
					4 => "Language");
	$formatters = array(4 => "fmt_languagecode",
						5 => "fmt_phone",
						6 => "fmt_email",
						7 => "fmt_phone",
						8 => "fmt_null",
						9 => "fmt_actions");
}

if($USER->authorize('sendphone')){
	$titles[5] = destination_label("phone", 0);
}
if($USER->authorize('sendemail')){
	$titles[6] = destination_label("email", 0);
}
if(getSystemSetting("_hassms") && $USER->authorize('sendsms')){
	$titles[7] = destination_label("sms", 0);
}

$titles[8] = "Address";
$titles[9] = "Actions";

showTable($data, $titles,$formatters);
echo '</table>';

endWindow();

buttons();

include_once("navbottom.inc.php");
