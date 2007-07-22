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
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
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

if(isset($_REQUEST['reportid'])){
	$_SESSION['reportid'] = $_REQUEST['reportid']+0;
	$clear = 1;
}

if($clear)
	redirect();

$orders = array("order1", "order2", "order3");
$fieldlist = getFieldMaps();
$ordering = JobDetailReport::getOrdering();

$results = array("A" => "Answered",
					"M" => "Machine",
					"N" => "No Answer",
					"B" => "Busy",
					"F" => "Failed",
					"X" => "Disconnected",
					"unsent" => "Unsent",
					"sent" => "Sent");

if(isset($_SESSION['reportid'])){
	if(!userOwns("reportsubscription", $_SESSION['reportid']+0)){
		redirect('unauthorized.php');
	}
	$subscription = new ReportSubscription($_SESSION['reportid']+0);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	
	if(isset($options['type'])){
		$_SESSION['report']['type'] = $options['type'];
	} else {
		unset($_SESSION['report']['type']);
	}
	$activefields = $instance->getActiveFields();
	if(!(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'save'))){
		foreach($fieldlist as $field){
			if(in_array($field->fieldnum, $activefields)){
				$_SESSION['fields'][$field->fieldnum] = true;
			} else {
				$_SESSION['fields'][$field->fieldnum] = false;
			}
		}
	}
	$_SESSION['saved_report'] = true;
	
	if($options['reporttype']!="jobdetailreport"){
		error_log("Expected job detail report, got something else");
	}
} else {
	$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	
	$options['reporttype'] = "jobdetailreport";
	if(isset($_SESSION['report']['type'])){
		if($_SESSION['report']['type'] == "phone"){
			$options['reporttype'] = "calldetail";
		} else if ($_SESSION['report']['type'] == "email"){
			$options['reporttype'] = "emaildetail";
		}
	}
	
	$_SESSION['saved_report'] = false;

	$subscription = new ReportSubscription();
	$subscription->createDefaults(fmt_report_name($options['reporttype']));
	$instance = new ReportInstance();

}

$_SESSION['report']['options'] = $options;

