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
require_once("inc/reportgeneratorutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Phone.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/date.inc.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/CallsReport.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$jobtypeobjs = DBFindMany("JobType", "from jobtype where deleted = '0'");
$jobtypes = array();
foreach($jobtypeobjs as $jobtype){
	$jobtypes[$jobtype->id] = $jobtype->name;
}
$fields = FieldMap::getOptionalAuthorizedFieldMaps();

$results = array("A" => "Answered",
					"M" => "Machine",
					"N" => "No Answer",
					"B" => "Busy",
					"F" => "Failed",
					"X" => "Disconnected",
					"sent" => "Sent",
					"unset" => "Unsent");	
					
if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	unset($_SESSION['reportid']);
	unset($_SESSION['report']['options']);
	redirect();
}



if(isset($_REQUEST['reportid'])){
	$_SESSION['reportid'] = $_REQUEST['reportid']+0;
	if(!userOwns("reportsubscription", $_SESSION['reportid'])){
		redirect('unauthorized.php');
	}
	$subscription = new ReportSubscription($_REQUEST['reportid']);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	
	$activefields = array();
	if(isset($options['activefields'])){
		$activefields = explode(",", $options['activefields']) ;
	}
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['fields'][$field->fieldnum] = false;
		}
	}

	redirect();
} else {

	$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	$activefields = array();
	foreach($fields as $field){
		// used in pdf,csv
		if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
			$activefields[] = $field->fieldnum; 
		}
	}
	$options['activefields'] = implode(",",$activefields);
}

if(isset($_SESSION['reportid'])){
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}
	
unset($_SESSION['reportrules']);
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
			if(isset($_SESSION['reportrules']) && is_array($_SESSION['reportrules']))
				$_SESSION['reportrules'][] = $newrule;
			else 
				$_SESSION['reportrules'] = array($newrule);
			$newrule->id = array_search($newrule, $_SESSION['reportrules']);
			$_SESSION['reportrules'][$newrule->id] = $newrule;
		}
	}
}

if(isset($_GET['deleterule'])) {
	unset($_SESSION['reportrules'][(int)$_GET['deleterule']]);
	$options['rules'] = explode("||", $options['rules']);
	unset($options['rules'][(int)$_GET['deleterule']]);
	if(count($options['rules']) == 0){
		unset($options['rules']);
	} else {
		$options['rules'] = implode("||", $options['rules']);
	}
	$options['rules'] = implode("||", $options['rules']);
	$_SESSION['report']['options'] = $options;
	if(!count($_SESSION['reportrules']) || !isset($_SESSION['reportrules']))
		$_SESSION['reportrules'] = false;
	redirect();
}

$_SESSION['report']['options'] = $options;

$f = "report";
$s = "personnotify";
$reload = 0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,"view")){
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime(GetFormData($f, $s, 'startdate'))){
			error("The start date is in an invalid format");
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime(GetFormData($f, $s, 'enddate'))){
			error("The end date is in an invalid format");
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && (strtotime(GetFormData($f, $s, 'startdate')) > strtotime(GetFormData($f, $s, 'enddate')))){
			error("The end date must be before the start date");
		} else if(GetFormData($f, $s, 'personid') == "" && GetFormData($f, $s, 'phone') == "" &&  GetFormData($f, $s, 'email')== ""){
			error("At least one search criteria must have input");
		} else {
			$options['reporttype'] = "contacthistory";
			$options['personid'] = GetFormData($f, $s, 'personid');
			$options['phone'] = GetFormData($f, $s, 'phone');
			$options['email'] = GetFormData($f, $s, 'email');
			
			$options['reldate'] = GetFormData($f, $s, "relativedate");
			
			if($options['reldate'] == "xdays"){
				$options['lastxdays'] = GetFormData($f, $s, "xdays");
			} else if($options['reldate'] == "daterange"){
				$options['startdate'] = GetFormData($f, $s, 'startdate');
				$options['enddate'] = GetFormData($f, $s, 'enddate');
			}
			
			$savedjobtypes = GetFormData($f, $s, 'jobtypes');
			if($savedjobtypes){
				$temp = array();
				foreach($savedjobtypes as $savedjobtype)
					$temp[] = DBSafe($savedjobtype);
				$options['jobtypes'] = implode("','", $temp);
			}else
				$options['jobtypes'] = "";
			
			$savedresults = GetFormData($f, $s, "results");
			if($savedresults){
				$temp = array();
				foreach($savedresults as $savedresult)
					$temp[] = DBSafe($savedresult);
				$options['results'] = implode("','", $temp);
			}else
				$options['results'] = "";
			
			$options['rules'] = isset($options['rules']) ? explode("||", $options['rules']) : array();
			$fieldnum = GetFormData($f,$s,"newrulefieldnum");
			if ($fieldnum != "") {
				$type = GetFormData($f,$s,"newruletype");

				if ($type == "text")
					$logic = "and";
				else
					$logic = GetFormData($f,$s,"newrulelogical_$type");

				if ($type == "multisearch")
					$op = "in";
				else
					$op = GetFormData($f,$s,"newruleoperator_$type");

				$value = GetFormData($f,$s,"newrulevalue_" . $fieldnum);
				if (count($value) > 0) {
					$rule = new Rule();
					$rule->logical = $logic;
					$rule->op = $op;
					$rule->val = ($type == 'multisearch' && is_array($value)) ? implode("|",$value) : $value;
					$rule->fieldnum = $fieldnum;
					if(isset($_SESSION['reportrules']) && is_array($_SESSION['reportrules']))
						$_SESSION['reportrules'][] = $rule;
					else
						$_SESSION['reportrules'] = array($rule);
					$rule->id = array_search($rule, $_SESSION['reportrules']);
					
					$options['rules'][$rule->id] = implode(";", array($rule->logical, $rule->op, $rule->fieldnum, $rule->val));
				}
			}
			$options['rules'] = implode("||", $options['rules']);

			foreach($options as $index => $option){
				if($option == "")
					unset($options[$index]);
			}
			$_SESSION['report']['options'] = $options;
			
			if(CheckFormSubmit($f, "save")){
				redirect("reportedit.php");
			}
			if(CheckFormSubmit($f,"view")){
				redirect("reportcallsresult.php");
			}
			redirect();
		}
	}
} else {
	$reload = 1;
}


