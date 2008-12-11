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
$pkeynotfound = array();
$pkeynophone = array();
$pkeynotallow = array();
$pkeyok = array();

$result = portalCreatePhoneActivation($_SESSION['customerid'], $_SESSION["portaluserid"], $_SESSION['phoneactivationpkeylist']);
if ($result['result'] == "") {
	$phones = explode(",", $result['phonelist']);
	if (count($phones) == 1 && $phones[0] == "")
		$phones = array(); // empty the array of no phones
	$code = $result['code'];
	$pkeyresults = explode(",", $result['pkeyresults']);
	foreach ($pkeyresults as $pair) {
		$pairsplit = explode(":", $pair);
		if ($pairsplit[1] == "notfound") {
			$pkeynotfound[] = $pairsplit[0];
		} else if ($pairsplit[1] == "nophone") {
			$pkeynophone[] = $pairsplit[0];
		} else if ($pairsplit[1] == "notallow") {
			$pkeynotallow[] = $pairsplit[0];
		} else if ($pairsplit[1] == "ok") {
			$pkeyok[] = $pairsplit[0];
		} else {
			error_log("bad pkey-result returned from authserver_createphoneactivation ".$pair);
		}
	}
} else {
	// TODO error
	error_log("portalcreatephoneactivation failed ");
}

$f="addstudent";
$s="main";
$reloadform = 0;


if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			redirect("addcontact3.php");
		}
	}
} else {
	$reloadform = 1;
}


if( $reloadform )
{
	ClearFormData($f);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Phone Selection";

include_once("nav.inc.php");
NewForm($f);
buttons(button("Done", NULL, "addcontact3.php"));


startWindow('Phone');
?>
<table>

<?	if (count($pkeynotfound) > 0) { ?>
		<tr>
			<td>The following contacts were not found in the system:</td>
		</tr>
<?		foreach ($pkeynotfound as $pkey) { ?>
			<tr><td><?=escapehtml($pkey) ?></td></tr>
<?		} ?>
<?	} ?>

<?	if (count($pkeynotallow) > 0) { ?>
		<tr>
			<td>The following contacts do not allow phone activation:</td>
		</tr>
<?		foreach ($pkeynotallow as $pkey) { ?>
			<tr><td><?=escapehtml($pkey) ?></td></tr>
<?		} ?>
<?	} ?>

<?	if (count($pkeynophone) > 0) { ?>
		<tr>
			<td>The following contacts have no phone information in the system:</td>
		</tr>
<?		foreach ($pkeynophone as $pkey) { ?>
			<tr><td><?=escapehtml($pkey) ?></td></tr>
<?		} ?>
<?	} ?>

<?	if (count($phones) > 0 && count($pkeyok) > 0) { ?>
		<tr>
			<td>The following contacts will be added:</td>
		</tr>
<?		foreach ($pkeyok as $pkey) { ?>
			<tr><td><?=escapehtml($pkey) ?></td></tr>
<?		} ?>
		<tr>
			<td>You must follow these steps to complete the activation of these contacts by phone.</td>
		</tr>
		<tr>
			<td>Step 1. Call <?echo Phone::format($INBOUND_ACTIVATION) ?> from one of the following phones.</td>
		</tr>
<?		foreach ($phones as $phone) { ?>
		<tr>
			<td> <? echo "&nbsp;&nbsp;&nbsp;&nbsp;(xxx)xxx-x".substr($phone, 7, 10); ?> </td>
		</tr>
<?		} ?>
		<tr>
			<td>Please note, if your phone service has caller identification blocked, you must first dial *82 to unblock it for this call.</td>
		</tr>
		<tr>
			<td>Step 2. When prompted, select option 2 to activate contacts.</td>
		</tr>
		<tr>
			<td>Step 3. When prompted, enter this code  <?=escapehtml($code) ?></td>
		</tr>
		<tr>
			<td>Step 4. When the call is completed, return to the Contacts page to view and edit your preferences.</td>
		</tr>

<?	} else { ?>
		<tr>
			<td>There are no contacts available for phone activation.  They do not share a common number, you may try to enter one contact at a time, or please contact support.</td>
		</tr>
<?		foreach ($pkeyok as $pkey) { ?>
			<tr><td><?=escapehtml($pkey) ?></td></tr>
<?		} ?>

<?	} ?>

</table>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>