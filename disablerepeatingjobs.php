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

$f = "disablerepeatingjobs";
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

		//do check
		if( CheckFormSection($f, $s) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//submit changes
			setSystemSetting('disablerepeat', GetFormData($f, $s, 'disablerepeat'));
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
	PutFormData($f, $s, "disablerepeat", getSystemSetting('disablerepeat'), 'bool', 0, 1);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Enable/Disable Repeating Jobs';

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
						<td>
							Disable Repeating Jobs<? print help('Settings_DisableRepeat', NULL, "small"); ?>
						</td>
						<td>
							<table border="0" cellpadding="2" cellspacing="0">
								<tr>
									<td><? NewFormItem($f, $s, 'disablerepeat', 'checkbox'); ?></td>
									<td>This setting will prevent all scheduled repeating jobs from running.</td>
								</tr>
							</table>
						</td>
					</tr>
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