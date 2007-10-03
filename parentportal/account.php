<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

$f="portaluser";
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
		} else if(GetFormData($f, $s, "password1") != GetFormData($f, $s, "password2")){
			error('Password confirmation does not match');
		} else {
			//submit changes
			if(portalUpdatePortalUser($_SESSION['portaluserid'], GetFormData($f, $s, "firstname"), GetFormData($f, $s, "lastname"), GetFormData($f, $s, "zipcode"))){
				redirect();
			} else {
				error("An error occurred while updating your information");
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "firstname", $_SESSION['portaluser']['portaluser.firstname'], "text", "1", "100");
	PutFormData($f, $s, "lastname", $_SESSION['portaluser']['portaluser.lastname'], "text", "1", "100");
	PutFormData($f, $s, "password1", "00000000");
	PutFormData($f, $s, "password2", "00000000");
	PutFormData($f, $s, "zipcode", $_SESSION['portaluser']['portaluser.zipcode'], "number", "10000", "99999");
}

$PAGE = "welcome:account";
$TITLE = "Account Information: " . $_SESSION['portaluser']['portaluser.firstname'] . " " . $_SESSION['portaluser']['portaluser.lastname'];
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'Save'), button("Change Email",NULL, "changeemail.php"));

startWindow('User Information');
?>			
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;">Account Info:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td align="right">First Name:</td>
						<td colspan="4"><? NewFormItem($f,$s, 'firstname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right">Last Name:</td>
						<td colspan="4"><? NewFormItem($f,$s, 'lastname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right">Password:</td>
						<td colspan="4"><? NewFormItem($f,$s, 'password1', 'password', 20,50); ?></td>
						<td>&nbsp;</td>
						<td align="right">Confirm Password:</td>
						<td colspan="4"><? NewFormItem($f,$s, 'password2', 'password', 20,50); ?></td>
					</tr>
					<tr>
						<td align="right">Zipcode:</td>
						<td colspan="4"><? NewFormItem($f, $s, 'zipcode', 'text', '5'); ?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<?
endWindow();
buttons();
include_once("navbottom.inc.php");
?>