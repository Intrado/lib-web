<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
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

function runReport($reportinstance){
	$options = $reportinstance->getParameters();
	switch($options['reporttype']){
		case "surveyreport":
		case "jobreport":
			redirect("reportjobsurvey.php?reportid=$reportinstance->id");
			break;
		case "undelivered":
		case "emergency":
		case "attendance":
		case "callsreport":
			redirect("reportcallsresult.php?reportid=$reportinstance->id");
			break;
		case "contacts":
			redirect("contactresult.php?reportid=$reportinstance->id");
			break;
	}
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$reload = 0;
$f = "reports";
$s = "options";
$jobtypes = DBFindMany("JobType", "from jobtype");
$results = array("A" => "Answered",
					"M" => "Machine",
					"N" => "No Answer",
					"B" => "Busy",
					"F" => "Failed",
					"X" => "Disconnected");
$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') order by id desc");
$surveys = DBFindMany("Job", "from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') and questionnaireid is not null order by id desc");

$fieldlist = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");

$options = array();
if(isset($_REQUEST['reportid'])){
	$_SESSION['savedreport']['reportid'] = $_REQUEST['reportid']+0;
	$reportid = $_REQUEST['reportid'] +0;
} else {
	$reportid = isset($_SESSION['savedreport']['reportid']) ? $_SESSION['savedreport']['reportid'] : 0 ;
}

if($reportid){
	$reportsubscription = new ReportSubscription($reportid);
	$newreport = new ReportInstance($reportsubscription->reportinstanceid);
	$options = $newreport->getParameters();
	
	switch($options['reporttype']){
		case 'attendance':
			$options['systempriority'] = "2";
			break;
		case 'emergency':
			$options['systempriority'] = "1";
			break;
		case 'undelivered':
			$options['result'] = array("N", "B", "F", "X");
			break;
	}
	
	$_SESSION['saved_report'] = true;
	$activefields = $newreport->getActiveFields();
	if(!(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'save'))){
		foreach($fieldlist as $field){
			if(in_array($field->fieldnum, $activefields)){
				$_SESSION['fields'][$field->fieldnum] = true;
			} else {
				$_SESSION['fields'][$field->fieldnum] = false;
			}
		}
	}
	unset($_SESSION['savedrules']);
	if(isset($_REQUEST['reportid'])){
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
					if(isset($_SESSION['savedrules']) && (is_array($_SESSION['savedrules'])))
						$_SESSION['savedrules'][] = $newrule;
					else 
						$_SESSION['savedrules'] = array($newrule);
					$newrule->id = array_search($newrule, $_SESSION['savedrules']);
					$_SESSION['savedrules'][$newrule->id] = $newrule;
				}
			}
		}
	}
} else {
	redirect("reports.php");
}

if(isset($_GET['deleterule'])) {
	unset($_SESSION['savedrules'][(int)$_GET['deleterule']]);
	$options['rules'] = explode("||", $options['rules']);
	$options['rules'][(int)$_GET['deleterule']] = "";
	$options['rules'] = implode("||", $options['rules']);
	$newreport->setParameters($options);
	$newreport->update();
	if(!isset($_SESSION['savedrules']) || !count($_SESSION['savedrules']))
		$_SESSION['savedrules'] = false;
	redirect();
}

