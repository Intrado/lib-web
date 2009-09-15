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
				error('The email address is not valid.');
			} else {
				QuickQuery("BEGIN");
				$result = QuickUpdate("insert into blockeddestination(userid, description, destination, type, createdate)
							values (?, ?, ?, 'email', now())", false, array($USER->id, TrimFormData($form, $section, 'reason'), $email));
				QuickQuery("COMMIT");
				if ($result) {
					$reloadform = true;
					notice(_L("Emails for %s are now blocked.", escapehtml($email)));
				} else {
					error("An error occurred when saving the email address");
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
		return $row[$index];
	return "Recipient";
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:blocked";
$TITLE = "Blocked List";

include_once("nav.inc.php");

NewForm($form);
startWindow(_L('Systemwide Blocked Email') , 'padding: 3px;', false, true);
if ($ACCESS->getValue('callblockingperms') == 'addonly' || $ACCESS->getValue('callblockingperms') == 'editall') {
?>
	<table style="margin-top: 5px;" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td>Email: <? NewFormItem($form, $section, 'email', 'text',20,200); ?>&nbsp;&nbsp;</td>
			<td>Reason: <? NewFormItem($form, $section, 'reason', 'text',30,100); ?>&nbsp;&nbsp;</td>
			<td><?= submit($form, $section, 'Add'); ?></td>
		</tr>
	</table>
<?
} // End if

if ($ACCESS->getValue('callblockingperms') == 'editall' || $ACCESS->getValue('callblockingperms') == 'addonly') {
	$titles = array(
				"0" => '#Email Address',
				"1" => '#Reason for Blocking',
				"2" => '#Blocked by',
				"7" => 'Blocked on',
				"3" => 'Actions');
} else {
	$titles = array(
				"0" => '#Email Address',
				"1" => '#Reason for Blocking',
				"2" => '#Blocked by');
}

$formatters = array(
				"0" => 'fmt_email',
				"2" => 'fmt_blockedby',
				"3" => 'fmt_blocking_actions');

$result = Query(
		"select b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate
			from blockeddestination b
			left join user u on (b.userid = u.id)
			where b.type = 'email'
			order by b.id desc");
$data=array();
while ($row = DBGetRow($result)) {
	$data[] = $row;
}

echo '<table width="100%" cellpadding="3" cellspacing="1" class="list sortable" id="blocked_numbers">';
showTable($data, $titles, $formatters);
echo "\n</table>";

endWindow();
EndForm();

include_once("navbottom.inc.php");

?>
