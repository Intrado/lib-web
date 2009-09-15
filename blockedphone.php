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
				QuickQuery("BEGIN");
				$blocktype = GetFormData($form, $section, 'type');
				if ($blocktype == 'both') {
					$result = QuickUpdate("insert into blockeddestination(userid, description, destination, type, createdate)
								values ($USER->id, '" .
								DBSafe(GetFormData($form, $section, 'reason')) . "', '$phone','phone', now())");
					$result = QuickUpdate("insert into blockeddestination(userid, description, destination, type, createdate)
								values ($USER->id, '" .
								DBSafe(GetFormData($form, $section, 'reason')) . "', '$phone','sms', now())");
				} else {
					$result = QuickUpdate("insert into blockeddestination(userid, description, destination, type, createdate)
								values ($USER->id, '" .
								DBSafe(GetFormData($form, $section, 'reason')) . "', '$phone','" .
								DBSafe($blocktype) . "', now())");
				}
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


function fmt_blocking_actions($row, $index) {
	global $USER;
	$id = $row[$index];
	$ownerid = $row[$index + 1];
	$perm = $row[$index + 2];

	// Only show the delete link in 'addonly' mode for blocked calls created by this user
	if ($perm == 'editall' ||
		($perm == 'addonly' && $USER->id == $ownerid)) {
		return action_links(action_link(_L("Delete"),"cross","blockedphone.php?delete=$id","return confirmDelete();"));
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

$PAGE = "system:blocked";
$TITLE = "Blocked List";

include_once("nav.inc.php");

NewForm($form);
startWindow(_L('Systemwide Blocked Phone') . help('Blocked_SystemwideBlocked'), 'padding: 3px;', false, true);
if ($ACCESS->getValue('callblockingperms') == 'addonly' || $ACCESS->getValue('callblockingperms') == 'editall') {
?>
	<table style="margin-top: 5px;" border="0" cellpadding="0" cellspacing="0">
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
	</table>
<?
} // End if

if ($ACCESS->getValue('callblockingperms') == 'editall' || $ACCESS->getValue('callblockingperms') == 'addonly') {
	$titles = array(
				"0" => '#Phone Number',
				"6" => "#Type",
				"1" => '#Reason for Blocking',
				"2" => '#Blocked by',
				"7" => 'Blocked on',
				"3" => 'Actions');
} else {
	$titles = array(
				"0" => '#Phone Number',
				"6" => "#Type",
				"1" => '#Reason for Blocking',
				"2" => '#Blocked by');
}

$formatters = array(
				"6" => "fmt_bntype",
				"0" => 'fmt_phone',
				"3" => 'fmt_blocking_actions');

$result = Query(
		"select b.destination, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission, b.type, b.createdate
			from blockeddestination b, user u
			where b.userid = u.id
			and b.type in ('phone', 'sms')
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
