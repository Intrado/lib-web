<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
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
	$blockinfo = QuickQueryRow("select `userid`, `destination` from blockeddestination where id = ?", true, false, array($deleteid));
	$ownerid = $blockinfo['userid'];
	if ($ACCESS->getValue('callblockingperms') == 'editall' ||
		($ACCESS->getValue('callblockingperms') == 'addonly' && $USER->id == $ownerid)) {
		QuickUpdate("delete from blockeddestination where id='$deleteid'");
		notice(_L("Emails for %s are now unblocked.", escapehtml($blockinfo['destination'])));
	}
	redirect();
}

// should add columns to display contact details?
if (isset($_GET['displaycontact'])) {
	if ($_GET['displaycontact'] == 'true')
		$_SESSION['shouldblockeddisplaycontact'] = true;
	else
		$_SESSION['shouldblockeddisplaycontact'] = false;
} else if (isset($_SESSION['shouldblockeddisplaycontact']) && $_SESSION['shouldblockeddisplaycontact'] == true)
	$_SESSION['shouldblockeddisplaycontact'] = true;
else
	$_SESSION['shouldblockeddisplaycontact'] = false;

// if csv download, else html
if (isset($_GET['csv']))
	$csv = true;
else
	$csv = false;

	
$form = "blockedemail";
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
			$email = TrimFormData($form, $section, 'email');
			if (strlen($email) > 200) {
				error('The email address cannot be more than 200 characters in length.');
			} else if (!validEmail($email)) {
				error(_L('The email address is not valid'));
			} else {
				QuickQuery("BEGIN");
				// Check to see if the email already exists
				$exists = QuickQuery("select count(id) from blockeddestination where type = 'email' and blockmethod = 'manual' and destination = ?", false, array($email));
				if ($exists) {
					error(_L('That email is already blocked'));
				} else {
					$description = TrimFormData($form, $section, 'reason');
					$result = QuickUpdate("insert into blockeddestination(userid, description, destination, type, createdate, blockmethod)
								values (?, ?, ?, 'email', now(), 'manual') on duplicate key update userid = ?, description = ?, createdate = now(), failattempts = null, blockmethod = 'manual'",
								false, array($USER->id, $description, $email, $USER->id, $description));
					QuickQuery("COMMIT");
					if ($result) {
						$reloadform = true;
						notice(_L("Emails for %s are now blocked.", escapehtml($email)));
					} else {
						error("An error occurred when saving the email address");
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
	PutFormData($form, $section,"email", "", "text", 1, 200, true);
	PutFormData($form, $section,"reason", "", "text", 1, 100, true);
}


$titles = array(
			"4" => '#Email Address',
			"5" => '#Reason for Blocking',
			"6" => '#Blocked by',
			"11" => 'Blocked on'); // date sort does not work with paging

if ($ACCESS->getValue('callblockingperms') == 'editall' || $ACCESS->getValue('callblockingperms') == 'addonly') {
	$titles = $titles + array("7" => 'Actions');
}

$formatters = array(
			"4" => 'fmt_email',
			"6" => 'fmt_blockedby',
			"7" => 'fmt_blocking_actions',
			"1" => 'fmt_persontip');

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 500;

if ($_SESSION['shouldblockeddisplaycontact']) {
	$personfields = array(
		"1" => _L("ID #"),
		"2" => _L("First Name"),
		"3" => _L("Last Name"));
	$titles = $personfields + $titles; // prepend the person fields, keeping the indecies in place
	
	// must have pid index 0, pkey index 1, for fmt_persontip to work
	$dataquery = "select SQL_CALC_FOUND_ROWS p.id, p.pkey, p.f01, p.f02,
			b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate, b.failattempts, b.blockmethod 
		from blockeddestination b
		join user u on (u.id = b.userid)
		left join email e on (e.email = b.destination)
		left join person p on (p.id = e.personid)
		where b.userid = u.id and b.type = 'email'
		and b.blockmethod in ('autoblock', 'manual')
		order by createdate desc";
			
} else {
	// must stub in dummy contact details for pid and pkey index order, if we do the same query with person details we get duplicate rows when multiple people share a phone
	$dataquery = "select SQL_CALC_FOUND_ROWS 'pid', 'pkey', 'f01', 'f02', b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate, b.failattempts, b.blockmethod
			from blockeddestination b
			join user u on (b.userid = u.id)
			where b.userid = u.id and b.type = 'email'
			and b.blockmethod in ('autoblock', 'manual')
			order by createdate desc";
}


///////////////////////////////
// Functions
//////////////////////////////

function fmt_blocking_actions($row, $index) {
	global $USER;
	$id = $row[$index];
	$ownerid = $row[$index + 1];
	$perm = $row[$index + 2];

	// Only show the delete link in 'addonly' mode for blocked calls created by this user
	if ($perm == 'editall' ||
		($perm == 'addonly' && $USER->id == $ownerid)) {
		return action_links(action_link(_L("Delete"),"cross","blockedemail.php?delete=$id","return confirmDelete();"));
	} else {
		return '';
	}
}

function fmt_blockedby($row, $index) {
	if ($row[$index])
		return escapehtml($row[$index]);
	else if ($row[9] == "autoblock")
		return "Auto-Blocked";
	return "Recipient";
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
if ($csv) {
	
	$titles = array(
			"4" => 'Email Address',
			"5" => 'Reason for Blocking',
			"6" => 'Blocked by',
			"11" => 'Blocked on');

	if ($_SESSION['shouldblockeddisplaycontact']) {
		$personfields = array(
			"1" => _L("ID #"),
			"2" => _L("First Name"),
			"3" => _L("Last Name"));
		$titles = $personfields + $titles; // prepend the person fields, keeping the indecies in place
	}
	
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=blockedemail.csv");
	header("Content-type: application/vnd.ms-excel");

	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
	// write column titles
	echo '"' . implode('","', $titles) . '"';
	echo "\r\n";

	$limit = 1000;
	$start = 0;
	$data = QuickQueryMultiRow($dataquery .  " limit $start, $limit");
	
	while (count($data) > 0) {
	
		// write out the rows of data
		foreach ($data as $row) {
			// [6] blocked by
			if ($row[6])
				$row[6] = $row[6];
			else if ($row[9] == "autoblock")
				$row[6] = "Auto-Blocked";
			else
				$row[6] = "Recipient";
		
			if ($_SESSION['shouldblockeddisplaycontact'])
				$displaydata = array($row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[11]);
			else
				$displaydata = array($row[4], $row[5], $row[6], $row[11]);
		
			echo '"' . implode('","', $displaydata) . '"';
			echo "\r\n";
		}
				
		$start += $limit;
		$data = QuickQueryMultiRow($dataquery .  " limit $start, $limit");
	}
	
} else { // HTML view
	
$data = QuickQueryMultiRow($dataquery .  " limit $start, $limit");
$total = QuickQuery("select FOUND_ROWS()");
	
$PAGE = "system:blocked";
$TITLE = "Blocked List";

include_once("nav.inc.php");

NewForm($form);
startWindow(_L('Systemwide Blocked Email') , 'padding: 3px;', false, true);
?>
	<table style="margin-top: 5px;" border="0" cellpadding="0" cellspacing="0">
<?
if ($ACCESS->getValue('callblockingperms') == 'addonly' || $ACCESS->getValue('callblockingperms') == 'editall') {
?>
		<tr>
			<td>Email: <? NewFormItem($form, $section, 'email', 'text',20,200); ?>&nbsp;&nbsp;</td>
			<td>Reason: <? NewFormItem($form, $section, 'reason', 'text',30,100); ?>&nbsp;&nbsp;</td>
			<td><?= submit($form, $section, 'Add'); ?></td>
		</tr>
<? 
}
?>
		<tr>
			<td>
			<input type='checkbox' id='checkboxDisplayContact' onclick='location.href="?displaycontact=" + this.checked + "&pagestart=" + "<? echo($start); ?>"' <?=$_SESSION['shouldblockeddisplaycontact'] ? 'checked' : ''?>><label for='checkboxDisplayContact'><?=_L('Display Contacts')?></label> 
			</td>
<?
if (!($ACCESS->getValue('callblockingperms') == 'addonly' || $ACCESS->getValue('callblockingperms') == 'editall')) {
?>
			<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<? 
}
?>
			<td>
			<a href='blockedemail.php?csv'><?= _L("CSV Download"); ?></a>
			</td>
		</tr>
	</table>
<?

showPageMenu($total, $start, $limit);
echo '<table width="100%" cellpadding="3" cellspacing="1" class="list sortable" id="blocked_numbers">';
showTable($data, $titles, $formatters);
echo "\n</table>";
showPageMenu($total, $start, $limit);

endWindow();
EndForm();

include_once("navbottom.inc.php");
}
?>