if(CheckFormSubmit($f, $s) || CheckFormSubmit($f, "save") || CheckFormSubmit($f, "run") || CheckFormSubmit($f, "saveview"))
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
		} else if(GetFormData($f, $s, "radioselect") != "1" && (GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($startdate)){
			error('Beginning Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if(GetFormData($f, $s, "radioselect") != "1" && (GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($enddate)){
			error('Ending Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else {
			$error=false;
			$radio = GetFormData($f, $s, "radioselect");
			switch($radio){
				case "1":
					$check = GetFormData($f, $s, "check_archived");
					if($check)
						$options['jobid'] = GetFormData($f, $s, "jobid_archived");
					else
						$options['jobid'] = GetFormData($f, $s, "jobid");
					if(!$options['jobid']){
						error("You Must Pick A job");
						$error = true;
					}
					$options['archived'] = $check;
					break;
				case "2":
					unset($options['archived']);
					unset($options['jobid']);
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
			
			foreach($orders as $order){
				$options[$order] = GetFormData($f, $s, $order);
			}
			foreach($options as $index => $option){
				if($option == "")
					unset($options[$index]);
			}
			
			$options['reporttype'] = "jobdetailreport";
			if(isset($_SESSION['report']['type'])){
				$options['type'] = $_SESSION['report']['type'];
				if($options['type'] == "phone"){
				$options['reporttype'] = "calldetail";
				} else if($options['type'] == "email"){
					$options['reporttype'] = "emaildetail";
				}
			}
			
			$_SESSION['report']['options'] = $options;
			
			if(!$error && (CheckFormSubmit($f, "save") || CheckFormSubmit($f, "saveview"))){
				$activefields = array();
				$fields = array();
				foreach($fieldlist as $field){
					$fields[$field->fieldnum] = $field->name;
					if(isset($_SESSION['fields'][$field->fieldnum]) && $_SESSION['fields'][$field->fieldnum]){
						$activefields[] = $field->fieldnum;
					}
				}
			
				$instance->setFields($fields);
				$instance->setActiveFields($activefields);
				$instance->setParameters($options);
				$instance->update();
				$subscription->reportinstanceid = $instance->id;
				$subscription->update();
				$_SESSION['reportid'] = $subscription->id;
				if(CheckFormSubmit($f, "save"))
					redirect("reportedit.php?reportid=" . $subscription->id);
			}
			if(!$error && (CheckFormSubmit($f, "run") || CheckFormSubmit($f, "saveview") ))
				redirect("reportjobdetails.php");
		}
	}
} else {
	$reload=1;
}


if($reload){
	ClearFormData($f, $s);
	if(!isset($options['reldate']))
		$radio = 1;
	else
		$radio = 2;
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
		$result = explode("','", $options['result']);
		if($result != "")
			$checkbox = 1;
	}
	PutFormData($f, $s, 'result', $checkbox, "bool", 0, 1);
	PutFormData($f, $s, 'results', $result , "array", array_keys($results));
	
	
	PutFormData($f, $s, "order1", isset($options['order1']) ? $options['order1'] : "rp.pkey");
	PutFormData($f, $s, "order2", isset($options['order2']) ? $options['order2'] : "");
	PutFormData($f, $s, "order3", isset($options['order3']) ? $options['order3'] : "");

}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Job Details";

if(isset($_SESSION['report']['type'])){
	if($_SESSION['report']['type'] == 'email'){
		$TITLE = "Email Detail";
	} else if($_SESSION['report']['type'] == 'phone'){
		$TITLE = "Call Detail";
	}
}

if(isset($_SESSION['reportid']))
	$TITLE .= " " . $subscription->name;

include_once("nav.inc.php");
NewForm($f);
buttons( button('done', "location.href='reports.php'"), submit($f, "save", "save", "save"),
			isset($_SESSION['reportid']) ? submit($f, "saveview", "saveview", "Save and View") : submit($f, "run", "View Report", "View Report"));

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
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "1", "onclick='hide(\"daterange\"); show(\"jobs\")'");?> Job</td>
								<td><? NewFormItem($f, $s, "radioselect", "radio", null, "2", "onclick='hide(\"jobs\"); show(\"daterange\")'");?> Date</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table  border="0" cellpadding="3" cellspacing="0" width="100%" id="daterange">
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
								<td><div id="date"><? NewFormItem($f, $s, "startdate", "text", "20") ?> To: <? NewFormItem($f, $s, "enddate", "text", "20")?></div></td>
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
						<table border="0" cellpadding="3" cellspacing="0" width="100%" id="jobs">
							<tr>
								<td width="1%">
								<?
									NewFormItem($f, $s, "jobid", "selectstart", null, null, "id='jobid'");
									NewFormItem($f, $s, "jobid", "selectoption", "-- Select a Job --", "");
									$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') order by id desc");
							
									foreach ($jobs as $job) {
										NewFormItem($f, $s, "jobid", "selectoption", $job->name, $job->id);
									}
									NewFormItem($f, $s, "jobid_archived", "selectstart", null, null, "id='jobid_archived' style='display: none'");
									NewFormItem($f, $s, "jobid_archived", "selectoption", "-- Select a Job --", "");
									$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 2 and status!='repeating' order by id desc");
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
<?
	if(!isset($_SESSION['report']['type']) || $_SESSION['report']['type'] != 'email'){		
?>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Filter by:</th>
		<td class="bottomBorder">
			<table>
				<tr>
					<td>
						<table>
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
	</tr>
<?
	}
?>
	<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Display Fields</th>
		<td class="bottomBorder">
			<? select_metadata(null, null, $fieldlist);?>
		</td>
	</tr>
	<tr valign="top"><th align="right" class="windowRowHeader">Sort Order</th>
		<td >
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
<script>
	setHiddenIfChecked(check_archived, 'jobid');
	setVisibleIfChecked(check_archived, 'jobid_archived')
	<?
		if(!isset($options['reldate'])){
			?>hide("daterange");<?
		} else {
			?>hide("jobs");<?
		}
	?>
</script>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>


