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
	return "<a href='reportedit.php?reportid=$obj->id' >Edit Schedule</a>&nbsp;|&nbsp;<a href='reportsavedoptions.php?reportid=$obj->id' >Edit Options</a>&nbsp;|&nbsp;<a href='reportsavedoptions.php?reportid=$obj->id&runreport=true' >View</a>&nbsp;|&nbsp;<a href='reports.php?delete=$obj->id' onclick='return confirm(\"Are you sure you want to delete this?\")';>Delete</a>";
}

function fmt_next_run($obj){
	if($obj->nextrun != null)
		return date("M j, Y g:i a", strtotime($obj->nextrun));
	return "";
}

function fmt_last_run($obj){
	if($obj->lastrun != null)
		return date("M j, Y g:i a", strtotime($obj->lastrun));
	return "";
}

function fmt_report_type($obj){
	$instance = new ReportInstance($obj->reportinstanceid);
	$options = $instance->getParameters();
	return report_name($options['reporttype']);
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
$TITLE= "Report Builder";

include("nav.inc.php");

startWindow("Select a Template"  . help('Reports_SelectATemplate'), 'padding: 3px;');
?>
	<table border="1" width="100%" cellpadding="3" cellspacing="1" class="list" >
		<tr class="listHeader">
			<th align="left" class="nosort">Job and Date Range</th>
			<th align="left" class="nosort">Individual</th>
			<th align="left" class="nosort">Survey</th>
		</tr>
		<tr align="left" valign="bottom">
			<td>
				<table>
					<tr><td><a href='reportjobsearch.php?clear=1'/>Notification Summary</a></td></tr>
					<tr><td><a href='reportjobdetailsearch.php?clear=1&type=phone'/>Phone Log</a></td></tr>
					<tr><td><a href='reportjobdetailsearch.php?clear=1&type=email'/>Email Log</a></td></tr>
				</table>
			</td>
			<td>
				<table>
					<tr><td><a href='reportcallssearch.php?clear=1'/>Contact History</a></td></tr>
					<tr><td>&nbsp;</td></tr>
					<tr><td>&nbsp;</td></tr>
				</table>
			</td>
			<td>
				<table>
					<tr><td><a href='reportsurvey.php?clear=1'/>Survey Results</a></td></tr>
					<tr><td>&nbsp;</td></tr>
					<tr><td>&nbsp;</td></tr>
				</table>
			</td>
		</tr>
	</table>
<?
endWindow();

?><br><?

$data = DBFindMany("ReportSubscription", "from reportsubscription where userid = '$USER->id'");

$titles = array("name" => "Name",
				"description" => "Description",
				"Type"	=> "Type",
				"Next Scheduled Run" => "Next Scheduled Run",
				"Last Run" => "Last Run",
				"Actions" => "Actions");
$formatters = array("Actions" => "fmt_report_actions",
					"Next Scheduled Run" => "fmt_next_run",
					"Type" => "fmt_report_type",
					"Last Run" => "fmt_last_run");
$scroll = false;

startWindow("My Saved Reports" . help('Reports_MySavedReports'), 'padding: 3px;');
showObjects($data, $titles, $formatters, $scroll, true);
EndWindow();
include("navbottom.inc.php");
?>