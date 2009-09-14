<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('blocknumbers')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:blocked";
$TITLE = "Blocked Lists";

require_once("nav.inc.php");
startWindow("Blocked Destination Types");
	?>
	<div style="padding-top: 8px">
	<table border="1" width="100%" cellpadding="3" cellspacing="1" class="list">
		<tr class="listHeader">
			<th align="left" class="nosort">Phone Numbers</th>
			<th align="left" class="nosort">Emails</th>
		</tr>
		<tr>
			<td><a href="blockedphone.php">Phone Calls / Text Messages</a></td>
			<td><a href="blockedemail.php">Email Addresses</a></td>
		</tr>
	</table>
	</div>
	<?
endWindow();
require_once("navbottom.inc.php");
?>
