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
require_once("inc/date.inc.php");
require_once("inc/rulesutils.inc.php");
include_once("ruleeditform.inc.php");


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

$clear = 0;
$fieldlist = FieldMap::getOptionalAuthorizedFieldMaps() + FieldMap::getOptionalAuthorizedFieldMapsLike('g');

if(isset($_GET['clear']) && $_GET['clear']){
	unset($_SESSION['report']);
	unset($_SESSION['reportid']);
	$clear = 1;
}

if(isset($_GET['type'])){
	$_SESSION['report']['type'] = $_GET['type'];
	$clear = 1;
}

if($clear)
	redirect();

if(isset($_GET['reportid'])){
	if(!userOwns("reportsubscription", $_GET['reportid']+0)){
		redirect('unauthorized.php');
	}
	$_SESSION['reportid'] = $_GET['reportid']+0;

	$subscription = new ReportSubscription($_SESSION['reportid']+0);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();

	$_SESSION['report']['type']="phone";
	if(isset($options['reporttype'])){
		if($options['reporttype']=="emaildetail")
			$_SESSION['report']['type']="email";
		else if($options['reporttype'] == "notcontacted")
			$_SESSION['report']['type']="notcontacted";
		else if($options['reporttype'] == "smsdetail")
			$_SESSION['report']['type']="sms";
	}
	$activefields = array();
	if(isset($options['activefields'])){
		$activefields = explode(",", $options['activefields']) ;
	}
	foreach($fieldlist as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['report']['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['report']['fields'][$field->fieldnum] = false;
		}
	}
	$_SESSION['saved_report'] = true;
	$_SESSION['report']['options'] = $options;
	redirect();
}

$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();

if(isset($_GET['deleterule'])) {
	if(isset($options['rules'])){
		unset($options['rules'][$_GET['deleterule']]);
		if(!count($options['rules']))
			unset($options['rules']);
	}
	$_SESSION['report']['options'] = $options;
	redirect();
}

$RULES = false;
if(isset($options['rules']) && $options['rules']){
	$RULES = $options['rules'];
}

$options['reporttype'] = "phonedetail";
if(isset($_SESSION['report']['type'])){
	if ($_SESSION['report']['type'] == "email"){
		$options['reporttype'] = "emaildetail";
	} else if ($_SESSION['report']['type'] == "sms"){
		$options['reporttype'] = "smsdetail";
	}
}
if(isset($_SESSION['reportid'])){
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}
$_SESSION['report']['options'] = $options;

$jobtypeobjs = DBFindMany("JobType", "from jobtype where deleted = '0' and not issurvey order by systempriority, name");
$jobtypes = array();
foreach($jobtypeobjs as $jobtype){
	$jobtypes[$jobtype->id] = $jobtype->name;
}

$ordercount = 3;
$ordering = JobDetailReport::getOrdering();

switch($_SESSION['report']['type']){
	case "phone":
		$results = array("A" => "Answered",
							"M" => "Machine",
							"N" => "No Answer",
							"B" => "Busy",
							"F" => "Unknown",
							"X" => "Disconnected",
							"duplicate" => "Duplicate",
							"blocked" => "Blocked",
							"notattempted" => "Not Attempted",
							"declined" => "No Phone Selected",
							"confirmed" => "Confirmed",
							"notconfirmed" => "Not Confirmed",
							"noconfirmation" => "No Confirmation Response");
		break;

	case "notcontacted":
		$results = array("N" => "No Answer",
						"B" => "Busy",
						"F" => "Unknown",
						"X" => "Disconnected",
						"blocked" => "Blocked",
						"notattempted" => "Not Attempted",
						"unsent" => "Unsent",
						"declined" => "No Destination Selected");
		break;
	case "email":
		$results = array("sent" => "Sent",
						"unsent" => "Unsent",
						"duplicate" => "Duplicate",
						"declined" => "No Email Selected");

		break;
	case "sms":
		$results = array("sent" => "Sent",
						"unsent" => "Unsent",
						"duplicate" => "Duplicate",
						"declined" => "No SMS Selected");
		break;
	default:
		$results = array("A" => "Answered",
							"M" => "Machine",
							"N" => "No Answer",
							"B" => "Busy",
							"F" => "Unknown",
							"X" => "Disconnected",
							"duplicate" => "Duplicate",
							"blocked" => "Blocked",
							"notattempted" => "Not Attempted",
							"sent" => "Sent",
							"unsent" => "Unsent",
							"duplicate" => "Duplicate",
							"declined" => "No Destination Selected");
		break;
}


$f="reports";
$s="jobs";
$reload=0;

