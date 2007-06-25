<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Phone.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("inc/form.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("inc/date.inc.php");
require_once("obj/JobReport.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////
function fmt_result ($row,$index) {
	if ($row[3] == "phone") {
		if ($row[9] == "duplicate")
			return "Duplicate";
		switch($row[$index]) {
			case "A":
				return "Answered";
			case "M":
				return "Machine";
			case "B":
				return "Busy";
			case "N":
				return "No Answer";
			case "X":
				return "Disconnect";
			case "F":
				return "Failed";
			case "C":
				return "In Progress";
			default:
				return "";
		}
	} else {
		if ($row[9] == "success")
			return "Success";
		else if ($row[9] == "fail")
			return "Failed";
		else if ($row[9] == "duplicate")
			return "Duplicate";
		else
			return "In Progress";
	}
}

function fmt_attempts ($row,$index) {

	return $row[$index];
	/*
	if ($row[$index] !== NULL && $row[$index] !== "") {
		if ($row[3] == "phone") {
			return $row[$index] . "/" . $row[];
		} else {
			return $row[$index] . "/1";
		}
	} else {
		return "";
	}
	*/
}

function fmt_message ($row,$index) {
	return '<img src="img/icon_' . $row[$index] . '_12.gif" align="bottom" />&nbsp;' . htmlentities($row[$index+1]);
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$f="report";
$s="order";

$reload = 0;
$pagestart=0;
if(isset($_GET['pagestart'])){
	$pagestart = $_GET['pagestart'];
}

$fields = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
$fieldmap = DBFindMany("FieldMap", "from fieldmap");
foreach($fields as $key => $fieldmap){
	if(!$USER->authorizeField($fieldmap->fieldnum))
		unset($fields[$key]);
}
$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");

$orders = array("order1", "order2", "order3");

$options = $_SESSION['report']['options'];
if(isset($options['jobid'])){
	$jobid = $options['jobid'];
	if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports') && customerOwns("job",$jobid)))
		redirect('unauthorized.php');
}
$activefields = array();
$fieldlist = array();
foreach($fields as $field){
	// used in html
	$fieldlist[$field->fieldnum] = $field->name;
	
	// used in pdf
	if(!isset($_SESSION['fields']['$field->fieldnum']) || $_SESSION['fields']['$field->fieldnum']){
		$activefields[] = $field->fieldnum; 
	}
}

foreach($orders as $order){
	$options[$order] = isset($_SESSION[$order]) ? $_SESSION[$order] : "";
}

unset($_SESSION['jobstats']);
if(isset($jobid)){
	$job = new Job($jobid);	
}
$options["reporttype"] = "jobreport";
$options["detailed"] = true;
$options["pagestart"] = $pagestart;

$reportinstance = new ReportInstance();
$reportinstance->setParameters($options);
$reportinstance->setFields($fieldlist);
$reportinstance->setActiveFields($activefields);
$reportgenerator = new JobReport();
$reportgenerator->reportinstance = $reportinstance;
$reportgenerator->userid = $USER->id;

if(isset($_REQUEST['csv']) && $_REQUEST['csv']){
	$reportgenerator->format = "csv";
} else {
	$reportgenerator->format = "html";
}



if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$orderquery = "";
			$options = $reportinstance->getParameters();
			foreach($orders as $order){
				$options[$order] = GetFormData($f, $s, $order);
				$_SESSION[$order] = GetFormData($f, $s, $order);
			}

			$_SESSION['report']['options']= $options;
			redirect();
		}
	}
} else {
	$reload = 1;
}

if($reload){
	ClearFormData($f);
	foreach($orders as $order){
		PutFormData($f, $s, $order, isset($_SESSION[$order]) ? $_SESSION[$order] : "");
	}

}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

if(isset($_REQUEST['csv']) && $_REQUEST['csv']){
	$reportgenerator->generate();
} else {
	if($_SESSION['reporttype'] == "surveyreport"){
		$PAGE = "reports:reports";
		$TITLE = "Standard Survey Report" . ($jobid ? " - " . $job->name : "");
	} else {
		$PAGE = "reports:reports";
		$TITLE = "Standard Job Report" . ($jobid ? " - " . $job->name : "");
	}
	include_once("nav.inc.php");
	NewForm($f);	
	
	startWindow("Display Options", "padding: 3px;");
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Fields:</th>
			<td class="bottomBorder">
	<? 		
				select_metadata('reportdetailstable', 7, $fields);
	?>
			</td>
		</tr>
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Sort by:</th>
			<td class="bottomBorder">
				<table>
					<tr>
	<?
					foreach($orders as $order){
	?>
					<td>
	<?
						NewFormItem($f, $s, $order, 'selectstart');
						NewFormItem($f, $s, $order, 'selectoption', " -- Not Selected --", "");
						NewFormItem($f, $s, $order, 'selectoption', $firstname->name, $firstname->fieldnum);
						NewFormItem($f, $s, $order, 'selectoption', $lastname->name, $lastname->fieldnum);
						foreach($fields as $field){
							NewFormItem($f, $s, $order, 'selectoption', $field->name, $field->fieldnum);
						}
						NewFormItem($f, $s, $order, 'selectend');
	?>
					</td>
	<?
				}
	?>
				
					</tr>
				</table>
			</td>
		</tr>
		<tr><td><? echo submit($f, $s, "filter", "search");?></td></tr>
	</table>
	<?
	endWindow();
	?>
	<br>
	<?
	
	if(isset($reportgenerator)){
		$reportgenerator->generate("detailed");
	}
	
	endForm();
	include_once("navbottom.inc.php");
}
?>