if(isset($_REQUEST['runreport'])){
	runReport($newreport);	
}

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'save') || CheckFormSubmit($f,'run'))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			$chosenreporttype = $options['reporttype'];
			$error = false;
			switch($chosenreporttype){
				case "surveyreport":
					$jobid = GetFormData($f, $s, "surveyjobid");
					$options['jobid'] = $jobid;
					$options['reporttype'] = $chosenreporttype;
					break;
				case "jobreport":
					$jobid = GetFormData($f, $s, "jobid");
					$options['jobid'] = $jobid;
					$options['reporttype'] = $chosenreporttype;
					break;
				case "undelivered":
				case "attendance":
				case "emergency":		
				case "callsreport":	
							
					$options['priority'] = GetFormData($f, $s, "priority");
					$options['personid'] = GetFormData($f, $s, "personid");
					$options['phone'] = GetFormData($f, $s, "phone");
					$options['email'] = GetFormData($f, $s, "email");
					$options['date_start'] = GetFormData($f, $s, "date_start");
					$options['date_end'] = GetFormData($f, $s, "date_end");
					$options['reldate'] = GetFormData($f, $s, "relativedate");
					if($options['reldate'] == "xdays"){
						$options['lastxdays'] = GetFormData($f, $s, "lastxdays");
					}

					foreach($options as $index => $value){
						if($value == "" || $value == null)
							unset($options[$index]);
					}
					$options['reporttype'] = $chosenreporttype;
					$result = GetFormData($f, $s, "result");
					if($result)
						$options['result'] = "'" . implode("','", $result) . "'";
					break;
				case "contacts":
					$options['phone'] = GetFormData($f, $s, "phone_search");
					$options['email'] = GetFormData($f, $s, "email_search");
					$options['reporttype'] = $chosenreporttype;
					break;
				default:
					$error = true;
					$reload=1;
					error("Bad report type chosen");
					break;
			}
			
			if(!$error){
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
						if(isset($_SESSION['savedrules']) && is_array($_SESSION['savedrules']))
							$_SESSION['savedrules'][] = $rule;
						else
							$_SESSION['savedrules'] = array($rule);
						$rule->id = array_search($rule, $_SESSION['savedrules']);
						
						$options['rules'][$rule->id] = implode(";", array($rule->logical, $rule->op, $rule->fieldnum, $rule->val));
					}
				}
				$options['rules'] = implode("||", $options['rules']);
				
				$activefields = array();
				$fields = array();
				foreach($fieldlist as $field){
					$fields[$field->fieldnum] = $field->name;
					if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
						$activefields[] = $field->fieldnum;
					}
				}
				$newreport->setParameters($options);
				$newreport->setFields($fields);
				$newreport->setActiveFields($activefields);
				$newreport->update();
				$reportsubscription->reportinstanceid = $newreport->id;
				$reportsubscription->update();
			}
			if(CheckFormSubmit($f, 'run') && !$error){
				runReport($newreport);
			} else if(CheckFormSubmit($f, 'save') && !$error){
				redirect("reports.php");
			}
		}
	}
} else{
	$reload=1;
}

