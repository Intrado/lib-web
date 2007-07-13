<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/reportutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/form.inc.php");
require_once("obj/JobType.obj.php");
require_once("obj/Job.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/date.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function runReport($subscription){
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	switch($options['reporttype']){
		case "surveyreport":
		case "jobreport":
			redirect("reportjobsurvey.php?reportid=$subscription->id");
			break;
		case "undelivered":
			redirect("reportundeliveredresult.php?reportid=$subscription->id");
		case "emergency":
		case "attendance":
		case "callsreport":
			redirect("reportcallsresult.php?reportid=$subscription->id");
			break;
	}
}

function editReport($subscription){
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	switch($options['reporttype']){
		case "surveyreport":
			redirect("reportsurvey.php?reportid=$subscription->id");
		case "jobreport":
			redirect("reportjob.php?reportid=$subscription->id");
		case "undelivered":
			redirect("reportundeliveredsearch.php?reportid=$subscription->id");
		case "emergency":
		case "attendance":
		case "callsreport":
			redirect("reportcallssearch.php?reportid=$subscription->id");
	}
}



////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_REQUEST['reportid'])){
	$reportid = $_REQUEST['reportid'] +0;
}

if(isset($reportid)){
	if(!userOwns("reportsubscription", $reportid)){
		redirect("unauthorized.php");
	}
	$reportsubscription = new ReportSubscription($reportid);
} else {
	redirect("reports.php");
}

if(isset($_REQUEST['runreport'])){
	runReport($reportsubscription);	
} else {
	editReport($reportsubscription);
}
?>