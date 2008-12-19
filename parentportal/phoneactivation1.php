<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

// if not authorized, redirect
if (!$INBOUND_ACTIVATION)
	redirect("addcontact1.php");


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
			//submit changes

			// all done
			if (GetFormData($f, $s, "radioselect") == "havenone") {
				redirect("phoneactivation2.php");
			} else {
				// add another
				$pkey = GetFormData($f, $s, "pkey");
				if ($pkey == "")
					error("Please enter a Contact Identification Number");

				$_SESSION['phoneactivationpkeylist'][] = $pkey;
				$reloadform = 1;
			} // end radio selection bycode
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	PutFormData($f, $s, "radioselect", "havenone");

	PutFormData($f, $s, "pkey", "", "text", 1, 255, false);
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
<table>
	<tr>
		<td>
		The following people will be added once the phone confirmation is complete.
		</td>
	</tr>

<tr><td><table border="1" cellpadding="3" cellspacing="0" width="70%">
<th>&nbsp;</th><th>ID Number</th>

<? $i = 1; ?>
<?	foreach ($_SESSION['phoneactivationpkeylist'] as $pkey) { ?>
		<tr><td width="10%"><?=$i++?></td><td align="center"><b><?=escapehtml($pkey) ?></b></td></tr>
<?	} ?>
</td></tr></table>
	<tr><td class="bottomBorder">&nbsp;</td></tr>
	<tr><td>You may enter one or more people to your account with a single phone call to our toll free number.</td></tr>
	<tr><td>Do you have another ID Number to enter now?</td></tr>
	<tr>
		<td>
			<? NewFormItem($f, $s, "radioselect", "radio", null, "havemore", "onclick=\"document.getElementById('pkeybox').disabled=false\""); ?> Yes, add another ID Number:
			<? NewFormItem($f, $s, "pkey", "text", "20", "255", "id=\"pkeybox\" disabled=false"); ?>
		</td>
	</tr>
	<tr>
		<td>
			<? NewFormItem($f, $s, "radioselect", "radio", null, "havenone", "onclick=\"document.getElementById('pkeybox').disabled=true\""); ?> No, I am ready for the confirmation step.
		</td>
	</tr>
</table>
<?
endWindow();
buttons(submit($f, $s, 'Next'), button("Cancel", NULL, "addcontact3.php"));
EndForm();
include_once("navbottom.inc.php");
?>
