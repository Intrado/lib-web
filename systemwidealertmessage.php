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

$f = "systemwidealertmessage";
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
			setSystemSetting('alertmessage', trim(GetFormData($f, $s, 'alertmessage')));
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
	PutFormData($f, $s, "alertmessage", getSystemSetting('alertmessage'), 'text',0,255);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Systemwide Alert Message';

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
						<td>Systemwide Alert Message<? print help('Settings_SystemwideAlert', NULL, "small"); ?></td>
						<td><? NewFormItem($f, $s, 'alertmessage', 'textarea',44,4);  ?></td>
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
