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
	<div style="padding: 8px">
		<a href="blockedphone.php">Phone Calls / Text Messages</a><br>
		<a href="blockedemail.php">Email Addresses</a>
	</div>
	<?
endWindow();
require_once("navbottom.inc.php");
?>
