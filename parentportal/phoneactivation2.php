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
buttons(button("Done", NULL, "addcontact3.php"));


startWindow('Phone Confirmation');
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">

<?	// OK section
	if (count($phones) > 0 && count($pkeyok) > 0) { ?>
	<tr>
		<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Add People:</th>
		<td>
			<table>
			<tr>
				<td>The people with the following ID Numbers will be added:</td>
			</tr>
<?			foreach ($pkeyok as $pkey) { ?>
				<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;<b><?=escapehtml($pkey) ?></b></td></tr>
<?			} ?>
			</table>
		</td>
	</tr>
<?	} ?>



<?	// Warning section
	if ((count($pkeynotfound) > 0) ||
		(count($pkeynotallow) > 0) ||
		(count($pkeynophone) > 0)) { ?>
	<tr>
		<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Skip People:</th>
		<td>
		<table>
<?	if (count($pkeynotfound) > 0) { ?>
		<tr>
			<td>The people with the following ID Numbers were not found in the system:</td>
		</tr>
<?		foreach ($pkeynotfound as $pkey) { ?>
			<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;<b><?=escapehtml($pkey) ?></b></td></tr>
<?		} ?>
<?	} ?>

<?	if (count($pkeynotallow) > 0) { ?>
		<tr>
			<td>The people with the following ID Numbers do not allow phone confirmation:</td>
		</tr>
<?		foreach ($pkeynotallow as $pkey) { ?>
			<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;<b><?=escapehtml($pkey) ?></b></td></tr>
<?		} ?>
<?	} ?>

<?	if (count($pkeynophone) > 0) { ?>
		<tr>
			<td>The people with the following ID Numbers have no phone information in the system:</td>
		</tr>
<?		foreach ($pkeynophone as $pkey) { ?>
			<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;<b><?=escapehtml($pkey) ?></b></td></tr>
<?		} ?>
<?	} ?>

		</table>
		</td>
	</tr>
<?	} ?>


<?	// Steps to proceed section
	if (count($phones) > 0 && count($pkeyok) > 0) { ?>
	<tr>
		<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Confirm:</th>
		<td>
		<table>
		<tr>
			<td>You must follow these steps to confirm adding these people to your account.<BR><BR></td>
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
			<td>Step 3. When prompted, enter this code  <span style="font-weight:bold; font-size: 140%;"><?=escapehtml($code) ?></span></td>
		</tr>
		<tr>
			<td>Step 4. When the call is completed, click the 'Contacts' tab above to view and edit your notification preferences.</td>
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
buttons();
EndForm();
include_once("navbottom.inc.php");
?>