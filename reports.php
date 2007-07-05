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
	return "<a href='reportedit.php?reportid=$obj->id' >Edit</a>&nbsp;|&nbsp;<a href='reportsavedoptions.php?reportid=$obj->id&runreport=true' >Run Report</a>&nbsp;|&nbsp;<a href='reportsavedoptions.php?reportid=$obj->id' >Options</a>&nbsp;|&nbsp;<a href='reports.php?delete=$obj->id' onclick='return confirm(\"Are you sure you want to delete this?\")';>Delete</a>";
}

function fmt_next_run($obj){
	return $obj->nextrun;
}

function fmt_last_run($obj){
	return $obj->lastrun;
}

function fmt_report_type($obj){
	$instance = new ReportInstance($obj->reportinstanceid);
	$options = $instance->getParameters();
	return fmt_report_name($options['reporttype']);
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_REQUEST['delete'])){
	if(userOwns("reportsubscription", $_REQUEST['delete']+0)){
		$subscription = new ReportSubscription($_REQUEST['delete']+0);
		$instance = new ReportInstance($subscription->reportinstanceid);
		$instance->destroy();
		$subscription->destroy();
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE= "reports:reports";
$TITLE= "Reports";

include("nav.inc.php");

startWindow("Custom Reports");
?>
	<table width="100%" cellpadding="3" cellspacing="2" class="list" >
		<tr>
			<td><a href='reportjob.php?clear=1'/>Jobs</a></td>
			<td><a href='reportcallssearch.php?clear=1&callsreport=1'/>Individual Calls Report</a></td>
			<td><a href='reportcallssearch.php?clear=1&attendance=1'/>Attendance Calls</a></td>
		</tr>
		<tr>
			<td><a href='reportsurvey.php?clear=1'/>Surveys</a></td>
			<td><a href='reportcallssearch.php?clear=1&undelivered=1'/>Undelivered Calls</a></td>
			<td><a href='reportcallssearch.php?clear=1&emergency=1'/>Emergency Calls</a></td>
		</tr>
	</table>
<?
endWindow();

?><br><?

$data = DBFindMany("ReportSubscription", "from reportsubscription where userid = '$USER->id'");

$titles = array("name" => "Name",
				"description" => "Description",
				"Type"	=> "Type",
				"Next Run" => "Next Run",
				"Last Run" => "Last Run",
				"Actions" => "Actions");
$formatters = array("Actions" => "fmt_report_actions",
					"Next Run" => "fmt_next_run",
					"Type" => "fmt_report_type",
					"Last Run" => "fmt_last_run");
$scroll = false;


startWindow("Saved Reports");
button_bar(button("newreport", null, "reportedit.php"));
showObjects($data, $titles, $formatters, $scroll, true);
EndWindow();
include("navbottom.inc.php");
?>