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
	<table border="1" width="100%" cellpadding="3" cellspacing="1" class="list" >
		<tr class="listHeader">
			<th align="left" class="nosort">Job and Date Range</th>
			<th align="left" class="nosort">Individual</th>
<? if ($USER->authorize('viewsystemreports')) { ?>
			<th align="left" class="nosort">Other</th>
<? } ?>
		</tr>
		<tr align="left" valign="top">
			<td>
				<table>
					<tr><td><a href='reportjobsearch.php?clear=1'/>Notification Summary</a></td></tr>
<? if($USER->authorize('viewsystemreports') || $USER->authorize("sendphone")){ ?>
					<tr><td><a href='reportjobdetailsearch.php?clear=1&type=phone'/>Phone Log</a></td></tr>
<?
	}
	if($USER->authorize('viewsystemreports') || $USER->authorize("sendemail")){
?>
					<tr><td><a href='reportjobdetailsearch.php?clear=1&type=email'/>Email Log</a></td></tr>
<?
	}
	if(getSystemSetting('_hassms', false) && ($USER->authorize('viewsystemreports') || $USER->authorize("sendsms"))) {
?>
					<tr><td><a href='reportjobdetailsearch.php?clear=1&type=sms'/>SMS Log</a></td></tr>
<?	}
	if(getSystemSetting('_hassurvey', true) && ($USER->authorize('viewsystemreports') || $USER->authorize("survey"))){ ?>
					<tr><td><a href='reportsurvey.php?clear=1'/>Survey Results</a></td></tr>
<? } ?>
				</table>
			</td>
			<td>
				<table>
					<tr><td><a href='reportcallssearch.php?clear=1'/>Contact History</a></td></tr>
				</table>
			</td>
<? if ($USER->authorize('viewsystemreports')) { ?>
			<td>
				<table>
					<tr><td><a href='reportarchive.php'/>Systemwide Report Archive</a></td></tr>
				</table>
			</td>
<? } ?>
		</tr>
	</table>
<?
endWindow();

?><br><?

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
