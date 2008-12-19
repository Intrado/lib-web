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
$TITLE = "Contact Activation - Step 3";

include_once("nav.inc.php");
NewForm($f);


startWindow('Phone Activation');
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">

<?	// OK section
	if (count($phones) > 0 && count($pkeyok) > 0) { ?>
	<tr>
		<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Allowed:</th>
		<td>
			<table>
			<tr>
				<td>The people with the following ID Numbers may be added by following the confirmation steps below.</td>
			</tr>
			<tr><td><table border="1" cellpadding="3" cellspacing="0" width="10%">
<?			foreach ($pkeyok as $pkey) { ?>
				<tr><td align="center"><b><?=escapehtml($pkey) ?></b></td></tr>
<?			} ?>
			</td></tr></table>
			<tr><td>&nbsp;</td></tr>
			</table>
		</td>
	</tr>
<?	} ?>



<?	// Warning section
	if ((count($pkeynotfound) > 0) ||
		(count($pkeynotallow) > 0) ||
		(count($pkeynophone) > 0)) { ?>
	<tr>
		<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Not Allowed:</th>
		<td>
		<table>
		<tr>
			<td>The people with the following ID Numbers cannot be added to your account for the following reasons:</td>
		</tr>

		<tr><td><table border="1" cellpadding="3" cellspacing="0" width="100%">

<?	if (count($pkeynotfound) > 0) { ?>
<?		foreach ($pkeynotfound as $pkey) { ?>
			<tr><td align="center"><b><?=escapehtml($pkey) ?></b></td><td>ID Number was not found in the system</td></tr>
<?		} ?>
<?	} ?>

<?	if (count($pkeynotallow) > 0) { ?>
<?		foreach ($pkeynotallow as $pkey) { ?>
			<tr><td align="center"><b><?=escapehtml($pkey) ?></b></td><td>Person with this ID Number has been blocked by the system administrator</td></tr>
<?		} ?>
<?	} ?>

<?	if (count($pkeynophone) > 0) { ?>
<?		foreach ($pkeynophone as $pkey) { ?>
			<tr><td align="center"><b><?=escapehtml($pkey) ?></b></td><td>There are no phone numbers on record</td></tr>
<?		} ?>
<?	} ?>
		</td></tr></table>
		<tr><td>&nbsp;</td></tr>
		</table>
		</td>
	</tr>
<?	} ?>


<?	// Steps to proceed section
	if (count($phones) > 0 && count($pkeyok) > 0) { ?>
	<tr>
		<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Confirmation Steps:</th>
		<td>
		<table>
		<tr>
			<td>You must follow these steps within 24 hours to add the allowed people to your account.<BR><BR></td>
		</tr>
		<tr><td><table cellpadding="2">
		<tr>
			<td valign="top">Step 1.</td><td>Call <font color="blue"><?echo Phone::format($INBOUND_ACTIVATION) ?> </font> from one of the phones listed.<br>
			For security reasons, we have hidden parts of your phone numbers with "xxx".
			</td>
		</tr>
<?		foreach ($phones as $phone) { ?>
		<tr>
			<td>&nbsp;</td><td> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo "(xxx)xxx-x".substr($phone, 7, 10); ?> </td>
		</tr>
<?		} ?>
		<tr>
			<td>&nbsp;</td><td>[NOTE] If your phone service has caller identification blocked, you must first dial *82 to unblock it for this call.</td>
		</tr>
		<tr>
			<td>Step 2.</td><td>When prompted, select option 2 to activate the ID Numbers you have added.</td>
		</tr>
		<tr>
			<td>Step 3.</td><td>When prompted, enter this activation code  <span style="font-weight:bold; font-size: 140%;"><?=escapehtml($code) ?></span></td>
		</tr>
		<tr>
			<td>Step 4.</td><td>When the call is complete, click the 'Contacts' tab to edit your notification preferences.</td>
		</tr>
		</table>
		</td>
	</tr>

<?	// else no matching phones on record
	} else { ?>
	<tr>
		<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">No Match:</th>
		<td>
		<table>
		<tr>
			<td>Sorry, there are no people available for phone confirmation.</td>
		</tr>
<?		foreach ($pkeyok as $pkey) { ?>
			<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;<b><?=escapehtml($pkey) ?></b></td></tr>
<?		} ?>
		<tr>
			<td><BR>All people being added must share a common phone number in the system database.</td>
		</tr>
		<tr>
			<td>This phone must be used to call the toll free service used by the confirmation process.</td>
		</tr>
		<tr>
			<td><BR>You may try again by entering a single ID Number.</td>
		</tr>
		<tr>
			<td>Or, please contact your system administrator at <?=escapehtml($_SESSION['custname']);?>.  Thank you.</td>
		</tr>
		</table>
		</td>
	</tr>
<?	} ?>

</table>
<?
endWindow();
buttons(button("Done", NULL, "addcontact3.php"));
EndForm();
include_once("navbottom.inc.php");
?>