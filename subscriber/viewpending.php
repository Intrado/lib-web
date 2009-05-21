<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("../obj/SubscriberPending.obj.php");
require_once("subscriberutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	$temp = $_GET['id'];
	$id = substr($temp, 7);
	$pending = new SubscriberPending($id);
}

// TODO what if id is bad?




////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:notificationprefs";
$TITLE = _L("Notification Preferences");

require_once("nav.inc.php");

startWindow(_L('Pending'));
if ($pending->type == 'email') {
	echo getEmailReview($pending->value);
} else {
	echo getPhoneReview($pending->value, $pending->token);
}
buttons(icon_button("Done", "tick", null, "notificationpreferences.php"));
endWindow();
require_once("navbottom.inc.php");
?>