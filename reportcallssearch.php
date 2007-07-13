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

$f = "report";
$s = "personnotify";
$reload = 0;


$jobtypeobjs = DBFindMany("JobType", "from jobtype");
$jobtypes = array();
foreach($jobtypeobjs as $jobtype){
	$jobtypes[$jobtype->id] = $jobtype->name;
}
$fields = getFieldMaps();
$ordering = CallsReport::getOrdering();

$orders = array("order1", "order2", "order3");
$results = array("A" => "Answered",
					"M" => "Machine",
					"N" => "No Answer",
					"B" => "Busy",
					"F" => "Failed",
					"X" => "Disconnected");	
$clear = false;

if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	unset($_SESSION['reportid']);
	unset($_SESSION['report']['options']);
	$clear=true;
}

if(isset($_REQUEST['reportid'])){
	$_SESSION['reportid'] = $_REQUEST['reportid']+0;
	if(!userOwns("reportsubscription", $_SESSION['reportid'])){
		redirect('unauthorized.php');
	}
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	$_SESSION['saved_report'] = true;
	
	if($options['reporttype']!="jobreport"){
		error_log("Expected calls report, got something else");
	}
	$activefields = $instance->getActiveFields();
	if(!(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'save'))){
		foreach($fields as $field){
			if(in_array($field->fieldnum, $activefields)){
				$_SESSION['fields'][$field->fieldnum] = true;
			} else {
				$_SESSION['fields'][$field->fieldnum] = false;
			}
		}
	}
	$clear = true;
} else {
	
	if(!isset($_SESSION['report']['options'])){
		$_SESSION['report']['options'] = array();
	}
	$types = array("callsreport", "attendance", "emergency", "undelivered");
	if(isset($_REQUEST['type']) && in_array($_REQUEST['type'], $types)) {
		$clear=true;
		$_SESSION['report']['options']['reporttype'] = $_REQUEST['type'];
	}
	
	$options = $_SESSION['report']['options'];
	
	if(isset($_SESSION['reportid'])){
		$subscription = new ReportSubscription($_SESSION['reportid']);
		$instance = new ReportInstance($subscription->reportinstanceid);
	} else {
		$subscription = new ReportSubscription();
		$subscription->createDefaults(fmt_report_name($options['reporttype']));
		$instance = new ReportInstance();
	}
	
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
	$options['rules'][(int)$_GET['deleterule']] = "";
	$options['rules'] = implode("||", $options['rules']);
	$_SESSION['report']['options'] = $options;
	if(!count($_SESSION['reportrules']) || !isset($_SESSION['reportrules']))
		$_SESSION['reportrules'] = false;
	redirect();
}

if(isset($options['reporttype'])){
	switch($options['reporttype']){
		case 'undelivered':
			$chosenresults = array("N","B","F","X");
			unset($results["A"]);
			unset($results["M"]);
			$options['result'] = implode("','", $chosenresults);
			break;
		case 'emergency':
			$options['systempriority'] = "1";
			break;
		case 'attendance':
			$options['systempriority'] = "2";
			break;
	}
}

$_SESSION['report']['options'] = $options;

if($clear)
	redirect();
	
