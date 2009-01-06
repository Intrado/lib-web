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
include_once("obj/JobType.obj.php");
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");
include_once("inc/themes.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


/****************** main message section ******************/

$f = "securitysettings";
$s = "main";
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

		TrimFormData($f, $s, "usernamelength");
		TrimFormData($f, $s, "passwordlength");
		TrimFormData($f, $s, "loginlockoutattempts");
		TrimFormData($f, $s, "loginlockouttime");
		TrimFormData($f, $s, "logindisableattempts");
		
		//do check
		if( CheckFormSection($f, $s) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(GetFormData($f, $s, "loginlockoutattempts") != 0 && GetFormData($f, $s, "logindisableattempts") !=0 && GetFormData($f, $s, "logindisableattempts") <= GetFormData($f, $s, "loginlockoutattempts")){
			error("The login disable attempts must be greater than the login lockout attempts");
		} else {
			setSystemSetting('usernamelength', GetFormData($f, $s, 'usernamelength'));
			setSystemSetting('passwordlength', GetFormData($f, $s, 'passwordlength'));

			setSystemSetting('loginlockoutattempts', GetFormData($f, $s, 'loginlockoutattempts'));
			setSystemSetting('loginlockouttime', GetFormData($f, $s, 'loginlockouttime'));
			setSystemSetting('logindisableattempts', GetFormData($f, $s, 'logindisableattempts'));

			setSystemSetting('msgcallbackrequireid', GetFormData($f, $s, 'msgcallbackrequireid'));

			redirect("settings.php");
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	//check for new setting name/desc from settings.php
	PutFormData($f, $s, "loginlockoutattempts", getSystemSetting('loginlockoutattempts', "5"), "number", 0, 15, true);
	PutFormData($f, $s, "logindisableattempts", getSystemSetting('logindisableattempts', "0"), "number", 0, 15, true);
	PutFormData($f, $s, "loginlockouttime", getSystemSetting('loginlockouttime', "5"), "number", 1, 60, true);

	PutFormData($f, $s,"usernamelength", getSystemSetting('usernamelength', "5"), "number", 4, 10);
	PutFormData($f, $s,"passwordlength", getSystemSetting('passwordlength', "5"), "number", 4, 10);

	PutFormData($f, $s, "msgcallbackrequireid", getSystemSetting('msgcallbackrequireid'), 'bool', 0, 1);
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Systemwide Security';

include_once("nav.inc.php");

NewForm($f);
buttons(submit($f, $s, 'Save'));
startWindow('Login Settings');
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Options:</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="0" width=100%>
							<tr>
								<td width="30%">Minimum Username Length<? print help('Settings_MinimumUsername', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, 'usernamelength', 'text', 3,3);  ?> Must be between 4 amd 10.</td>
							</tr>
							<tr>
								<td>Minimum Password Length<? print help('Settings_MinimumPassword', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, 'passwordlength', 'text', 3,3);  ?> Must be between 4 amd 10.</td>
							</tr>
							<tr>
								<td>Invalid Login Lockout<? print help('Settings_InvalidLoginLockout', NULL, "small"); ?></td>
								<td><? NewFormItem($f,$s,'loginlockoutattempts','text', 2) ?> 1 - 15 attempts, or 0 to disable</td>
							</tr>
							<tr>
								<td>Invalid Login Lockout Period<? print help('Settings_LoginLockoutTime', NULL, "small"); ?></td>
								<td><? NewFormItem($f,$s,'loginlockouttime','text', 2) ?> 1 - 60 minutes</td>
							</tr>
							<tr>
								<td>Invalid Login Disable Account<? print help('Settings_LoginDisableAccount', NULL, "small"); ?></td>
								<td><? NewFormItem($f,$s,'logindisableattempts','text', 2) ?> 1 - 15 attempts, or 0 to disable</td>
							</tr>
<? if (getSystemSetting('_hascallback', false)) { ?>
							<tr>
								<td>Require Student ID on Call Back<? print help('Settings_MSGCallBackRequireID', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, 'msgcallbackrequireid', 'checkbox'); ?></td>
							</tr>
<? } ?>
						</table>
					</td>
				</tr>

			</table>
			<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>
