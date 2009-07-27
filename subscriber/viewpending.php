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
	$id = $_GET['id'] +0;
	$pending = DBFind("SubscriberPending", "from subscriberpending where id=?", false, array($id));
}

if (!isset($pending) || !$pending) {
	error_log("viewpending with bad id, redirect to notificationprefs");
	redirect("notificationpreferences.php");
}

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
buttons(icon_button(_L("Done"), "tick", null, "notificationpreferences.php"));
endWindow();
require_once("navbottom.inc.php");
?>