if($reload){
	ClearFormData($f);
	$options = isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	PutFormData($f, $s, "relativedate", isset($options['reldate']) ? $options['reldate'] : "");
	PutFormData($f, $s, 'xdays', isset($options['lastxdays']) ? $options['lastxdays'] : "", "number");
	PutFormData($f, $s, 'personid', isset($options['personid']) ? $options['personid'] : "", 'text');
	PutFormData($f, $s, 'phone', isset($options['phone']) ? $options['phone'] : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email', isset($options['email']) ? $options['email'] : "", 'email');
	
	PutFormData($f, $s, 'startdate', isset($options['startdate']) ? $options['startdate'] : "");
	PutFormData($f, $s, 'enddate', isset($options['enddate']) ? $options['enddate'] : "");
	$savedjobtypes = array();
	if(isset($options['jobtype'])){
		$savedjobtypes = explode("','", $options['jobtype']);
	}
	PutFormData($f, $s, 'jobtype', isset($options['jobtype']) && $options['jobtype'] !="" ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, 'jobtypes', $savedjobtypes, "array", array_keys($jobtypes));
	$result = array();
	if(isset($options['result'])){
		$result = explode("','", $options['result']);
	}
	
	PutFormData($f, $s, 'result', isset($options['result']) && $options['result'] !="" ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, 'results', $result , "array", array_keys($results));
	
	PutFormData($f,$s,"newrulefieldnum","");
	PutFormData($f,$s,"newruletype","text","text",1,50);
	PutFormData($f,$s,"newrulelogical_text","and","text",1,50);
	PutFormData($f,$s,"newrulelogical_multisearch","and","text",1,50);
	PutFormData($f,$s,"newruleoperator_text","sw","text",1,50);
	PutFormData($f,$s,"newruleoperator_multisearch","in","text",1,50);
}
	


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Contact History";


if(isset($_SESSION['reportid'])){
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$TITLE .= ": " . $subscription->name;
}

include_once("nav.inc.php");

NewForm($f);
buttons(button('Back', null, 'reports.php'), submit($f, "view", "View Report"));
startWindow("Person Notification Search", "padding: 3px;");

?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Search on:</th>
	<td class="bottomBorder">
		<table>
			<tr><td>ID#: </td><td><? NewFormItem($f, $s, 'personid', 'text', '20'); ?></td></tr>
			<tr><td>Phone Number: </td><td><? NewFormItem($f, $s, 'phone', 'text', '12'); ?></td></tr>
			<tr><td>Email Address: </td><td><? NewFormItem($f, $s, 'email', 'text', '20', '100'); ?></td></tr>
		</table>
		
	</td>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Filter by:</th>
	<td class="bottomBorder">
		<table border="0" cellpadding="3" cellspacing="0" width="100%">
			<tr>
				<td><br>
					<? 
						if(!isset($_SESSION['reportrules']) || is_null($_SESSION['reportrules']))
							$_SESSION['reportrules'] = false;
						
						$RULES = &$_SESSION['reportrules'];
						$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true);
						
						include("ruleeditform.inc.php");
					?>
				<br></td>
			</tr>
		</table>
		<table>
			<tr>
				<td>
<?
					dateOptions($f, $s, "", true);
?>
				</td>
			</tr>
			<tr>
				<td>
					<table>
						<tr valign="top">
							<td><? NewFormItem($f,$s,"jobtype","checkbox",NULL,NULL,'id="jobtype" onclick="clearAllIfNotChecked(this,\'jobtypeselect\');"'); ?></td>
							<td>Job Type: </td>
							<td>
								<?
								NewFormItem($f, $s, 'jobtypes', 'selectmultiple', count($jobtypes), $jobtypes, 'id="jobtypeselect" onmousedown="setChecked(\'jobtype\');"');
								?>
							</td>
						</tr>
						<tr valign="top">
							<td><? NewFormItem($f,$s,"result","checkbox",NULL,NULL,'id="result" onclick="clearAllIfNotChecked(this,\'resultselect\');"'); ?></td>
							<td>Call Result:</td>
							<td>
								<?
								NewFormItem($f, $s, 'results', 'selectmultiple',  "6", $results, 'id="resultselect" onmousedown="setChecked(\'result\');"');
								?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</td>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Display Fields:</th>
		<td class="bottomBorder">
<? 		
			select_metadata(null, null, $fields);
?>
		</td>
	</tr>
</table>
<?
endWindow();
buttons();
EndForm();

include_once("navbottom.inc.php");
?>
