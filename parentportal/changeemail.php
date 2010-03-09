<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$error_badpass = _L("That password is incorrect");
$error_generalproblem = _L("There was a problem changing your username, please try again later");
$error_badusername = _L("That username is already in use");
/****************** main message section ******************/

$f = "changeemail";
$s = "main";
$reloadform = 0;
$success = false;

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

		TrimFormData($f, $s, "newemail");

		//do check
		if( CheckFormSection($f, $s) ) {
			error(_L('There was a problem trying to save your changes'), _L('Please verify that all required field information has been entered properly'));
		} else {
			//submit changes
			$email = GetFormData($f, $s, "newemail");
			$pass = GetFormData($f, $s, "password");
			$result = portalUpdatePortalUsername($email, $pass);
			if($result['result'] == ""){
				$success = true;
			} else {
				$resultcode = $result['result'];
				if($resultcode == "invalid argument"){
					if(strpos($result['resultdetail'], "username") !== false){
						error($error_badusername);
					} else {
						error($error_badpass);
					}
				} else {
					error($error_generalproblem);
				}
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "newemail", "", "email", "0", "100", true);
	PutFormData($f, $s, "password", "", "text", "0", "100", true);

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "account:account";
$TITLE = _L("Change Email");

include_once("nav.inc.php");

if($success){
	startWindow(_L('Change Email'));
	?>
	<div style="margin:5px"><?=_L("You should receive an email shortly at the new address with a confirmation code.")?></div>
	<form method='POST' action="index.php?c" name="activate" id="activate">
		<table>
			<tr>
				<td><?=_L("Confirmation Code")?>: </td>
				<td><input type="text" name="token" size="50" /></td>
			</tr>
			<tr>
				<td><?=_L("Password")?>:</td>
				<td><input type="password" name="password" /></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><?=submit("activate", "main", "Submit")?></td>
			</tr>
		</table>
	</form>
	<?
	endWindow();
} else {
	NewForm($f);
	if(!$success)
		buttons(submit($f, $s, _L('Submit')), button(_L("Cancel"), NULL, "start.php"));
	startWindow(_L('Change Email'));
?>
	<table>
		<tr>
			<td><?=_L("New Email Address")?>:</td>
			<td><? NewFormItem($f, $s, "newemail", "text", "50", "100") ?> </td>
		</tr>
		<tr>
			<td><?=_L("Password")?>:</td>
			<td><? NewFormItem($f, $s, "password", "password", "20", "100") ?> </td>
		</tr>
	</table>
	<br>
<?
	endWindow();
	buttons();
	EndForm();
}

include_once("navbottom.inc.php");
?>