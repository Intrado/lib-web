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
require_once("obj/ReportSchedule.obj.php");

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
			redirect("report_survey.php?reportid=$reportinstance->id");
			break;
		case "jobreport":
			redirect("report_job.php?reportid=$reportinstance->id");
			break;
		case "emergency":
		case "attendance":
		case "callsreport":
			redirect("report_calls.php?reportid=$reportinstance->id");
			break;
		case "contacts":
			redirect("contact_result.php?reportid=$reportinstance->id");
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
$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') order by id desc");
$surveys = DBFindMany("Job", "from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') and questionnaireid is not null order by id desc");

$fieldlist = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");

$options = array();
if(isset($_REQUEST['reportid'])){
	$reportsubscription = new ReportSubscription($_REQUEST['reportid']+0);
	$newreport = new ReportInstance($reportsubscription->reportinstanceid);
	$options = $newreport->getParameters();
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
} else {
	redirect("reports.php");
}

if(isset($_REQUEST['runreport'])){
	runReport($newreport);	
}

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'save'))
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

			$reportsubscription->name = GetFormData($f, $s, "reportname");
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
					if($chosenreporttype == "attendance"){
						$options['priority'] = QuickQuery("select id from jobtype where name = 'Attendance'");
					} else if($chosenreporttype == "emergency"){
						$options['priority'] = QuickQuery("select id from jobtype where name = 'Emergency'");
					} else {
						$options['priority'] = GetFormData($f, $s, "priority");
					}
					if($chosenreporttype == "undelivered"){
						$options['unnotified'] = true;
					} else {
						$options['unnotified'] = false;
					}
					$options['personid'] = GetFormData($f, $s, "personid");
					$options['phone'] = GetFormData($f, $s, "phone");
					$options['email'] = GetFormData($f, $s, "email");
					$options['date_start'] = GetFormData($f, $s, "date_start");
					$options['date_end'] = GetFormData($f, $s, "date_end");
					if(GetFormData($f, $s, "relativedate") == "xdays"){
						$options['lastxdays'] = GetFormData($f, $s, "lastxdays");
					}

					foreach($options as $index => $value){
						if($value == "" || $value == null)
							unset($options[$index]);
					}
					$options['reporttype'] = $chosenreporttype;
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
				$activefields = array();
				$fields = array();
				foreach($fieldlist as $field){
					$fields[$field->fieldnum] = $field->name;
					if(!isset($_SESSION['fields'][$field->fieldnum]) || $_SESSION['fields'][$field->fieldnum]){
						$activefields[] = $field->fieldnum;
					}
				}
				$newreport->setParameters($options);
				$newreport->setFields($fields);
				$newreport->setActiveFields($activefields);
				$newreport->update();
				$reportsubscription->reportinstanceid = $newreport->id;
				$reportsubscription->userid= $USER->id;
				$reportsubscription->update();
			}
			if(CheckFormSubmit($f, $s) && !$error){
				runReport($newreport);
			}
			if(CheckFormSubmit($f, 'schedule') && !$error){
				redirect("report_scheduler.php");
			}
			redirect("reports.php");
		}
	}
} else{
	$reload=1;
}



if($reload){
	ClearFormData($f);
	PutFormData($f, $s, 'reportname', isset($reportsubscription) ? $reportsubscription->name : "", "text", "0", "255", true);
	PutFormData($f, $s, 'reporttype', isset($options['reporttype']) ? $options['reporttype'] : "", "", "", "", true);
	PutFormData($f, $s, "order1", isset($options['order1']) ? $options['order1'] : "");
	PutFormData($f, $s, "order2", isset($options['order2']) ? $options['order2'] : "");
	PutFormData($f, $s, "order3", isset($options['order3']) ? $options['order3'] : "");
	PutFormData($f, $s, "jobid", isset($options['jobid']) ? $options['jobid'] : "");
	PutFormData($f, $s, "surveyjobid", isset($options['jobid']) ? $options['jobid'] : "");
	PutFormData($f, $s, "relativedate", "");
	PutFormData($f, $s, 'personid', isset($options['personid']) ? $options['personid'] : "", 'text');
	PutFormData($f, $s, 'phone', isset($options['phone']) ? $options['phone'] : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email', isset($options['email']) ? $options['email'] : "", 'email');
	PutFormData($f, $s, 'priority', isset($options['priority']) ? $options['priority'] : "");
	PutFormData($f, $s, 'xdays', "");
	PutFormData($f, $s, 'phone_search', isset($options['phone']) ? $options['phone'] : "", 'phone', "7", "10");
	PutFormData($f, $s, 'email_search', isset($options['email']) ? $options['email'] : "", 'email');
	PutFormData($f, $s, 'date_start', isset($options['date_start']) ? $options['date_start'] : "", 'text');
	PutFormData($f, $s, 'date_end', isset($options['date_end']) ? $options['date_end'] : "", 'text');
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Main report options";

include("nav.inc.php");
NewForm($f);
buttons(submit($f, 'save', 'save', 'save'), submit($f, $s, 'create_report', 'create_report'));
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
								NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last Work Day', 'workday');
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
								NewFormItem($f, $s, 'priority', 'selectoption', ' -- None -- ', '');
								foreach($jobtypes as $jobtype){
									NewFormItem($f, $s, 'priority', 'selectoption', $jobtype->name, $jobtype->id);
								}
								NewformItem($f, $s, 'priority', 'selectend');
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