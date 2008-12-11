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
				$pkey = DBSafe(GetFormData($f, $s, "pkey"));
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
$TITLE = "Contact Activation";

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'Next'), button("Cancel", NULL, "addcontact3.php"));


startWindow('Activation Method');
?>
<table>
	<tr>
		<td>
			<? NewFormItem($f, $s, "radioselect", "radio", null, "havenone", ""); ?> I am finished adding contacts.
		</td>
	</tr>
	<tr>
		<td>
			<? NewFormItem($f, $s, "radioselect", "radio", null, "havemore", ""); ?> I want to add another Contact Identification Number:
		</td>
		<td><? NewFormItem($f, $s, "pkey", "text", "20", "255") ?></td>
	</tr>
</table>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>