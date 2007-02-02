<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Phone.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
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
	$ownerid = QuickQuery("select userid from blockednumber where id = '$deleteid'");
	if ($ACCESS->getValue('callblockingperms') == 'editall' ||
		($ACCESS->getValue('callblockingperms') == 'addonly' && $USER->id == $ownerid)) {
		QuickUpdate("delete from blockednumber where id='$deleteid'");
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
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
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
				$result = QuickUpdate("insert into blockednumber(userid, customerid, description, pattern)
								values ($USER->id, $USER->customerid, '" .
								DBSafe(GetFormData($form, $section, 'reason')) . "', '$phone')");
				if ($result) {
					ClearFormData($form);
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
	PutFormData($form, $section,"number", "", "text", 1, 20);
	PutFormData($form, $section,"reason", "", "text", 1, 100);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:blockednumbers";
$TITLE = "Blocked Numbers List";

include_once("nav.inc.php");

NewForm($form);
startWindow('Systemwide Blocked Phone Numbers ' . help('Blocked_SystemwideBlocked', NULL, 'blue'), 'padding: 3px;', false, true);
if ($ACCESS->getValue('callblockingperms') == 'addonly' || $ACCESS->getValue('callblockingperms') == 'editall') {
?>
	<table style="margin-top: 5px;" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td>Number: <? NewFormItem($form, $section, 'number', 'text',20,20); ?>&nbsp;&nbsp;</td>
			<td>Reason: <? NewFormItem($form, $section, 'reason', 'text'); ?>&nbsp;&nbsp;</td>
			<td><?= submit($form, $section, 'add', 'add'); ?></td>
			<td><? print help('Blocked_Add', 'style="margin-left: 5px;"'); ?></td>
		</tr>
	</table>
<?
} // End if

endWindow();
EndForm();

if ($ACCESS->getValue('callblockingperms') == 'editall' || $ACCESS->getValue('callblockingperms') == 'addonly') {
	$titles = array(
				"0" => '#Phone Number',
				"1" => '#Reason for Blocking',
				"2" => '#Blocked by',
				"3" => 'Actions');
} else {
	$titles = array(
				"0" => '#Phone Number',
				"1" => '#Reason for Blocking',
				"2" => '#Blocked by');
}

$formatters = array(
				"0" => 'fmt_phone',
				"3" => 'fmt_blocking_actions');

$result = Query(
		"select b.pattern, b.description, CONCAT(u.firstname, ' ', u.lastname) as fullname, b.id, b.userid, '" .
			$ACCESS->getValue('callblockingperms') . "' as permission
			from blockednumber b, user u
			where b.customerid = {$USER->customerid} and b.userid = u.id
			order by b.id desc");

while ($row = DBGetRow($result)) {
	$data[] = $row;
}

echo '<table width="100%" cellpadding="3" cellspacing="1" class="list sortable" id="blocked_numbers">';
showTable($data, $titles, $formatters);
echo "\n</table>";

include_once("navbottom.inc.php");

function fmt_blocking_actions($row, $index) {
	global $USER;
	$id = $row[$index];
	$ownerid = $row[$index + 1];
	$perm = $row[$index + 2];

	// Only show the delete link in 'addonly' mode for blocked calls created by this user
	if ($perm == 'editall' ||
		($perm == 'addonly' && $USER->id == $ownerid)) {
		return "<a href=\"blocked.php?delete=$id\" onclick=\"return confirmDelete();\">Delete</a>";
	} else {
		return '';
	}
}

?>