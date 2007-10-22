<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");


$error_failedupdate = "There was an error updating your information";
$error_failedupdatepassword = "There was an error updating your password";
$f="portaluser";
$s="main";
$reloadform = 0;
$error = 0;


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
		} else if(GetFormData($f, $s, "newpassword1") != GetFormData($f, $s, "newpassword2")){
			error('Password confirmation does not match');
		} else {
			//submit changes
			$result = portalUpdatePortalUser(GetFormData($f, $s, "firstname"), GetFormData($f, $s, "lastname"), GetFormData($f, $s, "zipcode"));
			if($result['result'] != ""){
				$updateuser = false;
				error($error_failedupdate);
				$error = 1;
			}
			if(GetFormData($f, $s, "newpassword1")){
				$result = portalUpdatePortalUserPassword(GetFormData($f, $s, "newpassword1"), GetFormData($f, $s, "oldpassword"));
				if($result['result'] != ""){
					$updateuser = false;
					error($error_failedupdatepassword);
					$error = 1;
				}
			}
			if(!$error){
				redirect();
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
	PutFormData($f, $s, "newpassword1", "", "text");
	PutFormData($f, $s, "newpassword2", "", "text");
	PutFormData($f, $s, "oldpassword", "", "text");
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
						<td align="right">Email:</td>
						<td colspan="4"><?=$_SESSION['portaluser']['portaluser.username']?></td>
					</tr>
					<tr>
						<td align="right">First Name:</td>
						<td colspan="4"><? NewFormItem($f,$s, 'firstname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right">Last Name:</td>
						<td colspan="4"><? NewFormItem($f,$s, 'lastname', 'text', 20,100); ?></td>
					</tr>
					<tr>
						<td align="right">Zipcode:</td>
						<td colspan="4"><? NewFormItem($f, $s, 'zipcode', 'text', '5'); ?></td>
					</tr>
					<tr>
						<td align="right">*Old Password:</td>
						<td colspan="4"><? NewFormItem($f,$s, 'oldpassword', 'password', 20,50); ?></td>
					</tr>
					<tr>
						<td align="right">New Password:</td>
						<td colspan="4"><? NewFormItem($f,$s, 'newpassword1', 'password', 20,50); ?></td>
					</tr>
					<tr>
						<td align="right">Confirm New Password:</td>
						<td colspan="4"><? NewFormItem($f,$s, 'newpassword2', 'password', 20,50); ?></td>
					</tr>
					
				</table>
				<div>*Only required for changing your password</div>
			</td>
		</tr>
	</table>
	
<?
endWindow();
buttons();
include_once("navbottom.inc.php");
?>