<?
////////////////////////////////////////////////////////////////////////////////
// Formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_survey_graph($row, $index){
	global $jobid;
	echo "<div><img src=\"graph_survey_result.png.php?jobid=" . $jobid . "&question=" . ($row[0] -1) . "&valid=".$row[14] ."\"></div>";
	
}

function fmt_question($row, $index){
	return "<div style='font-weight:bold'>$row[$index]</div><br><div>$row[2]</div>";	
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$fields = DBFindMany("FieldMap", "from fieldmap where options not like '%firstname%' and options not like '%lastname%'");
$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");

$orders = array("order1", "order2", "order3");


if(isset($_REQUEST['reportid'])){
	$reportinstance = new ReportInstance($_REQUEST['reportid']);
	$reportgenerator = new ReportGenerator();
	$reportgenerator->reportinstance = $reportinstance;
	$reportgenerator->format = "html";
	$params = $reportinstance->getParameters();
	$jobid = $params['jobid'];
	
	$activefields = $reportinstance->getActiveFields();
	foreach($fields as $field){
		if(in_array($field->fieldnum, $activefields)){
			$_SESSION['fields'][$field->fieldnum] = true;
		} else {
			$_SESSION['fields'][$field->fieldnum] = false;
		}
	}
	$_SESSION['reporttype'] = $params['reporttype'];
	$job = new Job($jobid);
	$reportinstance->setParameters($params);
	$_SESSION['saved_report'] = true;
} else if (isset($_GET['jobid'])) {
	$jobid = $_GET['jobid'] + 0;
	//check userowns or customerowns and viewsystemreports
	if (!userOwns("job",$jobid) && !($USER->authorize('viewsystemreports') && customerOwns("job",$jobid))) {
		redirect('unauthorized.php');
	}
	if ($jobid) {
	
		$options = array("jobid" => $jobid);
		
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
			$options[$order] = isset($_SESSION[$order]) ? $_SESSION[$order] : "";
		}
		
		unset($_SESSION['jobstats'][$jobid]);
		$job = new Job($jobid);	
		
		$options["reporttype"] = $_SESSION['reporttype'];
		$reportinstance = new ReportInstance();
		$reportinstance->setParameters($options);
		$reportinstance->setFields($fieldlist);
		$reportinstance->setActiveFields($activefields);
		$reportgenerator = new ReportGenerator();
		$reportgenerator->reportinstance = $reportinstance;
		$reportgenerator->format = "html";
	}
	$_SESSION['saved_report'] = false;
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
if($_SESSION['reporttype'] == "surveyreport"){
	$TITLE = "Standard Survey Report" . ((isset($jobid) && $jobid) ? " - " . $job->name : "");
} else {
	$TITLE = "Standard Job Report" . ((isset($jobid) && $jobid) ? " - " . $job->name : "");
}
include_once("nav.inc.php");

//TODO buttons for notification log: download csv, view call details
if (isset($jobid) && $jobid)
	echo buttons(button('refresh', 'window.location.reload()'), button('done', 'window.history.go(-1)'));
else
	buttons();


//--------------- Select window ---------------
startWindow("Select", NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
<tr>
	<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Job:</th>
	<td width="1%">
	<select name="jobid" id="jobid" onchange="location.href='?jobid=' + this.value">
			<option value='0'>-- Select a Job --</option>
<?
if($_SESSION['reporttype'] == "jobreport"){
	$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') order by id desc");
} else if($_SESSION['reporttype'] == "surveyreport"){
	$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status in ('active','complete','cancelled','cancelling') and questionnaireid is not null order by id desc");
}

foreach ($jobs as $job) {
	echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
}
?>
	</select>
	<select id="jobid_archived" style="display: none" onchange="location.href='?jobid=' + this.value">
			<option value='0'>-- Select a Job --</option>
<?
if($_SESSION['reporttype'] == "jobreport"){
	$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 2 and status!='repeating' order by id desc");
} else if($_SESSION['reporttype'] == "surveyreport"){
	$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 2 and status in ('active','complete','cancelled','cancelling') and questionnaireid is not null order by id desc");
}
foreach ($jobs as $job) {
	echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
}
?>
	</select></td>
	<td aligh="left"><input id="check_archived" type="checkbox" name="check_archived" value="true" onclick = "setHiddenIfChecked(this, 'jobid'); setVisibleIfChecked(this, 'jobid_archived'); ">
	Show archived jobs</td>
	</tr>
	</table>

<?
endWindow();
?>
<br>
<?

if(isset($reportgenerator)){
	$reportgenerator->generate();
}

echo buttons();
endForm();
include_once("navbottom.inc.php");
?>