if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, "save")|| CheckFormSubmit($f,"view")|| CheckFormSubmit($f, "saveview")){
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
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
		} else if(GetFormData($f, $s, 'personid') == "" && GetFormData($f, $s, 'phone') == "" &&  GetFormData($f, $s, 'email')== "" && !(isset($options['rules']) && $options['rules'] != "") && GetFormData($f,$s,"newrulefieldnum") == "-1"){
			error("At least one search criteria must have input");
		} else {
			$options['personid'] = GetFormData($f, $s, 'personid');
			$options['phone'] = GetFormData($f, $s, 'phone');
			$options['email'] = GetFormData($f, $s, 'email');
			$options['datestart'] = GetFormData($f, $s, 'datestart');
			$options['dateend'] = GetFormData($f, $s, 'dateend');
			
			$priorities = GetFormData($f, $s, 'priorities');
			if($priorities)
				$options['priority'] = implode("','", $priorities);

			$result = GetFormData($f, $s, "result");
			if($result)
				$options['result'] = implode("','", $result);
			
			foreach($orders as $order)
				$options[$order] = GetFormData($f, $s, $order);

			$options['reldate'] = GetFormData($f, $s, "relativedate");
			
			if($options['reldate'] == "xdays"){
				$options['lastxdays'] = GetFormData($f, $s, "xdays");
			} else if($options['reldate'] == "daterange"){
				$options['startdate'] = GetFormData($f, $s, 'startdate');
				$options['enddate'] = GetFormData($f, $s, 'enddate');
			}
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

			$_SESSION['report']['options'] = $options;
			
			if(CheckFormSubmit($f, "save") ||CheckFormSubmit($f,"saveview") ){
				$activefields = array();
				$fieldlist = array();
				foreach($fields as $field){
					$fields[$field->fieldnum] = $field->name;
					if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
						$activefields[] = $field->fieldnum;
					}
				}
			
				$instance->setFields($fieldlist);
				$instance->setActiveFields($activefields);
				$instance->setParameters($options);
				$instance->update();
				$subscription->reportinstanceid = $instance->id;
				$subscription->update();
				$_SESSION['reportid'] = $subscription->id;
				if(CheckFormSubmit($f, "save"))
					redirect("reportedit.php?reportid=" . $subscription->id);
			}
			if(CheckFormSubmit($f,"view")||CheckFormSubmit($f,"saveview")){
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
	PutFormData($f, $s, "relativedate", isset($options['reldate']) ? $options['reldate'] : "today");
	PutFormData($f, $s, 'xdays', isset($options['lastxdays']) ? $options['lastxdays'] : "", "number");
	PutFormData($f, $s, 'personid', isset($options['personid']) ? $options['personid'] : "", 'text');
	PutFormData($f, $s, 'phone', isset($options['phone']) ? $options['phone'] : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email', isset($options['email']) ? $options['email'] : "", 'email');
	
	PutFormData($f, $s, 'startdate', isset($options['startdate']) ? $options['startdate'] : "");
	PutFormData($f, $s, 'enddate', isset($options['enddate']) ? $options['enddate'] : "");
	$priority = array();
	if(isset($options['priority'])){
		$priority = explode("','", $options['priority']);
	}
	PutFormData($f, $s, 'priority', isset($options['priority']) && $options['priority'] !="" ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, 'priorities', $priority, "array", array_keys($jobtypes));
	$result = array();
	if(isset($options['result'])){
		$result = explode("','", $options['result']);
	}
	
	PutFormData($f, $s, 'result', isset($options['result']) && $options['result'] !="" ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, 'results', $result , "array", array_keys($results));
	
	PutFormData($f, $s, "order1", isset($options["order1"]) ? $options["order1"] : "rp.pkey");
	PutFormData($f, $s, "order2", isset($options["order2"]) ? $options["order2"] : "date");
	PutFormData($f, $s, "order3", isset($options["order3"]) ? $options["order3"] : "");
	
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

switch($options['reporttype']){
	case 'undelivered':
		$TITLE = "Undelivered";
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

if(isset($_SESSION['reportid'])){
	$TITLE .= ": " . $subscription->name;
}

include_once("nav.inc.php");

NewForm($f);
buttons(button('done', null, "reports.php"), submit($f, "save", "save", "save"), 
			isset($_SESSION['reportid']) ? submit($f, "saveview", "saveview", "save and view") : submit($f, "view", "view", "view report"));
startWindow("Person Notification Search", "padding: 3px;");

?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Search on:</th>
	<td class="bottomBorder">
		<table>
			<tr><td>Person ID: </td><td><? NewFormItem($f, $s, 'personid', 'text', '20'); ?></td></tr>
			<tr><td>Phone Number: </td><td><? NewFormItem($f, $s, 'phone', 'text', '12'); ?></td></tr>
			<tr><td>Email Address: </td><td><? NewFormItem($f, $s, 'email', 'text', '20', '100'); ?></td></tr>
		</table>
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
	</td>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Filter by:</th>
	<td class="bottomBorder">
		<table>
			<tr>
				<td>
					<table>
						<tr><td>Relative Date: </td>
							<td><?
								NewFormItem($f, $s, 'relativedate', 'selectstart', null, null, "id='reldate' onchange='if(this.value!=\"xdays\"){hide(\"xdays\")} else { show(\"xdays\");} if(new getObj(\"reldate\").obj.value!=\"daterange\"){ hide(\"date\");} else { show(\"date\")}'");
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Today', 'today');
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Yesterday', 'yesterday');
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last Week Day', 'weekday');
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Week to date', 'weektodate');
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Month to date', 'monthtodate');
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last X Days', 'xdays');
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Date Range(inclusive)', 'daterange');
								NewFormItem($f, $s, 'relativedate', 'selectend');
								
								?>
							</td>
							<td><? NewFormItem($f, $s, 'xdays', 'text', '3', null, "id='xdays'"); ?></td>
							<td><div id="date"><? NewFormItem($f, $s, 'startdate', 'text', '10'); ?> To: <? NewFormItem($f, $s, 'enddate', 'text', '10'); ?></div></td>
						</tr>
						<script>
							if(new getObj("reldate").obj.value!="xdays"){
								hide("xdays")
							}
							if(new getObj("reldate").obj.value!="daterange"){
								hide("date");
							
							}
						</script>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table>
		<?
			if($options['reporttype'] == 'callsreport' || $options['reporttype'] == 'undelivered'){
		?>
			
						<tr valign="top">
							<td><? NewFormItem($f,$s,"priority","checkbox",NULL,NULL,'id="priority" onclick="clearAllIfNotChecked(this,\'priorityselect\');"'); ?></td>
							<td>Job Type: </td>
							<td>
								<?
								NewFormItem($f, $s, 'priorities', 'selectmultiple', count($jobtypes), $jobtypes, 'id="priorityselect" onmousedown="setChecked(\'priority\');"');
								?>
							</td>
						</tr>
		<?
			}
		?>
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
</table>
<?
endWindow();
buttons();
EndForm();

include_once("navbottom.inc.php");
?>
