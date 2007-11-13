<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$error_badpass = "That password is incorrect";
$error_generalproblem = "There was a problem changing your username";
$error_badusername = "That username is already in use";
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
			$result = portalUpdatePortalUsername(GetFormData($f, $s, "newemail"), GetFormData($f, $s, "password"));
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
$TITLE = "Change Email";

include_once("nav.inc.php");

if($success){
	startWindow('Change Email' . help("Changeemail"));
	?>
	<div style="margin:5px">You should receive an email shortly at the new address with an activation code.</div>
	<form method='POST' action="activate.php?changeuser=1" name="activate" id="activate">
		<table>
			<tr>
				<td>Activation Code: </td>
				<td><input type="text" name="token" size="50" /></td>
			</tr>
			<tr>
				<td>Password:</td>
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
		buttons(submit($f, $s, 'Submit'));
	startWindow('Change Email' . help("Changeemail"));
?>
	<table>
		<tr>
			<td>New Email Address:</td>
			<td><? NewFormItem($f, $s, "newemail", "text", "50", "100") ?> </td>
		</tr>
		<tr>
			<td>Password:</td>
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