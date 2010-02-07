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
			redirect("reportsurveysummary.php?reportid=$subscription->id");
		case "surveynotification":
		case "jobsummaryreport":
			redirect("reportjobsummary.php?reportid=$subscription->id");
		case "phonedetail":
		case "emaildetail":
		case "smsdetail":
		case "notcontacted":
			redirect("reportjobdetails.php?reportid=$subscription->id");
		case "contacthistory":
			redirect("reportcallsperson.php?reportid=$subscription->id");
		case "contactchangereport":
			redirect("reportcontactchangesummary.php?reportid=$subscription->id");
	}
}

function editReport($subscription){
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	switch($options['reporttype']){
		case "surveyreport":
			redirect("reportsurvey.php?reportid=$subscription->id");
		case "surveynotification":
		case "jobsummaryreport":
			redirect("reportjobsearch.php?reportid=$subscription->id");
		case "phonedetail":
		case "emaildetail":
		case "smsdetail":
		case "notcontacted":
			redirect("reportjobdetailsearch.php?reportid=$subscription->id");
		case "contacthistory":
			redirect("reportcallssearch.php?reportid=$subscription->id");
		case "contactchangereport":
			redirect("reportcontactchange.php?reportid=$subscription->id");

	}
}



////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['reportid'])){
	$reportid = $_GET['reportid'] +0;
}

if(isset($reportid)){
	if(!userOwns("reportsubscription", $reportid)){
		redirect("unauthorized.php");
	}
	$reportsubscription = new ReportSubscription($reportid);
} else {
	redirect("reports.php");
}

if(isset($_GET['runreport'])){
	runReport($reportsubscription);	
} else {
	editReport($reportsubscription);
}
?>