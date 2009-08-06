<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
include_once("../obj/Phone.obj.php");
include_once("authportal.inc.php");

// if not authorized, redirect
if (!$INBOUND_ACTIVATION)
	redirect("addcontact1.php");

$phones = array();
$code = "";
$pkeyok = array();

if ($_SESSION['doubleclick'] && $_SESSION['phoneactivationcode']) {
	redirect("phoneactivation2.php");
} else {
$result = portalCreatePhoneActivation($_SESSION['customerid'], $_SESSION['portaluserid'], array_keys($_SESSION['phoneactivationpkeylist']), true);
if ($result['result'] == "") {
	$phones = $result['phonelist'];
	if (count($phones) == 1 && $phones[0] == "")
		$phones = array(); // empty the array of no phones
	$code = $result['code'];
	$pkeyresults = $result['pkeyresults'];
	foreach ($pkeyresults as $pair) {
		$pairsplit = explode(":", $pair);
		if ($pairsplit[1] == "ok") {
			$pkeyok[] = $pairsplit[0];
		}
	}
	$_SESSION['phoneactivationcode'] = $code;
	$_SESSION['phoneactivationokpkeylist'] = $pkeyok;
	$_SESSION['phoneactivationokphonelist'] = $phones;
	redirect("phoneactivation2.php");
}
}

error_log("portalcreatephoneactivation failed for customer ".$_SESSION['customerid']." and portaluser ".$_SESSION['portaluserid']);

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Contact Activation - ERROR";

include_once("nav.inc.php");
NewForm("addstudent");
startWindow(_L('Phone Activation'));
?>

<BR><?=_L("Sorry, an unexpected error occured.  Please go back and try again.")?><BR><BR>

<?
endWindow();
buttons(button(_L("Back"), NULL, "phoneactivation1.php"));
EndForm();
include_once("navbottom.inc.php");

?>

