<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

// if not authorized, redirect
if (!$INBOUND_ACTIVATION)
	redirect("addcontact1.php");

$maxcontacts = 10;

global $oktogo;
$oktogo = true;

function checkIDStatus() {
	global $oktogo;
	$oktogo = false;

	// if we have some pkeys, find their status
	if (isset($_SESSION['phoneactivationpkeylist']) && count($_SESSION['phoneactivationpkeylist'])) {
		$result = portalCreatePhoneActivation($_SESSION['customerid'], $_SESSION["portaluserid"], array_keys($_SESSION['phoneactivationpkeylist']), false);
		if ($result['result'] == "") {
			$phones = $result['phonelist'];
			if (count($phones) == 1 && $phones[0] == "")
				$phones = array(); // empty the array of no phones
			if (count($phones))
				$oktogo = true;

			$pkeyresults = $result['pkeyresults'];
			foreach ($pkeyresults as $pair) {
				$pairsplit = explode(":", $pair);
				$status = $pairsplit[1];
				if ($status == "ok" && !count($phones))
					$status = "nophonematch";

				$_SESSION['phoneactivationpkeylist'][$pairsplit[0]] = $status;
				if ($status != "ok")
					$oktogo = false;
			}
		} else {
			// TODO error
			error_log("portalcreatephoneactivation failed ");
		}
	}
}


$f="addstudent";
$s="main";
$reloadform = 0;


if (CheckFormSubmit($f,$s) || CheckFormSubmit($f,'add')) {
	//check to see if formdata is valid
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);

		//do check
		if ( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (CheckFormSubmit($f, 'add') && (GetFormData($f, $s, "pkeyadd") == "")) {
			error('Please enter a Contact Identification Number');
		} else {
			$_SESSION['phoneactivationpkeylist'] = array(); // clear out any previous pkeys

			// save any changed pkeys
			$i = 0;
			while ($i < $maxcontacts) {
				$pkey = trim(GetFormData($f, $s, "pkey".$i));
				if ($pkey != "")
					$_SESSION['phoneactivationpkeylist'][$pkey] = "Unknown";
				$i++;
			}
			$pkey = trim(GetFormData($f, $s, "pkeyadd"));
			if ($pkey != "")
				$_SESSION['phoneactivationpkeylist'][$pkey] = "Unknown";

			// add another
			if (CheckFormSubmit($f, 'add')) {
				//$_SESSION['phoneactivationpkeylist'][""] = "Unknown";
			} else if (CheckFormSubmit($f, $s)) {
				// Next button, must verify all pkey status ok to continue
				checkIDStatus();
				if ($oktogo)
					redirect("phoneactivationcode.php");
			}
			$reloadform = 1;
		}
	}
} else {
	$reloadform = 1;
}

if ($reloadform) {
	ClearFormData($f);

	PutFormData($f, $s, "pkeyadd", "", "text", 1, 255, false); // Add new ID

	// existing IDs
	if (isset($_SESSION['phoneactivationpkeylist']) && count($_SESSION['phoneactivationpkeylist'])) {
		$i = 0;
		foreach (array_keys($_SESSION['phoneactivationpkeylist']) as $pkey) {
			PutFormData($f, $s, "pkey".$i, $pkey, "text", 1, 255, false);
			$i++;
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Contact Activation - Step 2";

include_once("nav.inc.php");
NewForm($f);


startWindow('Add Contact');
?>
<table cellpadding="3">

<? if (!$oktogo) { ?>
	<tr><td>There were problems with one or more of the ID numbers you entered.</td></tr>
	<tr><td>Try modifying or removing the ID numbers that are causing the errors.</td></tr>
	<tr><td>If you still experience problems try activating only one ID number at a time.</td></tr>

<?	} else { ?>

	<tr><td>Enter the ID numbers of the people you wish to add to your account.</td></tr>
	<tr><td>You can add multiple people to your account in a single call to our toll free number.</td></tr>
	<tr><td>Enter all of your ID numbers by using the Add More button.</td></tr>
	<tr><td>Clear any ID numbers you don't want to include, then click Next.</td></tr>
<?	} ?>

	<tr><td class="bottomBorder">&nbsp;</td></tr>

<tr><td><table border="1" cellpadding="3" cellspacing="0">
<th>&nbsp;</th><th>ID Number</th>
<? if (!$oktogo) { ?>
<th>Status</th>
<? } ?>

<? $i = 0; ?>
<?	foreach (array_keys($_SESSION['phoneactivationpkeylist']) as $pkey) { ?>
		<tr><td width="10%"><?=$i+1?></td><td align="center"><? NewFormItem($f, $s, "pkey".$i, "text", "20", "255"); ?></td>
<? if (!$oktogo) {
		$statustext = "Unknown";
		if ($_SESSION['phoneactivationpkeylist'][$pkey] == "notfound") {
			$statustext = "Error: Unable to locate this ID number in the system.";
		} else if ($_SESSION['phoneactivationpkeylist'][$pkey] == "nophone") {
			$statustext = "Error: There are no phone numbers on record for this ID number.";
		} else if ($_SESSION['phoneactivationpkeylist'][$pkey] == "notallow") {
			$statustext = "Error: This ID number is blocked from phone activation.  Contact the system administrator for ".escapehtml($_SESSION['custname'])." for assistance.";
		} else if ($_SESSION['phoneactivationpkeylist'][$pkey] == "nophonematch") {
			$statustext = "Error: This ID number cannot be activated along with the others you entered.";
		} else if ($_SESSION['phoneactivationpkeylist'][$pkey] == "ok") {
			$statustext = "OK";
		}
?>
		<td><?=$statustext?></td>
<? } ?>
		</tr>
<?		$i++;
	}
?>

<?		if ($i < $maxcontacts) { ?>
		<tr><td width="10%"><?=$i+1?></td><td align="center"><? NewFormItem($f, $s, "pkeyadd", "text", "20", "255"); $i++; ?></td>
		<td></td>
		</tr>
<?		} ?>

</table></td></tr>

	<tr><td> <? if ($i < $maxcontacts) echo submit($f, 'add', 'Add More'); ?>  </td></tr>

</table>
<?
endWindow();
buttons(submit($f, $s, 'Next'), button("Cancel", NULL, "addcontact3.php"));
EndForm();
include_once("navbottom.inc.php");
?>
