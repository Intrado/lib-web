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

// NOTE page must be forwarded from the phoneactivationcode to provide the code and phone list
// avoids page reload from calling authserver again

$code = $_SESSION['phoneactivationcode'];
$pkeyok = $_SESSION['phoneactivationokpkeylist'];
$phones = $_SESSION['phoneactivationokphonelist'];

$f="addstudent";
$s="main";
$reloadform = 0;

if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error(_L('Form was edited in another window, reloading data'));
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check

		if( CheckFormSection($f, $s) ) {
			error(_L('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly'));
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
$TITLE = _L("Contact Activation - Step 3");

include_once("nav.inc.php");
NewForm($f);

startWindow(_L('Phone Activation'));
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">

<?	if (count($phones) > 0 && count($pkeyok) > 0) { ?>
	<tr>
		<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">People to Add:</th>
		<td>
			<table>
			<tr>
				<td>
<?				if (count($pkeyok) > 1) { ?>
					<?=_L("The people with the following ID Numbers can be added by following the confirmation steps below.")?>
<?				} else { ?>
					<?=_L("The person with the following ID Number can be added by following the confirmation steps below.")?>
<?				} ?>
				</td>
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

	<tr>
		<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;"><?=_L("Confirmation Steps")?>:</th>
		<td>
		<table>
		<tr>
			<td>
<?			if (count($pkeyok) > 1) { ?>
				<?=_L("You must follow these steps within <b>24 hours</b> to add the above people to your account.")?>
<?			} else { ?>
				<?=_L("You must follow these steps within <b>24 hours</b> to add the above person to your account.")?>
<?			} ?>
			<BR><BR></td>
		</tr>
		<tr>
			<td><? echo button(_L("Print this page now"), "window.print()"); ?> <BR><BR><BR></td>
		</tr>
		<tr><td><table cellpadding="2">
		<tr>
			<td valign="top"><?=_L("Step 1.")?></td>
			<td>
<?			if (count($phones) > 1) { ?>
				<?=_L("You must call from one of the phones listed below in order to verify your caller ID with our records.")?><br><br>
				<?=_L('For security reasons, we have hidden parts of your phone numbers with "xxx".')?>
<?			} else { ?>
				<?=_L("You must call from the phone listed below in order to verify your caller ID with our records.")?><br><br>
				<?=_L('For security reasons, we have hidden parts of your phone number with "xxx".')?>
<?			} ?>
			</td>
		</tr>

		<tr><td>&nbsp;</td><td><table border="1" cellpadding="3" cellspacing="0" width="30%">
<?		foreach ($phones as $phone) { ?>
		<tr>
			<td> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo "(xxx)xxx-x<b>".substr($phone, 7, 10)."</b>"; ?> </td>
		</tr>
<?		} ?>
		</td></tr></table>

		<tr>
			<td>&nbsp;</td><td><img src="img/bug_lightbulb.gif" > <?=_L("If your phone service has caller identification blocked, you must first dial *82 to unblock it for this call.")?></td>
		</tr>
		<tr>
			<td><?=_L('Step 2')?>.</td><td><?=_L('Call')?> <b><?echo Phone::format($INBOUND_ACTIVATION) ?> </b></td>
		</tr>
		<tr>
			<td><?=_L('Step 3')?>.</td><td><?=_L("When prompted, select option 2.")?></td>
		</tr>
		<tr>
			<td><?=_L('Step 4')?>.</td><td><?=_L("When prompted, enter this activation code ") . '<span style="font-weight:bold; font-size: 140%;">' . escapehtml($code) . '</span>'?></td>
		</tr>
		<tr>
			<td><?=_L('Step 5')?>.</td><td><?=_L("When the call is complete, log back into your Contact Manager account to edit your notification preferences.")?></td>
		</tr>
		</table>
		</td>
		</tr>
		</table>
		</td>
	</tr>

<?	} else { ?>

	<tr><td>
		<?=_L("Sorry, an unexpected error occured.  Please go back and try again.")?>
	</td></tr>

<?	} ?>

</table>

<?
endWindow();
buttons(button(_L("Back"), NULL, "phoneactivation1.php"), button(_L("Done"), NULL, "addcontact3.php"));
EndForm();
include_once("navbottom.inc.php");
?>