if($reload){
	ClearFormData($f);
	PutFormData($f, $s, "order1", isset($options['order1']) ? $options['order1'] : "");
	PutFormData($f, $s, "order2", isset($options['order2']) ? $options['order2'] : "");
	PutFormData($f, $s, "order3", isset($options['order3']) ? $options['order3'] : "");
	$jobidreq="false";
	if(isset($options['reporttype']) && ($options['reporttype'] == 'jobreport' || $options['reporttype'] == 'surveyreport'))
		$jobidreq = "true";
	PutFormData($f, $s, "jobid", isset($options['jobid']) ? $options['jobid'] : "", null, null, null, $jobidreq);
	PutFormData($f, $s, "surveyjobid", isset($options['jobid']) ? $options['jobid'] : "");
	PutFormData($f, $s, "relativedate", isset($options['reldate']) ? $options['reldate'] : "");
	PutFormData($f, $s, 'xdays', isset($options['lastxdays']) ? $options['lastxdays'] : "", "number");
	PutFormData($f, $s, 'personid', isset($options['personid']) ? $options['personid'] : "", 'text');
	PutFormData($f, $s, 'phone', isset($options['phone']) ? $options['phone'] : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email', isset($options['email']) ? $options['email'] : "", 'email');
	PutFormData($f, $s, 'priority', isset($options['priority']) ? $options['priority'] : "");

	PutFormData($f, $s, 'phone_search', isset($options['phone']) ? $options['phone'] : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email_search', isset($options['email']) ? $options['email'] : "", 'email');
	PutFormData($f, $s, 'date_start', isset($options['date_start']) ? $options['date_start'] : "", 'text');
	PutFormData($f, $s, 'date_end', isset($options['date_end']) ? $options['date_end'] : "", 'text');
	PutFormdata($f, $s, 'result', isset($options['result']) ? $options['result'] : "" , "array", array_keys($results));
	
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
$TITLE = "Main report options";

include("nav.inc.php");
NewForm($f);
buttons(submit($f, 'save', 'save', 'save'), submit($f, 'run', 'create_report', 'create_report'));
startWindow("Options");
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top">
			<th align="right" class="windowRowHeader bottomBorder">Report:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="3" cellspacing="0">
					<tr><td>Report Name: </td><td><?=$reportsubscription->name ?></td><tr>
					<tr><td>Report Type: </td><td><?=$options['reporttype'] ?></td></tr>
				</table>
			</td>
		</tr>
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Options:</th>
			<td class="bottomBorder">
<?
			switch($options['reporttype']){
				case 'jobreport':
?>
					<table id='jobreport'>
						<tr>
							<td>Job:
								<?
									NewFormItem($f, $s, 'jobid', 'selectstart');
									NewFormItem($f, $s, 'jobid', 'selectoption', ' -- Pick a Job -- ', "");
									foreach($jobs as $job){
										NewFormItem($f, $s, 'jobid', 'selectoption', $job->name, $job->id);
									}
									NewFormItem($f, $s, 'jobid', 'selectend');
								?>
							</td>
						</tr>
					</table>
<?
					break;
				case 'surveyreport':
?>
					<table id='surveyreport'>
						<tr>
							<td>Job:
								<?
									
									NewFormItem($f, $s, 'surveyjobid', 'selectstart');
									NewFormItem($f, $s, 'surveyjobid', 'selectoption', ' -- Pick a Job -- ');
									foreach($surveys as $survey){
										NewFormItem($f, $s, 'surveyjobid', 'selectoption', $survey->name, $survey->id);
									}
									NewFormItem($f, $s, 'surveyjobid', 'selectend');
								?>
							</td>
						</tr>
					</table>
<?
					break;
				case 'undelivered':
				case 'attendance':
				case 'emergency':
				case 'callsreport':
?>
					<table id='callsreport'>
						<tr><td>Person ID: </td><td><? NewFormItem($f, $s, 'personid', 'text', '20'); ?></td></tr>
						<tr><td>Phone Number: </td><td><? NewFormItem($f, $s, 'phone', 'text', '12'); ?></td></tr>
						<tr><td>Email Address: </td><td><? NewFormItem($f, $s, 'email', 'text', '20', '100'); ?></td></tr>
						<tr><td>Date Range: </td><td><? NewFormItem($f, $s, 'date_start', 'text', '20'); ?></td><td> To: </td><td><? NewFormItem($f, $s, 'date_end', 'text', '20'); ?></td></tr>
						<tr><td>Relative Date: </td>
							<td><?
								NewFormItem($f, $s, 'relativedate', 'selectstart', null, null, "onchange='new getObj(\"xdays\").obj.disabled=(this.value!=\"xdays\")'");
								NewFormItem($f, $s, 'relativedate', 'selectoption', ' -- None -- ', '');
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Yesterday', 'yesterday');
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last Week Day', 'weekday');
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last X Days', 'xdays');
								NewFormItem($f, $s, 'relativedate', 'selectend');
								NewFormItem($f, $s, 'xdays', 'text', '3', null, "id='xdays' disabled");
								?>
							</td>
						</tr>
					<?
						if($options['reporttype'] == 'callsreport' || $options['reporttype'] == 'undelivered'){
					?>
						<tr>
							<td>Priority: </td>
							<td>
								<?
								NewFormItem($f, $s, 'priority', 'selectstart', null, null, "id='priority'");
								NewFormItem($f, $s, 'priority', 'selectoption', ' -- All -- ', '');
								foreach($jobtypes as $jobtype){
									NewFormItem($f, $s, 'priority', 'selectoption', $jobtype->name, $jobtype->id);
								}
								NewformItem($f, $s, 'priority', 'selectend');
								?>
							</td>
						</tr>
					<?
						}
						if($options['reporttype'] != 'undelivered'){
					?>
						<tr>
							<td>Call Result:</td>
							<td>
								<?
								NewFormItem($f, $s, 'result', 'selectmultiple',  "6", $results);
								?>
							</td>
						</tr>
					<? 
						}
					?>
					</table>
<?
					break;
				case 'contacts':
?>
					<table id='contacts'>
						<tr><td>Phone Number: </td><td><? NewFormItem($f, $s, 'phone_search', 'text', '12');?></td></tr>
						<tr><td>Email Address: </td><td><? NewFormItem($f, $s, 'email_search', 'text', '20', '100');?></td></tr>
					</table>
<?
					break;
			}
			if($options['reporttype'] != 'surveyreport'){
?>
				
				<table border="0" cellpadding="3" cellspacing="0" width="100%">
					<tr valign="top">
						<td><br>
							<? 
								if(!isset($_SESSION['savedrules']) || is_null($_SESSION['savedrules']))
									$_SESSION['savedrules'] = false;
								
								$RULES = &$_SESSION['savedrules'];
								$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true);
								
								include("ruleeditform.inc.php");
							?>
						<br></td>
				</table>
<?
			}
?>
			</td>
		</tr>
<?
		if(!in_array($options['reporttype'], array("surveyreport"))){
?>
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Fields</th>
			<td class="bottomBorder">
				<? select_metadata(null, null, $fieldlist);?>
			</td>
		</tr>
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Sort Order</th>
			<td class="bottomBorder">
				<table>
				<tr>
		<?
					$orders = array("order1", "order2", "order3");
					foreach($orders as $order){
?>
					<td>
<?
						NewFormItem($f, $s, $order, 'selectstart');
						NewFormItem($f, $s, $order, 'selectoption', " -- Not Selected --", "");
						NewFormItem($f, $s, $order, 'selectoption', $firstname->name, $firstname->fieldnum);
						NewFormItem($f, $s, $order, 'selectoption', $lastname->name, $lastname->fieldnum);
						foreach($fieldlist as $field){
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
<?
		}
?>
	</table>
<?
buttons();
endWindow();

include("navbottom.inc.php");
?>