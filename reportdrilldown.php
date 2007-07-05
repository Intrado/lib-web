<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/DrillDownReport.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function job_status($row, $index){
	switch($row[$index]){
		case 'A':
			return "Answered";
		case 'M':
			return "Machine";
		case 'B':
			return "Busy";
		case 'N':
			return "No Answer";
		case 'X':
			return "Disconnect";
		case 'fail':
		case 'F':
			return "Failed";
		case 'C':
			return "In Progress";
		case 'sent':
			return "Sent";
		case 'unsent':
			return "Unsent";
		case 'printed':
			return "Printed";
		case 'notprinted':
			return "Not Printed";
		case 'notattempted':
			return "Not Attempted";
		case 'success':
			return "Success";
		default:
			return $row[$index];
	}
}

function fmt_sequence($row, $index){
	if($row[$index] != ""){
		return $row[$index] +1;
	} else {
		return "";
	}
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


if(isset($_REQUEST['id'])){
	$_SESSION['drilldown']['id'] = $_REQUEST['id'] + 0;
}
if(isset($_REQUEST['jobid'])){
	$_SESSION['drilldown']['jobid'] = $_REQUEST['jobid'] + 0;
}
if(isset($_REQEST['id']) || isset($_REQUEST['jobid'])){
	redirect();
}

$options = array();
if(isset($_SESSION['drilldown']['id'])){
	$options['personid'] = $_SESSION['drilldown']['id'];
}
if(isset($_SESSION['drilldown']['jobid'])){
	$options['jobid'] = $_SESSION['drilldown']['jobid'];
}
$options['reporttype']="drilldown";

$fields = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
foreach($fields as $key => $fieldmap){
	if(!$USER->authorizeField($fieldmap->fieldnum))
		unset($fields[$key]);
}

$activefields = array();
$fieldlist = array();
foreach($fields as $field){
	// used in html
	$fieldlist[$field->fieldnum] = $field->name;
}

$reportinstance = new ReportInstance();
$reportinstance->setParameters($options);
$reportinstance->setFields($fieldlist);
$reportgenerator = new DrillDownReport();
$reportgenerator->format = "html";
$reportgenerator->reportinstance = $reportinstance;
$reportgenerator->userid = $USER->id;

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Individual Report Data";

include_once("nav.inc.php");
buttons(button('back', 'window.history.go(-1)'));
$reportgenerator->generate();
buttons();
include_once("navbottom.inc.php");
?>
