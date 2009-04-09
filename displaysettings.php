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
		} else if(!eregi("^[0-9A-F]{6}$", TrimFormData($f, $s, "_brandprimary"))){
			error("That is not a valid 'Primary Color'. Please enter only a 6 digit hexadecimal value");
		} else if(GetFormData($f, $s, "_brandratio") < 0 || GetFormData($f, $s, "_brandratio") > .5){
			error("The ratio of primary to background can only be between 0 and .5(50%)");
		} else {
			//submit changes
			setSystemSetting('_locale', GetFormData($f, $s, '_locale'));
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

	PutFormData($f, $s, "_locale", getSystemSetting('_locale'), "text", "nomin", "nomax");
	PutFormData($f, $s, "_brandtheme", getSystemSetting('_brandtheme'), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "_brandratio", getSystemSetting('_brandratio'), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "_brandprimary", getSystemSetting('_brandprimary'), "text", "nomin", "nomax", true);

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Systemwide Display Settings';

include_once("nav.inc.php");

?><script src="script/picker.js?<?=rand()?>"></script><?

NewForm($f);
buttons(submit($f, $s, 'Save'));
startWindow('Defaults');
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Options:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="2" cellspacing="0" width=100%>
					<tr>
						<td width="30%"><?=_L("Default Language")?></td>
						<td>
							<?
								NewFormItem($f, $s, '_locale', 'selectstart', null, null, "id='locale'");
								foreach($LOCALES as $loc => $lang){
									NewFormItem($f, $s, '_locale', 'selectoption', $lang, $loc);
								}
								NewFormItem($f, $s, '_locale', 'selectend');
							?>
						</td>
					</tr>
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