<?
// this is the main address book implementation

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Person.obj.php");
include_once("obj/Address.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$id = DBSafe($_GET['delete']);
	if (userOwns("person",$id)) {
		$person = new Person($id);
		$person->deleted = 1;
		$person->update();
		QuickQuery("delete from listentry where personid='$id'");
	}
	redirect();
}

// Check if the address book was clicked from the nav bar or the list page
$fromNav = ($ORIGINTYPE === "nav");
echo("origin: ".$ORIGINTYPE);
echo("fromNav:".$fromNav);

// set the pagename to jump to when editing selected contact
$addressPagename = $fromNav ? "address.php" : (($ORIGINTYPE === "manualadd") ? "addressmanualaddbook.php" : "addressmanualadd.php");


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions($row, $name) {
	global $addressPagename;
	return "<a href=\"$addressPagename?id=$row[0]\">Edit</a>&nbsp;|&nbsp;<a href=\"?delete=$row[0]\" onclick=\"return confirmDelete();\">Delete</a>";
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = "My Address Book " . ($fromNav ? '' : help('AddressBook_MyAddressBook'));

include_once("popup.inc.php");

if (!$fromNav && $_SESSION['listid'] == null) {
	echo "<font color=#FF0000>";
	print("Please make sure to first save your list before adding entries to it from your address book");
	echo "</font>";
} else {

	?>
	<script langauge="javascript">

	function dolistbox (img, type, init, id) {
		if (!img.toggleset) {
			img.toggleset = true;
			img.toggle = init;
		}
		img.toggle = !img.toggle;
		img.src = "checkbox.png.php?type=" + type + "&toggle=" + img.toggle + "&id=" + id + "&foo=" + new Date();
	}
	</script>

	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="padding-bottom: 5px;">
		<tr>
			<td align="left"><? buttons(button('done', $fromNav ? 'window.close(); ' : 'window.opener.document.location.reload(); window.close(); ')); ?></td>
			<td align="right" valign="bottom">
				<?= ($fromNav) ? '' : 'Select the individuals you want to add to your list.'?>
			</td>
		</tr>
	</table>
	<?
	startWindow('Addresses','padding: 3px;');
	?>
	<table border="0" cellpadding="0" cellspacing="0" style="margin-top: 3px;">
		<tr>
			<td><? button_bar(button('addcontact', NULL,"$addressPagename?id=new")); ?></td>
		</tr>
	</table>

	<?

	$first = FieldMap::getFirstNameField();
	$last = FieldMap::getLastNameField();
	$lang = FieldMap::getLanguageField();
	$table = Query("select person.id, pkey, $first, $last, $lang, phone, email,
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
						left join address on person.id = address.personid
						left join persondata on person.id = persondata.personid
					where userid = $USER->id and not deleted
					order by $last, $first
					");

	$data = array();
	$onpageids = array();
	while($row = DBGetRow($table)) {
		$data[] = $row;
		$onpageids[] = $row[0];
	}

	//fmt_checkbox_addrbook needs $inlistids to be set
	$listid = $_SESSION['listid'];
	$inlistids = array();
	if (count($onpageids) > 0) {
		$query = "
		select le.personid
		from listentry le
		where le.listid = $listid and le.type='A'
		and (le.personid = " . implode(" or le.personid =", $onpageids) . ")
		";
		$inlistids = QuickQueryList($query);
	}

	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';

	if (!$fromNav) {
		$titles = array(1 => "In List",
						2 => "First Name",
						3 => "Last Name",
						4 => "Language",
						5 => "Phone",
						6 => "Email",
						7 => "Address",
						8 => "Actions");
		$formatters = array(1 => "fmt_checkbox_addrbook",
							5 => "fmt_phone",
							6 => "fmt_email",
							8 => "fmt_actions");
	} else {
		$titles = array(2 => "First Name",
						3 => "Last Name",
						4 => "Language",
						5 => "Phone",
						6 => "Email",
						7 => "Address",
						8 => "Actions");
		$formatters = array(5 => "fmt_phone",
							6 => "fmt_email",
							8 => "fmt_actions");
	}

	showTable($data, $titles,$formatters);
	echo '</table>';

	endWindow();

	buttons();

} //end if no list

include_once("popupbottom.inc.php");
