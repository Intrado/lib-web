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
$TITLE= "Report Builder";

include("nav.inc.php");

startWindow("Select a Template");
?>
	<table width="100%" cellpadding="3" cellspacing="2" class="list" >
		<tr class="listHeader" align="left" valign="bottom">
			<th>Summary</th>
			<th>Individual</th>
			<th>Detailed</th>
		</tr>
		<tr>
			<td><a href='reportjobsearch.php?clear=1'/>Job Summary</a></td>
			<td><a href='reportcallssearch.php?clear=1&type=callsreport'/>Contact History</a></td>
			<td><a href='reportjobdetailsearch.php?clear=1&type=phone'/>Call Detail</td>
		</tr>
		<tr>
			<td><a href='reportsurvey.php?clear=1'/>Survey</a></td>
			<td>&nbsp;</td>
			<td><a href='reportjobdetailsearch.php?clear=1&type=email'/>Email Detail</a></td>	
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


startWindow("My Reports");
showObjects($data, $titles, $formatters, $scroll, true);
EndWindow();
include("navbottom.inc.php");
?>