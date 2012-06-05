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


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_report_actions($obj){

	return action_links(
		action_link(_L("Edit Schedule"),"calendar","reportedit.php?reportid=$obj->id"),
		action_link(_L("Edit Options"),"pencil","reportsavedoptions.php?reportid=$obj->id"),
		action_link(_L("View"),"layout_header","reportsavedoptions.php?reportid=$obj->id&runreport=true"),
		action_link(_L("Delete"),"cross","reports.php?delete=$obj->id","return confirmDelete();")
	);
}

function fmt_report_type($obj){
	$instance = new ReportInstance($obj->reportinstanceid);
	$options = $instance->getParameters();
	return report_name($options['reporttype']);
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['delete'])){
	$delete = $_GET['delete'] +0;
	if(userOwns("reportsubscription", $delete)){
		$subscription = new ReportSubscription($delete);
		$instance = new ReportInstance($subscription->reportinstanceid);
		Query("BEGIN");
			$instance->destroy();
			$subscription->destroy();
		Query("COMMIT");
		notice(_L("The report, %s, is now deleted.", escapehtml($subscription->name)));
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE= "reports:reports";
$TITLE= "Report Builder";

include("nav.inc.php");

startWindow("Select a Template"  . help('Reports_SelectATemplate'), 'padding: 3px;');
?>
	<table class="list" >
	<thead>
		<tr class="listHeader">
			<th align="left" class="nosort"><?= _L("%s and Date Range", getJobsTitle()) ?></th>
			<th align="left" class="nosort"><?= _L("Individual")?></th>
<? if ($USER->authorize('viewsystemreports')) { ?>
			<th align="left" class="nosort"><?= _L("Other")?></th>
<? } ?>
		</tr>
	</thead>
	<tbody>
		<tr align="left" valign="top">
			<td>
				<ul>
					<li><a href='reportjobsearch.php?clear=1' ><?= _L("%s Summary", getJobTitle()) ?></a></li>
<? if($USER->authorize('viewsystemreports') || $USER->authorize("sendphone")){ ?>
					<li><a href='reportjobdetailsearch.php?clear=1&type=phone' >Phone Log</a></li>
<?
	}
	if($USER->authorize('viewsystemreports') || $USER->authorize("sendemail")){
?>
					<li><a href='reportjobdetailsearch.php?clear=1&type=email' >Email Log</a></li>
<?
	}
	if(getSystemSetting('_hassms', false) && ($USER->authorize('viewsystemreports') || $USER->authorize("sendsms"))) {
?>
					<li><a href='reportjobdetailsearch.php?clear=1&type=sms' >SMS Log</a></li>
<?	}
	if((getSystemSetting('_hasfacebook', false) || getSystemSetting('_hastwitter', false) && ($USER->authorize('viewsystemreports') || $USER->authorize("facebookpost") || $USER->authorize("twitterpost")))) {
?>
					<li><a href='reportsocialmediasearch.php?clear=1' >Social Media Log</a></li>
<?	}
	if(getSystemSetting('_hassurvey', true) && ($USER->authorize('viewsystemreports') || $USER->authorize("survey"))){ ?>
					<li><a href='reportsurvey.php?clear=1' >Survey Results</a></li>
<?	}
	if(getSystemSetting('_hastargetedmessage', false) && $USER->authorize('viewsystemreports')){ // Top level permission only
?>
					<li><a href='reportclassroomsearch.php?clear=1&type=organization'>Classroom Messaging Summary</a></li>
<? } ?>
				</ul>
			</td>
			<td>
				<ul>
					<li><a href='reportcallssearch.php?clear=1' >Contact History</a></li>
<?	
	if(getSystemSetting('_hastargetedmessage', false) && $USER->authorize('viewsystemreports')){ // Top level permission only
?>
					<li><a href='reportclassroomsearch.php?clear=1&type=person' >Classroom Contact History</a></li>
<?  } ?>
					<li>&nbsp;</li>
					<li>&nbsp;</li>
					<li>&nbsp;</li>
					<li>&nbsp;</li>
					<li>&nbsp;</li>
				</ul>
			</td>
<? if ($USER->authorize('viewsystemreports')) { ?>
			<td>
				<ul>
					<li><a href='reportarchive.php' >Systemwide Report Archive</a></li>
					<li><a href='reportcontactchange.php?clear=1' >Contact Information Changes</a></li>
					<li>&nbsp;</li>
					<li>&nbsp;</li>
					<li>&nbsp;</li>
					<li>&nbsp;</li>
					<li>&nbsp;</li>
				</ul>
			</td>
<? } ?>
		</tr>
	</tbody>
	</table>
<?
endWindow();

?>


<?

$data = DBFindMany("ReportSubscription", "from reportsubscription where userid = ?", false, array($USER->id));

$titles = array("name" => "Name",
				"description" => "Description",
				"Type"	=> "Type",
				"nextrun" => "Next Scheduled Run",
				"lastrun" => "Last Run",
				"Actions" => "Actions");
$formatters = array("Actions" => "fmt_report_actions",
					"nextrun" => "fmt_obj_date",
					"Type" => "fmt_report_type",
					"lastrun" => "fmt_obj_date");
$scroll = false;

startWindow("My Saved Reports" . help('Reports_MySavedReports'), 'padding: 3px;');
showObjects($data, $titles, $formatters, $scroll, true);
EndWindow();
include("navbottom.inc.php");
?>
