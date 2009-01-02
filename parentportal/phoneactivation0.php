<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

// if not authorized, redirect
if (!$INBOUND_ACTIVATION)
	redirect("addcontact1.php");

$_SESSION['phoneactivationpkeylist'] = array(); // clear out any previous pkeys

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

			// by phone
			if (GetFormData($f, $s, "radioselect") == "byphone") {
				redirect("phoneactivation1.php");
			} else {
				// by code
				redirect("addcontact1.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	PutFormData($f, $s, "radioselect", "bycode");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Contact Activation - Step 1";

include_once("nav.inc.php");
NewForm($f);

startWindow('Activation Method');
?>
<table>
	<tr><td>Start by choosing the method you'll use to add people to your account.</td</tr>

	<tr><td class="bottomBorder">&nbsp;</td></tr>

	<tr>
		<td>
			<? NewFormItem($f, $s, "radioselect", "radio", null, "bycode"); ?> I have an Activation Code to enter now.
		</td>
	</tr>
	<tr>
		<td>
			<? NewFormItem($f, $s, "radioselect", "radio", null, "byphone"); ?> I do not have an Activation Code and want to activate by phone.
		</td>
	</tr>
</table>
<?
endWindow();
buttons(submit($f, $s, 'Next'), button("Cancel", NULL, "addcontact3.php"));
EndForm();
include_once("navbottom.inc.php");
?>