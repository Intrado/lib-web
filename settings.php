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
		

<?
		if($USER->authorize('managesystem') || $USER->authorize('metadata')){
?>

				<ul class="linkslist">
				
						<li class="heading">System</li>
<?
					if($USER->authorize('managesystem')){
?>
						<li><a href='systemwidealertmessage.php'>Systemwide Alert Message</a></li>
						<li><a href='customerinfo.php'>Customer Information</a></li>
<?
					}
					if($USER->authorize('metadata')){
?>
						<li><a href='persondatamanager.php'>Field Definitions</a></li>
						<li><a href='groupdatamanager.php'>Group Field Definitions</a></li>
<? if (getSystemSetting('_hasenrollment', false)) { ?>
						<li><a href='scheduledatamanager.php'>Section Field Definitions</a></li>
<? } ?>
						<li><a href='organizationdatamanager.php'>Organization Manager</a></li>
<?
					}

					if($USER->authorize('managesystem')){
?>
						<li><a href='securitysettings.php'>Security</a></li>
						<li><a href='displaysettings.php'>Display</a></li>

<?
					}
?>
				</ul>

<?
		}
		if($USER->authorize('managesystem')){
?>

				<ul class="linkslist">
				
					<li class="heading"><?= getJobTitle()?></li>
				
					<li><a href='disablerepeatingjobs.php'><?= _L("Enable/Disable Repeating %s",getJobsTitle())?></a></li>
					<li><a href='jobsettings.php'><?= _L("%s Settings",getJobTitle())?></a></li>
					<li><a href='jobtypemanagement.php'><?= _L("%s Types",getJobTitle())?></a></li>
<?
		if (getSystemSetting("_amdtype","ivr") == "ivr") {
?>
					<li><a href='messageintro.php'>Message Intro</a></li>
<?
		} else {
?>
					<li>&nbsp;</li>
<?
		}
?>

				</ul>

				<ul class="linkslist">
				
					<li class="heading">Destination Labels</li>
				
					<li><a href='destinationlabel.php?type=phone'>Phone Labels</a></li>
					<li><a href='destinationlabel.php?type=email'>Email Labels</a></li>
<? if(getSystemSetting('_hassms', false)){ ?>
					<li><a href='destinationlabel.php?type=sms'>SMS Labels</a></li>
<? } ?>

				</ul>

<?
		}
		// features - if contact manager, or self-signup, or smartcall appliance, or Classroom
		if ((getSystemSetting('_hasportal', false) && $USER->authorize('portalaccess') && $USER->authorize('managesystem')) ||
			(getSystemSetting('_hasselfsignup', false) && ($USER->authorize('metadata') || $USER->authorize('managesystem'))) ||
			($USER->authorize('managesystem') && getSystemSetting('_dmmethod', "")!='asp') ||
			($USER->authorize('manageclassroommessaging') && getSystemSetting('_hastargetedmessage')) ||
			($USER->authorize('managesystem') && getSystemSetting("_hasfacebook")) ||
			($USER->authorize('managesystem') && getSystemSetting("_hasfeed"))) {
?>

				<ul class="linkslist">
				
					<li class="heading">Features</li>
<?
					if (getSystemSetting('_hasportal', false) && $USER->authorize('portalaccess') && $USER->authorize('managesystem')) {
?>
					<li><a href='contactmanagersettings.php'>Contact Manager Settings</a></li>
<?
					}
					if (getSystemSetting('_hasselfsignup', false)) {
						if ($USER->authorize('managesystem')) {
?>
					<li><a href='subscribersettings.php'>Self-Signup Settings</a></li>
<?						}
						if ($USER->authorize('metadata')) {
?>
					<li><a href='subscriberfields.php'>Self-Signup Fields</a></li>
<?						}
					}
					if ($USER->authorize('managesystem') && getSystemSetting('_dmmethod', "")!='asp') {
?>
					<li><a href='dms.php'><?=_L("SmartCall Appliance")?></a></li>
<?
					}
					if (getSystemSetting('_hastargetedmessage', false) && $USER->authorize('manageclassroommessaging')) {
?>
					<li><a href='classroommessagemanager.php'>Classroom Message Manager</a></li>
					<li><a href='classroommessagetemplate.php'>Classroom Messaging Template</a></li>
<?
					}
					if (getSystemSetting("_hasfacebook")) {
?>
					<li><a href='authfacebookpages.php'>Facebook Authorized Pages</a></li>
<?					}
					if (getSystemSetting("_hasfeed")) {
?>
					<li><a href='editfeedcategory.php'><?=_L("Feed Categories")?></a></li>
<?					}
?>

				</ul>

<?
		}
?>

<?
endWindow();

include("navbottom.inc.php");
?>
