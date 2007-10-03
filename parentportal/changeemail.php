<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

/****************** main message section ******************/

$f = "changeemail";
$s = "main";
$reloadform = 0;

$email = "";

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
		$email = GetFormData($f, $s, "email");
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//submit changes


			redirect("changeemail.php");
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "email", $email, "email", "0", "100", true);

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "account:account";
$TITLE = "Change Email";

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'Submit'));


startWindow('Change Email');
?>
<table>
	<tr>
		<td>New Email Address:</td>
		<td><? NewFormItem($f, $s, "email", "text", "100") ?> </td>
	</tr>
</table>
<img src="img/bug_important.gif" >Changing your email will log you out and disable your account until you reactivate it.
<br>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>