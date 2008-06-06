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

$f = "jobsettings";
$s = "main";
$reloadform = 0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'addtype'))
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
		} else if($IS_COMMSUITE && GetFormData($f, $s, "easycallmin") > GetFormData($f, $s, "easycallmax") && (GetFormData($f, $s, "easycallmax") != "")){
			error('The minimum extensions length has to be less than or equal to the maximum');
		} else {
			//check the parsing

			if (isset($errors) && count($errors) > 0) {
				error('There was an error parsing the setting', implode("",$errors));
			} else {
				//submit changes

				if($IS_COMMSUITE){
					setSystemSetting('surveyurl', GetFormData($f, $s, 'surveyurl'));
				}
				setSystemSetting('retry', GetFormData($f, $s, 'retry'));
				setSystemSetting('callerid', Phone::parse(GetFormData($f, $s, 'callerid')));

				setSystemSetting('alertmessage', trim(GetFormData($f, $s, 'alertmessage')));

				setSystemSetting('autoreport_replyemail', GetFormData($f, $s, 'autoreport_replyemail'));
				setSystemSetting('autoreport_replyname', GetFormData($f, $s, 'autoreport_replyname'));

				if($IS_COMMSUITE || getSystemSetting('_dmmethod') != 'asp'){
					setSystemSetting('easycallmin', GetFormData($f, $s, 'easycallmin'));
					setSystemSetting('easycallmax', GetFormData($f, $s, 'easycallmax'));
				}
				redirect("settings.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	//check for new setting name/desc from settings.php
	PutFormData($f,$s,"retry",getSystemSetting('retry'),"number",5,240);
	PutFormData($f, $s, "callerid", Phone::format(getSystemSetting('callerid')), 'phone', 10, 10, true);


	if($IS_COMMSUITE || getSystemSetting('_dmmethod') != 'asp'){
		PutFormData($f, $s, "easycallmin", getSystemSetting('easycallmin', 10), "number", 0, 10);
		PutFormData($f, $s, "easycallmax", getSystemSetting('easycallmax', 10), "number", 0, 10);
	}

	PutFormData($f, $s, "autoreport_replyemail", getSystemSetting('autoreport_replyemail'), 'email',0,100);
	PutFormData($f, $s, "autoreport_replyname", getSystemSetting('autoreport_replyname'), 'text',0,100);

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Systemwide Job Settings';

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
								<td>Retry Setting<? print help('Settings_RetrySetting', NULL, "small"); ?></td>
								<td>
									<table border="0" cellpadding="2" cellspacing="0">
										<tr>
											<td>
								<?
									NewFormItem($f,$s,'retry','selectstart');
									NewFormItem($f,$s,'retry','selectoption',5,5);
									NewFormItem($f,$s,'retry','selectoption',10,10);
									NewFormItem($f,$s,'retry','selectoption',15,15);
									NewFormItem($f,$s,'retry','selectoption',30,30);
									NewFormItem($f,$s,'retry','selectoption',60,60);
									NewFormItem($f,$s,'retry','selectoption',90,90);
									NewFormItem($f,$s,'retry','selectoption',120,120);
									NewFormItem($f,$s,'retry','selectend');
								?>
											</td>
											<td>
												minutes to retry busy and unanswered phone numbers
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td>
									Default Caller ID Number<? print help('Settings_CallerID', NULL, "small"); ?>
								</td>
								<td>
								<? NewFormItem($f, $s, 'callerid', 'text', 20);  ?>
								</td>
							</tr>
							<tr>
								<td  width="30%">Autoreport Email Address<? print help('Settings_AutoreportEmailAddress', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, 'autoreport_replyemail', 'text', 60,100);  ?></td>
							</tr>
							<tr>
								<td>
									Autoreport Email Name<? print help('Settings_AutoreportEmailName', NULL, "small"); ?>
								</td>
								<td>
								<? NewFormItem($f, $s, 'autoreport_replyname', 'text', 60,100);  ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?
				if($IS_COMMSUITE || getSystemSetting('_dmmethod') != 'asp'){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">EasyCall/<br>Call Me:</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="0" width=100%>
							<tr>
								<td width="30%">Minimum Extensions Length<? print help('Settings_MinimumExtensions', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, 'easycallmin', 'text', 3,3);  ?></td>
							</tr>
							<tr>
								<td width="30%">Maximum Extensions Length<? print help('Settings_MaximumExtensions', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, 'easycallmax', 'text', 3,3);  ?></td>
							</tr>
						</table>
					</td>
				</tr>
<?
				}
?>
			</table>
			<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>