<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/form.inc.php");

if (!($USER->authorize('managesystem') || $USER->authorize('metadata') || $USER->authorize('portalaccess'))) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE= "admin:settings";
$TITLE= "Settings";

include("nav.inc.php");

startWindow("Options", 'padding: 3px;');
?>
	<table border="1" width="100%" cellpadding="3" cellspacing="1" class="list" >
		<tr class="listHeader">
<?
			if($USER->authorize('managesystem') || $USER->authorize('metadata')){
?>
				<th align="left" class="nosort">System</th>
<?
			}
			if($USER->authorize('managesystem')){
?>
				<th align="left" class="nosort">Job</th>
				<th align="left" class="nosort">Destination Labels</th>
<?
			}
			// features - if contact manager, or self-signup, or smartcall appliance
			if ((getSystemSetting('_hasportal', false) && $USER->authorize('portalaccess')) ||
				(getSystemSetting('_hasselfsignup', false) && $USER->authorize('metadata')) ||
				($USER->authorize('managesystem') && getSystemSetting('_dmmethod', "")!='asp')) {
?>
				<th align="left" class="nosort">Features</th>
<?
			}
?>
		</tr>
		<tr align="left" valign="top">
<?
		if($USER->authorize('managesystem') || $USER->authorize('metadata')){
?>
			<td>
				<table>
<?
					if($USER->authorize('managesystem')){
?>
						<tr><td><a href='systemwidealertmessage.php'>Systemwide Alert Message</a></td></tr>
						<tr><td><a href='customerinfo.php'>Customer Information</a></td></tr>
<?
					}
					if($USER->authorize('metadata')){
?>
						<tr><td><a href='persondatamanager.php'>Field Definitions</a></td></tr>
						<tr><td><a href='groupdatamanager.php'>Group Field Definitions</a></td></tr>
						<tr><td><a href='scheduledatamanager.php'>Enrollment Field Definitions</a></td></tr>
<?
					}

					if($USER->authorize('managesystem')){
?>
						<tr><td><a href='securitysettings.php'>Security</a></td></tr>
						<tr><td><a href='displaysettings.php'>Display</a></td></tr>

<?
					}
?>
				</table>
			</td>
<?
		}
		if($USER->authorize('managesystem')){
?>
			<td>
				<table>
					<tr><td><a href='disablerepeatingjobs.php'>Enable/Disable Repeating Jobs</a></td></tr>
					<tr><td><a href='jobsettings.php'>Job Settings</a></td></tr>
					<tr><td><a href='jobtypemanagement.php'>Job Types</a></td></tr>
					<tr><td><a href='messageintro.php'>Message Intro</a></td></tr>
					<tr><td><a href='classroommessagemanager.php'>Classroom Message Manager</a></td></tr>
				</table>
			</td>
			<td>
				<table>
					<tr><td><a href='destinationlabel.php?type=phone'>Phone Labels</a></td></tr>
					<tr><td><a href='destinationlabel.php?type=email'>Email Labels</a></td></tr>
<? if(getSystemSetting('_hassms', false)){ ?>
					<tr><td><a href='destinationlabel.php?type=sms'>SMS Labels</a></td></tr>
<? } ?>
				</table>
			</td>
<?
		}
		// features - if contact manager, or self-signup, or smartcall appliance
		if ((getSystemSetting('_hasportal', false) && $USER->authorize('portalaccess')) ||
			(getSystemSetting('_hasselfsignup', false) && ($USER->authorize('metadata') || $USER->authorize('managesystem'))) ||
			($USER->authorize('managesystem') && getSystemSetting('_dmmethod', "")!='asp')) {
?>
			<td>
				<table>
<?
					if (getSystemSetting('_hasportal', false) && $USER->authorize('portalaccess')) {
?>
						<tr><td><a href='contactmanagersettings.php'>Contact Manager Settings</a></td></tr>
<?
					}
					if (getSystemSetting('_hasselfsignup', false)) {
						if ($USER->authorize('managesystem')) {
?>
							<tr><td><a href='subscribersettings.php'>Self-Signup Settings</a></td></tr>
<?						}
						if ($USER->authorize('metadata')) {
?>
							<tr><td><a href='subscriberfields.php'>Self-Signup Fields</a></td></tr>
<?						}
					}
					if ($USER->authorize('managesystem') && getSystemSetting('_dmmethod', "")!='asp') {
?>
						<tr><td><a href='dms.php'><?=($IS_COMMSUITE)?_L("Telephony Settings"):_L("SmartCall Appliance")?></a></td></tr>
<?
					}
?>
				</table>
			</td>
<?
		}
?>
		</tr>
	</table>
<?
endWindow();

include("navbottom.inc.php");
?>
