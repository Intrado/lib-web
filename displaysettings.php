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

$f = "setting";
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
		} else if(!eregi("[0-9A-F]{6}", GetFormData($f, $s, "_brandprimary"))){
			error("That is not a valid 'Primary Color'");
		} else if(GetFormData($f, $s, "_brandratio") < 0 || GetFormData($f, $s, "_brandratio") > .5){
			error("The ratio of primary to background can only be between 0 and .5(50%)");
		} else {
			//check the parsing

			if (isset($errors) && count($errors) > 0) {
				error('There was an error parsing the setting', implode("",$errors));
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

				setSystemSetting('alertmessage', trim(GetFormData($f, $s, 'alertmessage')));

				setSystemSetting('_brandtheme', GetFormData($f, $s, '_brandtheme'));
				setSystemSetting('_brandprimary', GetFormData($f, $s, '_brandprimary'));
				setSystemSetting('_brandratio', GetFormData($f, $s, '_brandratio'));
				setSystemSetting('_brandtheme1', $COLORSCHEMES[GetFormData($f, $s, "_brandtheme")]["_brandtheme1"]);
				setSystemSetting('_brandtheme2', $COLORSCHEMES[GetFormData($f, $s, "_brandtheme")]["_brandtheme2"]);

				if(!QuickQuery("select value from usersetting where name = '_brandtheme' and userid='" . $USER->id . "'")){
					$_SESSION['colorscheme']['_brandtheme'] = GetFormData($f, $s, '_brandtheme');
					$_SESSION['colorscheme']['_brandprimary'] = GetFormData($f, $s, '_brandprimary');
					$_SESSION['colorscheme']['_brandratio'] = GetFormData($f, $s, '_brandratio');
					$_SESSION['colorscheme']['_brandtheme1'] = $COLORSCHEMES[GetFormData($f, $s, "_brandtheme")]["_brandtheme1"];
					$_SESSION['colorscheme']['_brandtheme2'] = $COLORSCHEMES[GetFormData($f, $s, "_brandtheme")]["_brandtheme2"];
				}


				setSystemSetting('emaildomain', DBSafe(trim(GetFormData($f, $s, 'emaildomain'))));

				if($IS_COMMSUITE){
					setSystemSetting('_supportphone', Phone::parse(GetFormData($f, $s, 'supportphone')));
					setSystemSetting('_supportemail', DBSafe(GetFormData($f, $s, 'supportemail')));
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

	$custname = getSystemSetting('displayname');
	PutFormData($f, $s,"custdisplayname", $custname, 'text', 0, 50);
	if($IS_COMMSUITE)
		PutFormData($f, $s, "surveyurl", getSystemSetting('surveyurl'), 'text', 0, 100);

	PutFormData($f, $s, "alertmessage", getSystemSetting('alertmessage'), 'text',0,255);

	PutFormData($f, $s, "_brandtheme", getSystemSetting('_brandtheme'), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "_brandratio", getSystemSetting('_brandratio'), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "_brandprimary", getSystemSetting('_brandprimary'), "text", "nomin", "nomax", true);

	PutFormData($f, $s, "emaildomain", getSystemSetting('emaildomain'), "text", 0, 255);

	if($IS_COMMSUITE){
		PutFormData($f, $s, "supportphone", Phone::format(getSystemSetting('_supportphone')), "phone", "10", "10", true);
		PutFormData($f, $s, "supportemail", getSystemSetting('_supportemail'), "email", "nomin", "nomax", true);
	}

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Display Settings';

include_once("nav.inc.php");

?><script src="script/picker.js?<?=rand()?>"></script><?

NewForm($f);
buttons(submit($f, $s, 'Save'));
startWindow('Global Display Settings');
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Display Defaults:</th>
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
								<td>Systemwide Alert Message<? print help('Settings_SystemwideAlert', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, 'alertmessage', 'textarea',44,4);  ?></td>
							</tr>
							<tr>
								<td>Email Domain<? print help('Settings_EmailDomain', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, 'emaildomain', 'text', 30, 255);  ?></td>
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
							<tr>
								<td width="30%">Color Theme<? print help('Settings_ColorTheme', NULL, "small"); ?></td>
								<td>
									<?
										NewFormItem($f, $s, '_brandtheme', 'selectstart', null, null, "onchange='resetPrimaryAndRatio(this.value)'");
										foreach($COLORSCHEMES as $theme => $scheme){
											NewFormItem($f, $s, '_brandtheme', 'selectoption', $scheme['displayname'], $theme);
										}
										NewFormItem($f, $s, '_brandtheme', 'selectend');
									?>
								</td>
							</tr>
							<tr>
								<td>Primary Color(in hex)<? print help('Settings_PrimaryColor', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, "_brandprimary", "text", 0, 10, "id='brandprimary'") ?><img src="img/sel.gif" onclick="TCP.popup(new getObj('brandprimary').obj)"/></td>
							</tr>
							<tr>
								<td>Ratio of Primary to Background<? print help('Settings_Ratio', NULL, "small"); ?></td>
								<td><? NewFormItem($f, $s, "_brandratio", "text", 0, 3, "id='brandratio'") ?></td>
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

<script>

	var colorscheme = new Array();

<?
	//Make js array of colorschemes
	foreach($COLORSCHEMES as $index => $properties){
?>
		colorscheme['<?=$index?>'] = new Array();
		colorscheme['<?=$index?>']['_brandprimary'] = '<?=$properties['_brandprimary']?>';
		colorscheme['<?=$index?>']['_brandratio'] = '<?=$properties['_brandratio']?>';
<?
	}
?>

	function resetPrimaryAndRatio(value){

		new getObj('brandprimary').obj.value = colorscheme[value]['_brandprimary'];
		new getObj('brandratio').obj.value = colorscheme[value]['_brandratio'];
	}

</script>