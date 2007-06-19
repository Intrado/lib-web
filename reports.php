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
	return "<a href='reportedit.php?reportid=$obj->id' />Edit</a>&nbsp;|&nbsp;<a href='reportsavedoptions.php?reportid=$obj->id&runreport=true' />Run Report</a>&nbsp;|&nbsp;<a href='reportsavedoptions.php?reportid=$obj->id' />Options</a>";
}

function fmt_next_run($obj){
	return $obj->nextrun;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE= "reports:reports";
$TITLE= "Reports";

include("nav.inc.php");

startWindow("Custom Reports");
?>
	<table width="100%" cellpadding="3" cellspacing="1" class="list" >
		<tr>
			<td><a href='report_job.php'/>Jobs</a></td>
			<td><a href='report_notified.php'/>Notification Search</a></td>
			<td><a href='report_notified.php?attendance=1'/>Attendance</a></td>
		</tr>
		<tr>
			<td><a href='report_survey.php'/>Surveys</a></td>
			<td><a href='report_unnotified.php'/>Undelivered Calls</a></td>
			<td><a href='report_notified.php?emergency=1'/>Emergencies</a></td>
		</tr>
	</table>
<?
endWindow();

?><br><?

$data = DBFindMany("ReportSubscription", "from reportsubscription where userid = '$USER->id'");

$titles = array("name" => "Name",
				"Next Run" => "Next Run",
				"Actions" => "Actions");
$formatters = array("Actions" => "fmt_report_actions",
					"Next Run" => "fmt_next_run");
$scroll = false;


startWindow("Saved Reports");
button_bar(button("newreport", null, "report_edit.php"));
showObjects($data, $titles, $formatters, $scroll, true);
EndWindow();
include("navbottom.inc.php");
?>