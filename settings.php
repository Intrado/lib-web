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
// Data
////////////////////////////////////////////////////////////////////////////////

$headers = array();
$systemLinks = array();
$jobLinks = array();
$labelLinks = array();
$featureLinks = array();
$linkLists = array();

if ($USER->authorize('managesystem') || $USER->authorize('metadata')) {
	$headers[] = _L("System");
	
	if ($USER->authorize('managesystem')) {
		$systemLinks[] = "<a href='systemwidealertmessage.php'>Systemwide Alert Message</a>";
		$systemLinks[] = "<a href='customerinfo.php'>Customer Information</a>";
	}
	if ($USER->authorize('metadata')) {
		$systemLinks[] = "<a href='persondatamanager.php'>Field Definitions</a>";
		$systemLinks[] = "<a href='groupdatamanager.php'>Group Field Definitions</a>";
		if (getSystemSetting('_hasenrollment', false)) {
			$systemLinks[] = "<a href='scheduledatamanager.php'>Section Field Definitions</a>";
		}
		$systemLinks[] = "<a href='organizationdatamanager.php'>Organization Manager</a>";
	}
	if ($USER->authorize('managesystem')) {
		$systemLinks[] = "<a href='securitysettings.php'>Security</a>";
		$systemLinks[] = "<a href='displaysettings.php'>Display</a>";
	}
		
	$linkLists[] = $systemLinks;
}
if ($USER->authorize('managesystem')) {
	$headers[] = getJobTitle();
	$headers[] = _L("Destination Labels");
	
	$jobLinks[] = "<a href='disablerepeatingjobs.php'>" . _L("Enable/Disable Repeating %s",getJobsTitle()) . "</a>";
	$jobLinks[] = "<a href='jobsettings.php'>" . _L("%s Settings",getJobTitle()) . "</a>";
	$jobLinks[] = "<a href='jobtypemanagement.php'>" . _L("%s Types",getJobTitle()) . "</a>";
	if (getSystemSetting("_amdtype","ivr") == "ivr") {
		$jobLinks[] = "<a href='messageintro.php'>Message Intro</a>";
	}
	
	$labelLinks[] = "<a href='destinationlabel.php?type=phone'>Phone Labels</a>";
	$labelLinks[] = "<a href='destinationlabel.php?type=email'>Email Labels</a>";
	if (getSystemSetting('_hassms', false)) {
		$labelLinks[] = "<a href='destinationlabel.php?type=sms'>SMS Labels</a>";
	}
	
	$linkLists[] = $jobLinks;
	$linkLists[] = $labelLinks;
}
// features - if contact manager, or self-signup, or smartcall appliance, or classroom
if ((getSystemSetting('_hasportal', false) && $USER->authorize('portalaccess') && $USER->authorize('managesystem')) ||
	(getSystemSetting('_hasselfsignup', false) && ($USER->authorize('metadata') || $USER->authorize('managesystem'))) ||
	($USER->authorize('managesystem') && getSystemSetting('_dmmethod', "")!='asp') ||
	($USER->authorize('manageclassroommessaging') && getSystemSetting('_hastargetedmessage')) ||
	($USER->authorize('managesystem') && getSystemSetting("_hasfacebook")) ||
	($USER->authorize('managesystem') && getSystemSetting("_hasfeed"))) {

	$headers[] = _L("Features");
	
	if (getSystemSetting('_hasportal', false) && $USER->authorize('portalaccess') && $USER->authorize('managesystem')) {
		$featureLinks[] = "<a href='contactmanagersettings.php'>Contact Manager Settings</a>";
	}
	if (getSystemSetting('_hasselfsignup', false)) {
		if ($USER->authorize('managesystem')) {
			$featureLinks[] = "<a href='subscribersettings.php'>Self-Signup Settings</a>";
		}
		if ($USER->authorize('metadata')) {
			$featureLinks[] = "<a href='subscriberfields.php'>Self-Signup Fields</a>";
		}
	}
	if ($USER->authorize('managesystem') && getSystemSetting('_dmmethod', "")!='asp') {
		$featureLinks[] = "<a href='dms.php'>" . _L("SmartCall Appliance") . "</a>";
	}
	if (getSystemSetting('_hastargetedmessage', false) && $USER->authorize('manageclassroommessaging')) {
		$featureLinks[] = "<a href='classroommessagemanager.php'>Classroom Message Manager</a>";
		$featureLinks[] = "<a href='classroommessagetemplate.php'>Classroom Messaging Template</a>";
	}
	if (getSystemSetting("_hasfacebook")) {
		$featureLinks[] = "<a href='authfacebookpages.php'>Facebook Authorized Pages</a>";
	}
	if (getSystemSetting("_hasfeed")) {
		$featureLinks[] = "<a href='editfeedcategory.php'>" . _L("Feed Categories") . "</a>";
	}
	
	$linkLists[] = $featureLinks;
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE= "admin:settings";
$TITLE= "Settings";

include("nav.inc.php");

startWindow(_L("Options"), 'padding: 3px;');
drawTableOfLists($headers, $linkLists);
endWindow();

include("navbottom.inc.php");
?>
