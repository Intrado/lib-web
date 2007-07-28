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
require_once("inc/reportutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("inc/date.inc.php");
require_once("obj/CallsReport.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function job_status($resulttype){
	switch($resulttype){
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
		default:
			return $resulttype;
	}
}



function fmt_drilldown($personid, $jobid){
	if($personid == "" || $jobid == "")
		return null;
	$url = "<a href=\"reportdrilldown.php?id=" . $personid . "&jobid=" . $jobid . "\"><img src=\"img/magnify.gif\"></a>";
	return $url;
}

function fmt_type($jobname, $phone, $email){
	$phoneimg = "";
	$emailimg = "";
	if($phone){
		$phoneimg = "<img src=\"img/icon_phone_12.gif\" align=\"bottom\" />";
	}
	if($email){
		$emailimg = "<img src=\"img/icon_email_12.gif\" align=\"bottom\" />";
	}
	return $phoneimg . $emailimg . $jobname;
}

function fmt_calls_result($row, $index){
	if($row[$index] == "")
		return "";
	else {
		switch($row[$index]){
			case 'success':
				return "Yes";
			case 'fail':
				return "No";
			case 'duplicate':
				return "Duplicate";
			default:
				return "No";
		}
	}
	return "";
}

function fmt_rel_date($string, $arg1="", $arg2=""){
	switch($string){
		case 'today':
			return "Today";
		case 'yesterday':
			return "Yesterday";
		case 'lastweekday':
			return "Last Week Day";
		case 'weektodate':
			return "Week to Date";
		case 'monthtodate':
			return "Month to Date";
		case 'xdays':
			return "Last $arg1 days";
		case 'daterange':
			return date("M d, Y", strtotime($arg1)) . " To: " . date("M d, Y", strtotime($arg2));
		default:
			return $string;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$f="fields";
$s="sort";
$reload = 0;

$pagestart = 0;
if(isset($_REQUEST['pagestart'])){
	$pagestart = $_REQUEST['pagestart'] + 0;
}

$orders = array("order1", "order2", "order3");

$fields = getFieldMaps();
$ordering = CallsReport::getOrdering();


if(isset($_REQUEST['reportid'])){
	$_SESSION['reportid'] = $_REQUEST['reportid'];
	$reportid = $_SESSION['reportid']+0;
	$subscription = new ReportSubscription($reportid);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	$activefields = $instance->getActiveFields();
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['fields'][$field->fieldnum] = false;
		}
	}
	foreach($orders as $order){
		$_SESSION[$order] = isset($options[$order]) ? $options[$order] : "";
	}
	$_SESSION['report']['options'] = $options;
	redirect();
} else {

	$options = isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();

	$activefields = array();
	$fieldlist = array();

	foreach($fields as $field){
		// used in html
		$fieldlist[$field->fieldnum] = $field->name;

		// used in pdf
		if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum;
		}
	}

	foreach($orders as $order){
		$_SESSION[$order] = isset($options[$order]) ? $options[$order] : "" ;
	}
	if(isset($_SESSION['reportid'])){
		$subscription = new ReportSubscription($_SESSION['reportid']);
		$instance = new ReportInstance($subscription->reportinstanceid);
	} else {
		$instance = new ReportInstance();
		$subscription = new ReportSubscription();
		$subscription->createDefaults(fmt_report_name($options['reporttype']));
	}

	$instance->setFields($fieldlist);
	$instance->setActiveFields($activefields);
}

if(isset($_SESSION['reportid'])){
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}

$options['pagestart'] = $pagestart;

$instance->setParameters($options);
$generator = new CallsReport();
$generator->reportinstance = $instance;
$generator->userid = $USER->id;

if(isset($_REQUEST['csv']) && $_REQUEST['csv']){
	$generator->format = "csv";
} else if(isset($_REQUEST['pdf']) && $_REQUEST['pdf']){
	$generator->format = "pdf";
} else {
	$generator->format = "html";
}

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, "save"))
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
			$options = $instance->getParameters();
			foreach($orders as $order){
				$options[$order] = GetFormData($f, $s, $order);
				$_SESSION[$order] = GetFormData($f, $s, $order);
			}
			$_SESSION['report']['options']= $options;
			if(CheckFormSubmit($f, "save")){
				$instance->setParameters($options);
				$instance->update();
				$subscription->reportinstanceid = $instance->id;
				$subscription->update();
				$_SESSION['reportid'] = $subscription->id;
				redirect("reportedit.php?reportid=" . $subscription->id);
			}
			redirect();
		}
	}
} else {
	$reload = 1;
}

if($reload){
	ClearFormData($f);
	PutFormData($f, $s, "order1", isset($options["order1"]) ? $options["order1"] : "");
	PutFormData($f, $s, "order2", isset($options["order2"]) ? $options["order2"] : "");
	PutFormData($f, $s, "order3", isset($options["order3"]) ? $options["order3"] : "");

}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

if($generator->format != "html"){
	if($generator->format == "pdf"){
		$name = secure_tmpname("report", ".pdf");
		$params = createPdfParams($name);
		$generator->generate($params);


		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=$name");
		header("Content-type: application/pdf");
		session_write_close();
		$fp = fopen($name, "r");
		while($line = fgets($fp)){
			echo $line;
		}
		unlink($name);
	} else {
		$generator->generate();
	}

} else {
	$PAGE = "reports:reports";
	switch($options['reporttype']){
		case 'undelivered':
			$TITLE = "UnDelivered";
			break;
		case 'attendance':
			$TITLE = "Attendance";
			break;
		case 'emergency':
			$TITLE = "Emergency";
			break;
		default:
			$TITLE = "Individual's Report";
	}
	if(isset($subscription)){
		$TITLE .= ": " . $subscription->name;
	}

	include_once("nav.inc.php");
	NewForm($f);
	buttons(button('Back', 'window.history.go(-1)'), submit($f, "save", "Save"), submit($f, $s, "Refresh"));
	startWindow("Display Options", "padding: 3px;");
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Fields:</th>
			<td class="bottomBorder">
	<?
				select_metadata('searchresultstable', 5, $fields);
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
						foreach($ordering as $index => $item){
							NewFormItem($f, $s, $order, 'selectoption', $index, $item);
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
		<tr><th align="right" class="windowRowHeader">Output Format:</th>
			<td>
				<a href="reportcallsresult.php?csv=1">CSV</a>&nbsp;|&nbsp;<a href="reportcallsresult.php?pdf=1">PDF</a>
			</td>
		</tr>
	</table>
	<?
	endWindow();
	?>
	<br>
	<?

	$generator->generate();
	buttons();
	EndForm();
	include_once("navbottom.inc.php");
}
?>