if(CheckFormSubmit($f, $s) || CheckFormSubmit($f, "save") || CheckFormSubmit($f, "view"))
{
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check

		$startdate = TrimFormData($f, $s, "startdate");
		$enddate = TrimFormData($f, $s, "enddate");

		if(GetFormData($f, $s, "relativedate") != "xdays") {
			PutFormData($f, $s, 'xdays',"", "number");
		} else {
			TrimFormData($f, $s,'xdays');
		}

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(GetFormData($f, $s, "radioselect") == "date" && (GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($startdate)){
			error('Beginning Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if(GetFormData($f, $s, "radioselect") == "date" && (GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($enddate)){
			error('Ending Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if(GetFormData($f, $s, "radioselect") == "date" && (GetFormData($f, $s, "relativedate") == "xdays") && GetFormData($f, $s, "xdays") == ""){
			error('You must enter a number for X days');
		} else if(GetFormData($f, $s, "radioselect") == "job" && !GetFormData($f, $s, "jobid_archived") && !GetFormData($f, $s, "jobid")){
			error("You must pick a job");
		} else {
			$options['reporttype'] = "phonedetail";
			if($_SESSION['report']['type'] == "email"){
				$options['reporttype'] = "emaildetail";
			}else if($_SESSION['report']['type'] == "notcontacted"){
				$options['reporttype'] = "notcontacted";
			}else if($_SESSION['report']['type'] == "sms"){
				$options['reporttype'] = "smsdetail";
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
				$options['result'] = implode("','", $temp);
			}else
				$options['result'] = "";
			for($i=1; $i<=$ordercount; $i++){
				$options["order$i"] = DBSafe(GetFormData($f, $s, "order$i"));
			}

			if($rule = getRuleFromForm($f, $s)){
				if(!isset($options['rules']))
					$options['rules'] = array();
				$options['rules'][] = $rule;
				$rule->id = array_search($rule, $options['rules']);
				$options['rules'][$rule->id] = $rule;
			}

			foreach($options as $index => $option){
				if($option == "")
					unset($options[$index]);
			}

			$_SESSION['report']['options'] = $options;

			if(CheckFormSubmit($f, "save")){
				$activefields = array();
				foreach($fieldlist as $field){
					if(isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
						$activefields[] = $field->fieldnum;
					}
				}

				$options['activefields'] = implode(",",$activefields);
				$_SESSION['report']['options'] = $options;
				ClearFormData($f);
				redirect("reportedit.php");
			}
			if(CheckFormSubmit($f, "view")){
				ClearFormData($f);
				redirect("reportjobdetails.php");
			}
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
			$result = array("F", "B", "N", "X", "notattempted", "nocontacts", "blocked", "unsent");
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

	putRuleFormData($f, $s);


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
	} else if($_SESSION['report']['type'] == 'sms'){
		$TITLE = "SMS Log";
	} else if($_SESSION['report']['type'] == 'notcontacted'){
		$TITLE = "Recipients Not Contacted";
	}
}

if(isset($_SESSION['reportid']))
	$TITLE .= " - " . escapehtml($subscription->name);

include_once("nav.inc.php");
NewForm($f);
buttons( button('Back',null, "reports.php"), submit($f, "view", "View Report"),
			submit($f, "save", "Save/Schedule"));

//--------------- Select window ---------------
startWindow("Select ".help('ReportJobDetailSearch_Select'), NULL, false);
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
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "job", "id=\"job\" onclick='$(\"daterange\").hide(); $(\"jobs\").show()'");?> Job</td>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "date", "onclick='$(\"jobs\").hide(); $(\"daterange\").show()'");?> Date</td>
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

									$jobtypefilter = "";
									if (isset($_SESSION['report']['type'])) {
										if ($_SESSION['report']['type'] == "phone") {
											$jobtypefilter = " and phonemessageid is not null ";
										} else if ($_SESSION['report']['type'] == "email") {
											$jobtypefilter = " and emailmessageid is not null ";
										} else if ($_SESSION['report']['type'] == "sms") {
											$jobtypefilter = " and smsmessageid is not null ";
										}
									}
									$jobs = DBFindMany("Job","from job j where deleted = 0 and status in ('active','complete','cancelled','cancelling') and j.questionnaireid is null $userJoin $jobtypefilter order by id desc limit 500");

									foreach ($jobs as $job) {
										NewFormItem($f, $s, "jobid", "selectoption", $job->name, $job->id);
									}
									NewFormItem($f, $s, "jobid", "selectend");
									NewFormItem($f, $s, "jobid_archived", "selectstart", null, null, "id='jobid_archived' style='display: none'");
									NewFormItem($f, $s, "jobid_archived", "selectoption", "-- Select a Job --", "");
									$jobs = DBFindMany("Job","from job j where deleted = 2 and status!='repeating' and j.questionnaireid is null $userJoin $jobtypefilter order by id desc limit 500");
									foreach ($jobs as $job) {
										NewFormItem($f, $s, "jobid_archived", "selectoption", $job->name, $job->id);
									}
									NewFormItem($f, $s, "jobid_archived", "selectend");
								?>
								</td>
								<td align="left"><? NewFormItem($f, $s, "check_archived", "checkbox", null, null, "id='check_archived' onclick = \"setHiddenIfChecked(this, 'jobid'); setVisibleIfChecked(this, 'jobid_archived');\"") ?>
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
									//$RULES declared above
									$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true, 'numeric' => true);

									drawRuleTable($f, $s, false, true, true, false);

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
								<td>Result:</td>
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
	<script>
		setHiddenIfChecked(new getObj('check_archived').obj, 'jobid');
		setVisibleIfChecked(new getObj('check_archived').obj, 'jobid_archived')
		if(new getObj("job").obj.checked){
			hide("daterange");
		} else {
			hide("jobs");
		}
	</script>
</table>

<?
endWindow();
buttons();
EndForm();
?>
<script SRC="script/calendar.js"></script>
<?
include_once("navbottom.inc.php");
?>


