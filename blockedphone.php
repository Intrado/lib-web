<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Phone.obj.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('blocknumbers')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	$blockinfo = QuickQueryRow("select `userid`, `destination`, `type` from blockeddestination where id = ?", true, false, array($deleteid));
	$ownerid = $blockinfo['userid'];
	if ($ACCESS->getValue('callblockingperms') == 'editall' ||
		($ACCESS->getValue('callblockingperms') == 'addonly' && $USER->id == $ownerid)) {
		QuickUpdate("delete from blockeddestination where id=?", false, array($deleteid));
		notice(_L("%1s for %2s are now unblocked.", $blockinfo['type'] == 'phone' ? escapehtml(_L('Phone calls')) : escapehtml(_L('Text messages')), escapehtml(Phone::format($blockinfo['destination']))));
	}
	redirect();
}

// add columns to display contact details
if (isset($_GET['displaycontact']) && $_GET['displaycontact'] == 'true')
	$shoulddisplaycontact = true;
else
	$shoulddisplaycontact = false;

// if csv download, else html
if (isset($_GET['csv']) && $_GET['csv'])
	$csv = true;
else
	$csv = false;


$form = "blockednumbers";
$section = "main";
$reloadform = false;

if(CheckFormSubmit($form, $section))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($form))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = true;
	}
	else
	{
		MergeSectionFormData($form, $section);

		//do check
		if( CheckFormSection($form, $section) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($ACCESS->getValue('callblockingperms') == 'editall' || $ACCESS->getValue('callblockingperms') == 'addonly') {
			$phone = Phone::parse(GetFormData($form, $section, 'number'));
			if (strlen($phone) != 10) {
				error('The phone number must be exactly 10 digits long (including area code)','You do not need to include a 1 for long distance');
			} else {
				$blocktype = GetFormData($form, $section, 'type');
				// check to see if this number already exists
				$exists = QuickQueryList("select type from blockeddestination where  destination = ?", false, false, array($phone));
				if ($exists && ($blocktype == 'both' || $blocktype == 'phone') && in_array('phone', $exists)) {
					error(_L('That number is already blocked from receiving phone calls'));
				} else if($exists && ($blocktype == 'both' || $blocktype == 'sms') && in_array('sms', $exists)) {
					error(_L('That number is already blocked from receiving text messages'));
				} else {
					QuickQuery("BEGIN");
					if($blocktype == 'both' || $blocktype == 'phone')
						$result = QuickUpdate("insert into blockeddestination (userid, description, destination, type, createdate)
									values (?, ?, ?, 'phone', now())", false, array($USER->id, TrimFormData($form, $section, 'reason'), $phone));
					if($blocktype == 'both' || $blocktype == 'sms')
						$result = QuickUpdate("insert into blockeddestination (userid, description, destination, type, createdate)
									values (?, ?, ?, 'sms', now())", false, array($USER->id, TrimFormData($form, $section, 'reason'), $phone));

					QuickQuery("COMMIT");

					if ($result) {
						$reloadform = true;
						if ($blocktype == 'both') {
							notice(_L("Both phone calls and text messages for %s are now blocked.", escapehtml(Phone::format($phone))));
						} else {
							notice(_L("%1s for %2s are now blocked.", $blocktype == 'phone' ? escapehtml(_L('Phone calls')) : escapehtml(_L('Text messages')), escapehtml(Phone::format($phone))));
						}
					} else {
						error("An error occurred when saving the phone number");
					}
				}
			}
		} else {
			error("You are not authorized to perform this operation");
		}
	}
} else {
	$reloadform = true;
}

if( $reloadform )
{
	ClearFormData($form);
	PutFormData($form, $section,"number", "", "text", 1, 20, true);
	PutFormData($form, $section,"reason", "", "text", 1, 100, true);
	PutFormData($form, $section,"type", "both", "text");
}

	
$formatters = array(
	"10" => "fmt_bntype",
	"4" => 'fmt_phone',
	"7" => 'fmt_blocking_actions',
	"1" => 'fmt_persontip');

$titles = array(
	"4" => '#Phone Number',
	"10" => "#Type",
	"5" => '#Reason for Blocking',
	"6" => '#Blocked by',
	"11" => 'Blocked on');

if ($ACCESS->getValue('callblockingperms') == 'editall' || $ACCESS->getValue('callblockingperms') == 'addonly') {
	$titles = $titles + array("7" => 'Actions');
}

if ($shoulddisplaycontact) {
	$personfields = array(
		"1" => _L("ID #"),
		"2" => _L("First Name"),
		"3" => _L("Last Name"));
	$titles = $personfields + $titles; // prepend the person fields, keeping the indecies in place
	
	// must have pid index 0, pkey index 1, for fmt_persontip to work
	$result = Query(
		"(select p.id, p.pkey, p.f01, p.f02,
			b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate 
		from blockeddestination b
		join user u on (u.id = b.userid)
		left join phone ph on (ph.phone = b.destination)
		left join person p on (p.id = ph.personid)
		where b.userid = u.id and b.type = 'phone'
		)
		union
		(select p.id, p.pkey, p.f01, p.f02,
			b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate 
		from blockeddestination b
		join user u on (u.id = b.userid)
		left join sms s on (s.sms = b.destination)
		left join person p on (p.id = s.personid)
		where b.userid = u.id and b.type = 'sms'
		)
		order by createdate desc, type");
	
} else {
	// must stub in dummy contact details for pid and pkey index order, if we do the same query with person details we get duplicate rows when multiple people share a phone
	$result = Query(
		"select 'pid', 'pkey', 'f01', 'f02', b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate
			from blockeddestination b
			join user u on (u.id = b.userid) 
			where b.userid = u.id and b.type in ('phone', 'sms')
			order by createdate desc, type");
}

$data = array();
while ($row = DBGetRow($result)) {
	$data[] = $row;
}

//////////////////////////////////
// Functions
/////////////////////////////////

function fmt_blocking_actions($row, $index) {
	global $USER, $shoulddisplaycontact;
	$id = $row[$index];
	$ownerid = $row[$index + 1];
	$perm = $row[$index + 2];
	
	if ($shoulddisplaycontact)
		$displaytruefalse = "true";
	else
		$displaytruefalse = "false";

	// Only show the delete link in 'addonly' mode for blocked calls created by this user
	if ($perm == 'editall' ||
		($perm == 'addonly' && $USER->id == $ownerid)) {
		return action_links(action_link(_L("Delete"),"cross","blockedphone.php?delete=$id&displaycontact=$displaytruefalse","return confirmDelete();"));
	} else {
		return '';
	}
}

function fmt_bntype ($row, $index) {
	if ($row[$index] == "sms")
		return "Text Messages";
	else
		return "Phone Calls";
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
if ($csv) {

	$titles = array(
		"4" => 'Phone Number',
		"10" => 'Type',
		"5" => 'Reason for Blocking',
		"6" => 'Blocked by',
		"11" => 'Blocked on');
	
	if ($shoulddisplaycontact) {
		$personfields = array(
			"1" => _L("ID #"),
			"2" => _L("First Name"),
			"3" => _L("Last Name"));
		$titles = $personfields + $titles; // prepend the person fields, keeping the indecies in place
	}
	
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=blockedphone.csv");
	header("Content-type: application/vnd.ms-excel");

	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
	// write column titles
	echo '"' . implode('","', $titles) . '"';
	echo "\r\n";
	
	// write out the rows of data
	foreach ($data as $row) {
		// [10] type
		if ($row[10]) {
			if ($row[10] == "sms")
				$row[10] = "Text Messages";
			else
				$row[10] = "Phone Calls";
		}
		// [4] destination number
		if ($row[4]) {
			$row[4] = Phone::format($row[4]);
		}
		
		if ($shoulddisplaycontact)
			$displaydata = array($row[1], $row[2], $row[3], $row[4], $row[10], $row[5], $row[6], $row[11]);
				else
			$displaydata = array($row[4], $row[10], $row[5], $row[6], $row[11]);
		
		echo '"' . implode('","', $displaydata) . '"';
		echo "\r\n";
	}
	
} else { // HTML view
	
$PAGE = "system:blocked";
$TITLE = "Blocked List";

include_once("nav.inc.php");

NewForm($form);
startWindow(_L('Systemwide Blocked Phone') . help('Blocked_SystemwideBlocked'), 'padding: 3px;', false, true);
?>
	<table style="margin-top: 5px;" border="0" cellpadding="0" cellspacing="0">
<?
if ($ACCESS->getValue('callblockingperms') == 'addonly' || $ACCESS->getValue('callblockingperms') == 'editall') {
?>
		<tr>
			<td>Phone: <? NewFormItem($form, $section, 'number', 'text',20,20); ?>&nbsp;&nbsp;</td>
			<td>
<?
				NewFormItem($form, $section,"type","selectstart");
				if(getSystemSetting("_hassms", false)){
					NewFormItem($form, $section,"type","selectoption","Block Calls and Text Messages","both");
					NewFormItem($form, $section,"type","selectoption","Block Text Messages only","sms");
					NewFormItem($form, $section,"type","selectoption","Block Calls only","phone");
				} else {
					NewFormItem($form, $section,"type","selectoption","Block Calls","both");
				}
				NewFormItem($form, $section,"type","selectend");

?>
			&nbsp;
			</td>
			<td>Reason: <? NewFormItem($form, $section, 'reason', 'text',30,100); ?>&nbsp;&nbsp;</td>
			<td><?= submit($form, $section, 'Add'); ?></td>
			<td><? print help('Blocked_Add', 'style="margin-left: 5px;"'); ?></td>
		</tr>
<? 
}
?>

		<tr>
			<td>
			<input type='checkbox' id='checkboxDisplayContact' onclick='location.href="?displaycontact=" + this.checked' <?=$shoulddisplaycontact ? 'checked' : ''?>><label for='checkboxDisplayContact'><?=_L('Display Contacts')?></label> 
			</td>
<?
if (!($ACCESS->getValue('callblockingperms') == 'addonly' || $ACCESS->getValue('callblockingperms') == 'editall')) {
?>
			<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<? 
}
?>
			<td>
			<a href='blockedphone.php?csv=true&displaycontact=<?= $shoulddisplaycontact ? "true" : "false" ?>'><?= _L("CSV Download"); ?></a>
			</td>
		</tr>
	</table>
<?

echo '<table width="100%" cellpadding="3" cellspacing="1" class="list sortable" id="blocked_numbers">';
showTable($data, $titles, $formatters);
echo "\n</table>";

endWindow();
EndForm();

include_once("navbottom.inc.php");
}


?>
