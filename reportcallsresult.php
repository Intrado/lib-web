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
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportInstance.obj.php");
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



function fmt_jobdrilldown($personid, $jobid, $jobname){
	if($personid == "" || $jobid == "")
		return null;
	$url = "<a href=\"reportdrilldown.php?id=" . $personid . "&jobid=" . $jobid . "\">" . $jobname . "</a>";
	return $url;
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
		}
	}
	return "";
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

$fields = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
foreach($fields as $key => $fieldmap){
	if(!$USER->authorizeField($fieldmap->fieldnum))
		unset($fields[$key]);
}
$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");


if(isset($_REQUEST['reportid'])){
	$reportid = $_REQUEST['reportid']+0;
	$reportinstance = new ReportInstance($reportid);
	$options = $reportinstance->getParameters();
	
	$_SESSION['saved_report'] = true;
	$activefields = $reportinstance->getActiveFields();
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
	
	unset($_SESSION['contactrules']);
	if(isset($options['rules']) && $options['rules'] != ""){
		$rules = explode("||", $options['rules']);
		foreach($rules as $rule){
			if($rule != ""){
				$rule = explode(";", $rule);
				$newrule = new Rule();
				$newrule->logical = $rule[0];
				$newrule->op = $rule[1];
				$newrule->fieldnum = $rule[2];
				$newrule->val = $rule[3];
				if(isset($_SESSION['contactrules']) && is_array($_SESSION['contactrules']))
					$_SESSION['contactrules'][] = $newrule;
				else 
					$_SESSION['contactrules'] = array($newrule);
				$newrule->id = array_search($newrule, $_SESSION['contactrules']);
				$_SESSION['contactrules'][$newrule->id] = $newrule;
			}
		}
	}
	
	$_SESSION['report']['options'] = $options;
} else {
	
	$options = isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	$activefields = array();
	$fieldlist = array();
	foreach($fields as $field){
		// used in html
		$fieldlist[$field->fieldnum] = $field->name;
		
		// used in pdf
		if(isset($_SESSION['fields']['$field->fieldnum']) && $_SESSION['fields']['$field->fieldnum']){
			$activefields[] = $field->fieldnum; 
		}
	}
	
	foreach($orders as $order){
		$_SESSION[$order] = isset($options[$order]) ? $options[$order] : "" ;
	}
	$reportinstance = new ReportInstance();
	$reportinstance->setFields($fieldlist);
	$reportinstance->setActiveFields($activefields);
}

$options['pagestart'] = $pagestart;

$reportinstance->setParameters($options);

$reportgenerator = new CallsReport();
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

if($reportgenerator->format != "html"){
	$reportgenerator->generate();
} else {
	$PAGE = "reports:reports";
	switch($options['reporttype']){
		case 'undelivered':
			$TITLE = "UnDelivered Calls";
			break;
		case 'attendance':
			$TITLE = "Attendance Calls";
			break;
		case 'emergency':
			$TITLE = "Emergency Calls";
			break;
		default:
			$TITLE = "Calls Report";
	}
	
	include_once("nav.inc.php");
	NewForm($f);
	buttons(button('back', 'window.history.go(-1)'));
	startWindow("Display Options", "padding: 3px;");
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Fields:</th>
			<td class="bottomBorder">
	<? 		
				select_metadata('searchresultstable', 4, $fields);
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
						NewFormItem($f, $s, $order, 'selectoption', "Person ID", "pkey");
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
		<tr><th align="right" class="windowRowHeader bottomBorder">Output Format:</th>
			<td class="bottomBorder"><a href="reportcallsresult.php?csv=1">CSV</a></td>
		</tr>
		<tr><td><? echo submit($f, $s, "search", "search");?></td></tr>
	</table>
	<?
	endWindow();
	?>
	<br>
	<?
			
	$reportgenerator->generate();
	EndForm();
	buttons();
	include_once("navbottom.inc.php");
}
?>
