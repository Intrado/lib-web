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

$f = "customerinfo";
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
		TrimFormData($f, $s, 'custdisplayname');
		TrimFormData($f, $s, 'emaildomain');
		TrimFormData($f, $s, 'defaultareacode');
		//do check
		if( CheckFormSection($f, $s) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//submit changes
			$custname= GetFormData($f, $s, 'custdisplayname');
			if($custname != "" || $custname != $_SESSION['custname']){
				setSystemSetting('displayname', $custname);
				$_SESSION['custname']=$custname;
			}
			if($IS_COMMSUITE){
				setSystemSetting('surveyurl', GetFormData($f, $s, 'surveyurl'));
			}

			setSystemSetting('emaildomain', trim(GetFormData($f, $s, 'emaildomain')));
			setSystemSetting('defaultareacode', GetFormData($f, $s, 'defaultareacode'));

			if($IS_COMMSUITE){
				setSystemSetting('_supportphone', Phone::parse(GetFormData($f, $s, 'supportphone')));
				setSystemSetting('_supportemail', trim(GetFormData($f, $s, 'supportemail')));
			}
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

	$custname = getSystemSetting('displayname');
	PutFormData($f, $s,"custdisplayname", $custname, 'text', 0, 50);
	if($IS_COMMSUITE)
		PutFormData($f, $s, "surveyurl", getSystemSetting('surveyurl'), 'text', 0, 100);

	PutFormData($f, $s, "emaildomain", getSystemSetting('emaildomain'), "text", 0, 255);
	PutFormData($f, $s, "defaultareacode", getSystemSetting('defaultareacode'), 'number',200,999);
	if($IS_COMMSUITE){
		PutFormData($f, $s, "supportphone", Phone::format(getSystemSetting('_supportphone')), "phone", "10", "10", true);
		PutFormData($f, $s, "supportemail", getSystemSetting('_supportemail'), "email", "nomin", "nomax", true);
	}

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Customer Information';

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'Save'));
startWindow('Settings');
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Options:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="2" cellspacing="0" width=100%>

				<tr>
					<td width="30%">Customer Display Name<? print help('Settings_CustDisplayName', NULL, "small"); ?></td>
					<td><? NewFormItem($f, $s, 'custdisplayname', 'text', 50, 50);  ?></td>
				</tr>
<?
				if($IS_COMMSUITE){
?>
					<tr>
						<td>
							Survey URL<? print help('Settings_SurveyURL', NULL, "small"); ?>
						</td>
						<td><? NewFormItem($f, $s, 'surveyurl', 'text', 60, 100);  ?></td>
					</tr>
<?
				}
?>
					<tr>
						<td>Email Domain<? print help('Settings_EmailDomain', NULL, "small"); ?></td>
						<td><? NewFormItem($f, $s, 'emaildomain', 'text', 30, 255);  ?></td>
					</tr>
					<tr>
						<td width="30%">Local Area Code<? print help('Settings_DefaultLocalAreaCode', NULL, "small"); ?></td>
						<td><? NewFormItem($f, $s, 'defaultareacode', 'text', 3,3);  ?></td>
					</tr>
<?
				if($IS_COMMSUITE){
?>
					<tr>
						<td>
							Service & Support Phone<? print help('Settings_SupportPhone', NULL, "small"); ?>
						</td>
						<td><? NewFormItem($f, $s, 'supportphone', 'text', 14, 14);  ?></td>
					</tr>
					<tr>
						<td>
							Service & Support Email<? print help('Settings_SupportEmail', NULL, "small"); ?>
						</td>
						<td><? NewFormItem($f, $s, 'supportemail', 'text', 30, 250);  ?></td>
					</tr>
<?
				}
?>

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