<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/text.inc.php");
include_once("obj/Phone.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managemyaccount')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

/****************** main message section ******************/

$f = "user";
$s = "main";
$reloadform = 0;

function isValidPass($text) {
	if ($text == "99999999")
		return false;
	else
		return true;
}

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

		$phone = preg_replace('/[^\\d]/', '', GetFormData($f,$s,"phone"));

		//do check
		if (strlen(GetFormData($f, $s, 'pincode')) < 4) {
			error('Telephone Pin Code code is too short.');
		} else if (strlen(GetFormData($f, $s, 'password')) < 4) {
			error('Password is too short.');
		} elseif( GetFormData($f, $s, 'password') != GetFormData($f, $s, 'passwordconfirm') ) {
			error('Password confirmation does not match');
		} elseif( GetFormData($f, $s, 'pincode') != GetFormData($f, $s, 'pincodeconfirm') ) {
			error('Telephone Pin Code confirmation does not match');
		} else if ($phone != null && strlen($phone) < 2 || (strlen($phone) > 6 && strlen($phone) != 10)) {
			error('The phone number must be 2-6 digits or exactly 10 digits long (including area code)','You do not need to include a 1 for long distance');
		} elseif (User::checkDuplicateLogin(GetFormData($f,$s,"login"), $USER->customerid, $USER->id)) {
			error('This username already exists, please choose another');
		} elseif (User::checkDuplicateAccesscode(GetFormData($f, $s, 'accesscode'), $USER->customerid, $USER->id)) {
			$newcode = getNextAvailableAccessCode(DBSafe(GetFormData($f, $s, 'accesscode')), $USER->id,  $USER->customerid);
			PutFormData($f, $s, 'accesscode', $newcode, 'number', 'nomin', 'nomax', true); // Repopulate the form/session data with the generated code
			error('Your telephone user id number must be unique - one has been generated for you');
		} else if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//submit changes
			PopulateObject($f,$s,$USER,array("login","accesscode","firstname","lastname","email"));
			$USER->phone = Phone::parse(GetFormData($f,$s,"phone"));
			$USER->update();

			$newpassword = GetFormData($f, $s, 'password');
			if (isValidPass($newpassword))
				$USER->setPassword($newpassword);
			$newpin = GetFormData($f, $s, 'pincode');
			if (isValidPass($newpin))
				$USER->setPincode($newpin);

			redirect("start.php");
		}
	}
} else {
	$reloadform = 1;
}

$RULEMODE = array('multisearch' => true, 'text' => false, 'reldate' => false);
$RULES = DBFindMany('Rule', "from rule inner join userrule on rule.id = userrule.ruleid where userid = $_SESSION[userid]");
if( $reloadform )
{
	ClearFormData($f);

	$fields = array(
			array("login","text",1,20,true),
			array("accesscode","number",1000,"nomax",true),
			array("firstname","text",1,50,true),
			array("lastname","text",1,50,true),
			array("email","text",0,100)
			);

	PopulateForm($f,$s,$USER,$fields);
	PutFormData($f,$s,"phone",Phone::format($USER->phone),"text",2, 20);

	$pass = $USER->id ? '99999999' : '';
	PutFormData($f,$s,"password",$pass,"text",4,50,true);
	PutFormData($f,$s,"passwordconfirm",$pass,"text",4,50,true);
	PutFormData($f,$s,"pincode",$pass,"number",1000,"nomax",true);
	PutFormData($f,$s,"pincodeconfirm",$pass,"number",1000,"nomax",true);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "start";
$TITLE = "Account Information: $USER->firstname $USER->lastname";

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'save'));

$RULES = DBFindMany('Rule', "from rule inner join userrule on rule.id = userrule.ruleid where userid = $usr->id");

startWindow('User Information');
?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th valign="top" width="70" class="windowRowHeader" align="right" valign="top" style="padding-top: 6px;">Access Credentials:<br><? print help('User_AccessCredentials', NULL, 'grey'); ?></th>
					<td>
						<table border="0" cellpadding="1" cellspacing="0">
							<tr>
								<td align="right">First Name:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'firstname', 'text', 20,50); ?></td>
							</tr>
							<tr>
								<td align="right">Last Name:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'lastname', 'text', 20,50); ?></td>
							</tr>
							<tr>
								<td align="right">Username:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'login', 'text', 20); ?></td>
							</tr>
							<tr>
								<td align="right">Password:</td>
								<td><? NewFormItem($f,$s, 'password', 'password', 20,50); ?></td>
								<td>&nbsp;</td>
								<td align="right">Confirm Password:</td>
								<td><? NewFormItem($f,$s, 'passwordconfirm', 'password', 20,50); ?></td>
							</tr>
							<tr>
								<td align="right">Telephone User ID#:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'accesscode', 'text', 10); ?></td>
							</tr>
							<tr>
								<td align="right">Telephone Pin Code #:</td>
								<td><? NewFormItem($f,$s, 'pincode', 'password', 20,100); ?></td>
								<td>&nbsp;</td>
								<td align="right">Telephone Pin Code #:</td>
								<td><? NewFormItem($f,$s, 'pincodeconfirm', 'password', 20,100); ?></td>
							</tr>
							<tr>
								<td align="right">Email:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'email', 'text', 20, 100); ?></td>
							</tr>
							<tr>
								<td align="right">Phone:</td>
								<td colspan="4"><? NewFormItem($f,$s, 'phone', 'text', 20); ?></td>
							</tr>

						</table>
						<br>Please note: username and password are case-sensitive and must be a minimum of 4 characters long with no spaces.
						<br>Additionally, the telephone user ID and telephone PIN code must be all numeric.
					</td>
				</tr>
			</table>
		<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>