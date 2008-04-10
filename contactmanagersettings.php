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
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem') && !getSystemSetting("_hasportal", false) && !$USER->authorize('portalaccess')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$maxphones = getSystemSetting("maxphones", 3);
$maxemails = getSystemSetting("maxemails", 2);
$maxsms = getSystemSetting("maxsms", 2);
$maxcolumns = max($maxphones, $maxemails, $maxsms);

/****************** main message section ******************/

$f = "contactmanagersettings";
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
		} else {
			//check the parsing

			if (isset($errors) && count($errors) > 0) {
				error('There was an error parsing the setting', implode("",$errors));
			} else {
				//submit changes

				for($i = 0; $i < $maxphones; $i++){
					setSystemSetting('lockedphone' . $i, GetFormData($f, $s, 'lockedphone' . $i));
				}
				for($i = 0; $i < $maxemails; $i++){
					setSystemSetting('lockedemail' . $i, GetFormData($f, $s, 'lockedemail' . $i));
				}
				for($i = 0; $i < $maxsms; $i++){
					setSystemSetting('lockedsms' . $i, GetFormData($f, $s, 'lockedsms' . $i));
				}
				setSystemSetting('tokenlife', GetFormData($f, $s, 'tokenlife'));
				setSystemSetting('priorityenforcement', GetFormData($f, $s, 'priorityenforcement'));

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

	for($i=0; $i < $maxphones; $i++){
		PutFormData($f, $s, "lockedphone" . $i, getSystemSetting('lockedphone' . $i, 0), "bool", 0, 1);
	}
	for($i=0; $i < $maxemails; $i++){
		PutFormData($f, $s, "lockedemail" . $i, getSystemSetting('lockedemail' . $i, 0), "bool", 0, 1);
	}
	for($i=0; $i < $maxsms; $i++){
		PutFormData($f, $s, "lockedsms" . $i, getSystemSetting('lockedsms' . $i, 0), "bool", 0, 1);
	}
	PutFormData($f, $s, "tokenlife", getSystemSetting('tokenlife', 30), 'number', 1, 365, true);
	PutFormData($f, $s, 'priorityenforcement', getSystemSetting('priorityenforcement', 0), "bool", 0, 1);
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = 'Contact Manager Settings';

include_once("nav.inc.php");

NewForm($f);
buttons(submit($f, $s, 'Save'));
startWindow('Contact Manager Settings');
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Contact Manager:</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="2" cellspacing="0" width="100%">
								<tr>
									<td width="30%">Activation Code Lifetime<?=help("Settings_ActCodeLifetime", NULL, "small")?></td>
									<td><? NewFormItem($f, $s, "tokenlife", "text", 3); ?> 1 - 365 days</td>
								</tr>
								<tr>
									<td width="30%">Require phone numbers for Emergency and High Priority Job Types<?=help("Settings_RequirePhone", NULL, "small")?></td>
									<td><? NewFormItem($f, $s, "priorityenforcement", "checkbox"); ?></td>
								</tr>
								<tr>
									<td width="30%">Restricted Destination Fields<?=help("Settings_RestrictedDest", NULL, "small")?></td>
									<td>
										<table border="0" cellpadding="3" cellspacing="1">
											<tr class="listheader">
												<th>Contact Type</th>
												<?
												for($i=1; $i<= $maxcolumns;$i++){
													?><th><?=$i?></th><?
												}
												?>
											</tr>

											<tr>
												<td align="left" class="bottomBorder"><?=destination_label_popup_paragraph("phone")?></td>
<?
												for($i=0; $i< $maxcolumns; $i++){
?>
													<td align="center" class="bottomBorder">
<?
													if($i< $maxphones){
														destination_label_popup("phone", $i, $f, $s, "lockedphone" . $i);
													} else {
														echo "&nbsp;";
													}
?>
													</td>
<?
												}
?>
											</tr>
											<tr>
												<td align="left" class="bottomBorder"><?=destination_label_popup_paragraph("email")?></td>
<?
												for($i=0; $i< $maxcolumns; $i++){
?>
													<td align="center" class="bottomBorder">
<?
													if($i< $maxemails){
														destination_label_popup("email", $i, $f, $s, "lockedemail" . $i);
													} else {
														echo "&nbsp;";
													}
?>
													</td>
<?
												}
?>
											</tr>
<?
											if(getSystemSetting("_hassms", false)){
?>
											<tr>
												<td align="left" class="bottomBorder"><?=destination_label_popup_paragraph("sms")?></td>
<?
												for($i=0; $i< $maxcolumns; $i++){
?>
													<td align="center" class="bottomBorder">
<?
													if($i< $maxsms){
														destination_label_popup("sms", $i, $f, $s, "lockedsms" . $i);
													} else {
														echo "&nbsp;";
													}
?>
													</td>
<?
												}
?>
											</tr>
<?
											}
?>
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