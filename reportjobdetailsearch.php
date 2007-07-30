<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/form.inc.php");
require_once("inc/reportutils.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/JobDetailReport.obj.php");
require_once("obj/JobType.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

//if this user can see systemwide reports, then lock them to the customerid
//otherwise lock them to jobs that they own
if (!$USER->authorize('viewsystemreports')) {
	$userJoin = " and userid = $USER->id ";
} else {
	$userJoin = "";
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$f="reports";
$s="jobs";
$reload=0;
$clear = 0;


if(isset($_REQUEST['clear']) && $_REQUEST['clear']){
	unset($_SESSION['report']);
	unset($_SESSION['reportid']);
	$clear = 1;
}

if(isset($_REQUEST['type'])){
	$_SESSION['report']['type'] = $_REQUEST['type'];
	$clear = 1;
}

if($clear)
	redirect();

$jobtypeobjs = DBFindMany("JobType", "from jobtype");
$jobtypes = array();
foreach($jobtypeobjs as $jobtype){
	$jobtypes[$jobtype->id] = $jobtype->name;
}
$fieldlist = FieldMap::getOptionalAuthorizedFieldMaps();
$ordercount = 3;
$ordering = JobDetailReport::getOrdering();

if(isset($_REQUEST['reportid'])){
	if(!userOwns("reportsubscription", $_REQUEST['reportid']+0)){
		redirect('unauthorized.php');
	}
	$_SESSION['reportid'] = $_REQUEST['reportid']+0;
	
	$subscription = new ReportSubscription($_SESSION['reportid']+0);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	
	$_SESSION['report']['type']="phone";
	if(isset($options['reporttype'])){
		if($options['reporttype']=="emaildetail")
			$_SESSION['report']['type']="email";
	}
	$activefields = array();
	if(isset($options['activefields'])){
		$activefields = explode(",", $options['activefields']) ;
	}
	foreach($fieldlist as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['fields'][$field->fieldnum] = false;
		}
	}
	$_SESSION['saved_report'] = true;
	$_SESSION['report']['options'] = $options;
	redirect();
} else {
	$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	
	$options['reporttype'] = "phonedetail";
	if(isset($_SESSION['report']['type'])){
		if ($_SESSION['report']['type'] == "email"){
			$options['reporttype'] = "emaildetail";
		}
	}
	if(isset($_SESSION['reportid'])){
		$subscription = new ReportSubscription($_SESSION['reportid']);
		$_SESSION['saved_report'] = true;
	} else {
		$_SESSION['saved_report'] = false;
	}
	$_SESSION['report']['options'] = $options;
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
	if(!isset($options['rules'])){
		if(count($_SESSION['contactrules']) > 0){
			$_SESSION['contactrules'] = false;
		}
		redirect();
	}
	$options['rules'] = explode("||", $options['rules']);
	unset($options['rules'][(int)$_GET['deleterule']]);
	if(count($options['rules']) == 0){
		unset($options['rules']);
	} else {
		$options['rules'] = implode("||", $options['rules']);
	}
	$_SESSION['report']['options'] = $options;
	if(!count($_SESSION['reportrules']))
		$_SESSION['reportrules'] = false;
	redirect();
}


if($_SESSION['report']['type'] == "phone"){
	$results = array("A" => "Answered",
					"M" => "Machine",
					"N" => "No Answer",
					"B" => "Busy",
					"F" => "Failed",
					"X" => "Disconnected");
} else {
	$results = array("sent" => "Sent",
					"unsent" => "Unsent");
}

if(CheckFormSubmit($f, $s) || CheckFormSubmit($f, "save") || CheckFormSubmit($f, "view") || CheckFormSubmit($f, "saveview"))
{
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		
		$startdate = GetFormData($f, $s, "startdate");
		$enddate = GetFormData($f, $s, "enddate");
		
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(GetFormData($f, $s, "radioselect") == "date" && (GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($startdate)){
			error('Beginning Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if(GetFormData($f, $s, "radioselect") == "date" && (GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($enddate)){
			error('Ending Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if(GetFormData($f, $s, "radioselect") == "date" && (GetFormData($f, $s, "relativedate") == "xdays") && GetFormData($f, $s, "xdays") == ""){
			error('You must enter a number');
		} else if(GetFormData($f, $s, "radioselect") == "job" && !GetFormData($f, $s, "jobid_archived") && !GetFormData($f, $s, "jobid")){
			error("You must pick a job");
		} else {
			if($_SESSION['report']['type'] == "phone"){
				$options['reporttype'] = "phonedetail";
			} else {
				$options['reporttype'] = "emaildetail";
			}
				
			$radio = GetFormData($f, $s, "radioselect");
			switch($radio){
				case "job":
					unset($options['reldate']);
					unset($options['startdate']);
					unset($options['enddate']);
					unset($options['lastxdays']);
					$check = GetFormData($f, $s, "check_archived");
					if($check)
						$options['jobid'] = GetFormData($f, $s, "jobid_archived");
					else
						$options['jobid'] = GetFormData($f, $s, "jobid");
					$options['archived'] = $check;
					break;
				case "date":
					unset($options['jobid']);
					unset($options['archived']);
					$options['reldate'] = GetFormData($f, $s, "relativedate");
			
					if($options['reldate'] == "xdays"){
						$options['lastxdays'] = GetFormData($f, $s, "xdays");
					} else if($options['reldate'] == "daterange"){
						$options['startdate'] = $startdate;
						$options['enddate'] = $enddate;
					}
					break;
			}
			
			$result = GetFormData($f, $s, "results");
			
			if($result)
				$options['result'] = implode("','", $result);
			else
				$options['result'] = "";
			
			$savedjobtype = GetFormData($f, $s, "jobtypes");
			if($savedjobtype)
				$options['jobtypes'] = implode("','", $savedjobtype);
			else
				$options['jobtypes'] = "";
			for($i=1; $i<=$ordercount; $i++){
				$options["order$i"] = GetFormData($f, $s, "order$i");
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
			foreach($options as $index => $option){
				if($option == "")
					unset($options[$index]);
			}
			
			$_SESSION['report']['options'] = $options;
			
			if(CheckFormSubmit($f, "save")){
				$activefields = array();
				foreach($fieldlist as $field){
					if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
						$activefields[] = $field->fieldnum;
					}
				}
			
				$options['activefields'] = implode(",",$activefields);
				$_SESSION['report']['options'] = $options;
				redirect("reportedit.php");
			}
			if(CheckFormSubmit($f, "view"))
				redirect("reportjobdetails.php");
			
			redirect();
		}
	}
} else {
	$reload=1;
}


if($reload){
	ClearFormData($f, $s);
	if(!isset($options['reldate']))
		$radio = "job";
	else
		$radio = "date";
	PutFormData($f, $s, "radioselect", $radio);
	PutFormData($f, $s, "relativedate", isset($options['reldate']) ? $options['reldate'] : "today");
	PutFormData($f, $s, 'xdays', isset($options['lastxdays']) ? $options['lastxdays'] : "", "number");
	PutFormData($f, $s, "startdate", isset($options['startdate']) ? $options['startdate'] : "", "text");
	PutFormData($f, $s, "enddate", isset($options['enddate']) ? $options['enddate'] : "", "text");
	if(isset($options['archived']) && $options['archived']){
		PutFormData($f, $s, "jobid", "");
		PutFormData($f, $s, "jobid_archived", isset($options['jobid']) ? $options['jobid'] : "");
	} else {
		PutFormData($f, $s, "jobid", isset($options['jobid']) ? $options['jobid']: "");
		PutFormData($f, $s, "jobid_archived", "");
	}
	PutFormData($f, $s, "check_archived", isset($options['archived']) ? $options['archived'] : 0, "bool", "0", "1");
	$result = array();
	$checkbox=0;
	if(isset($options['result'])){
		if($options['result'] == "undelivered"){
			$result = array("F", "B", "N", "X");
		} else {
			$result = explode("','", $options['result']);
		}
		if($result != "")
			$checkbox = 1;
	}
	PutFormData($f, $s, 'result', $checkbox, "bool", 0, 1);
	PutFormData($f, $s, 'results', $result , "array", array_keys($results));
	$savedjobtypes = array();
	if(isset($options['jobtypes'])){
		$savedjobtypes = explode("','", $options['jobtypes']);
	}
	PutFormData($f, $s, 'jobtype', isset($options['jobtypes']) && $options['jobtypes'] !="" ? 1 : 0, "bool", 0, 1);
	PutFormData($f, $s, 'jobtypes', $savedjobtypes, "array", array_keys($jobtypes));
	
	for($i=1;$i<=$ordercount;$i++){
		$order="order$i";
		if($i==1){
			if(!isset($options[$order])){
				if(isset($_SESSION['reportid']))
					$orderquery = "";
				else
					$orderquery = "rp.pkey";
			} else
				$orderquery = $options[$order];
			PutFormData($f, $s, $order, $orderquery);
		} else {
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "");
		}
	}
	
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
$TITLE = "Phone Log";

if(isset($_SESSION['report']['type'])){
	if($_SESSION['report']['type'] == 'email'){
		$TITLE = "Email Log";
	} else if($_SESSION['report']['type'] == 'phone'){
		$TITLE = "Phone Log";
	}
}

if(isset($_SESSION['reportid']))
	$TITLE .= " - " . $subscription->name;

include_once("nav.inc.php");
NewForm($f);
buttons( button('Back', 'window.history.go(-1)'), submit($f, "save", "Save/Schedule"),
			submit($f, "view", "View Report", "View Report"));

//--------------- Select window ---------------
startWindow("Select", NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Report Options:</th>
		<td class="bottomBorder">
			<table>
				<tr>
					<td>
						<table>
							<tr>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "job", "id=\"job\" onclick='hide(\"daterange\"); show(\"jobs\")'");?> Job</td>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "date", "onclick='hide(\"jobs\"); show(\"daterange\")'");?> Date</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
<?
					dateOptions($f, $s, "daterange");
?>	
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" cellpadding="3" cellspacing="0" width="100%" id="jobs">
							<tr>
								<td width="1%">
								<?
									NewFormItem($f, $s, "jobid", "selectstart", null, null, "id='jobid'");
									NewFormItem($f, $s, "jobid", "selectoption", "-- Select a Job --", "");
									$jobs = DBFindMany("Job","from job where deleted = 0 and status in ('active','complete','cancelled','cancelling') $userJoin order by id desc");
							
									foreach ($jobs as $job) {
										NewFormItem($f, $s, "jobid", "selectoption", $job->name, $job->id);
									}
									NewFormItem($f, $s, "jobid_archived", "selectstart", null, null, "id='jobid_archived' style='display: none'");
									NewFormItem($f, $s, "jobid_archived", "selectoption", "-- Select a Job --", "");
									$jobs = DBFindMany("Job","from job where deleted = 2 and status!='repeating' $userJoin order by id desc");
									foreach ($jobs as $job) {
										NewFormItem($f, $s, "jobid_archived", "selectoption", $job->name, $job->id);
									}
								?>
								</td>
								<td aligh="left"><? NewFormItem($f, $s, "check_archived", "checkbox", null, null, "id='check_archived' onclick = \"setHiddenIfChecked(this, 'jobid'); setVisibleIfChecked(this, 'jobid_archived');\"") ?>
								Show archived jobs</td>
							</tr>
							
						</table>
					</td>
				</tr>
				
			</table>
		</td>
	</tr>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Filter by:</th>
		<td class="bottomBorder">
			<table width="100%">
				<tr>
					<td>
						<table border="0" cellpadding="3" cellspacing="0" width="100%" id="searchcriteria">
							<tr>
								<td>
								<? 
									if(!isset($_SESSION['reportrules']) || is_null($_SESSION['reportrules']))
										$_SESSION['reportrules'] = false;
									
									$RULES = &$_SESSION['reportrules'];
									$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true);
									
									include("ruleeditform.inc.php");
								?>
								</td>
							</tr>
						</table>
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
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table>
							<tr valign="top">
								<td><? NewFormItem($f,$s,"result","checkbox",NULL,NULL,'id="result" onclick="clearAllIfNotChecked(this,\'resultselect\');"'); ?></td>
								<td>
								<?
									if((isset($options['reporttype']) && $options['reporttype'] == "emaildetails") || $_SESSION['report']['type']=="email")
										echo "Status:";
									else
										echo "Call Result:"
								?>
								</td>
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
	</tr>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Display Fields:</th>
		<td class="bottomBorder">
			<? select_metadata(null, null, $fieldlist);?>
		</td>
	</tr>
	<tr valign="top"><th align="right" class="windowRowHeader">Sort By:</th>
		<td >
<?
			selectOrderBy($f, $s, $ordercount, $ordering);
?>
		</td>
	</tr>
</table>
<script>
	setHiddenIfChecked(check_archived, 'jobid');
	setVisibleIfChecked(check_archived, 'jobid_archived')
	if(new getObj("job").obj.checked){
		hide("daterange");
	} else {
		hide("jobs");
	}
</script>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>


