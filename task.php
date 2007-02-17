<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Import.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/Job.obj.php");
require_once("obj/ImportJob.obj.php");
require_once("obj/ScheduleDay.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['run'])) {
	$run = $_GET['run'] + 0;
	if(customerOwns("import",$run)) {
		$import = new Import($run);
		$import->runNow();
		sleep(3);
	}
	redirectToReferrer();
}

if (isset($_GET['id'])) {
	setCurrentImport($_GET['id']);
	redirect();
}

$id = $_SESSION['importid'];
$IMPORT = new Import($id);

$query= "select job.id, job.name from job, user
				where user.customerid = '$USER->customerid'
				and job.userid = user.id
				and job.status = 'repeating'";
$joblist = Query($query);
$repeatingjobs = array();
while($row = DBGetRow($joblist)){
	$repeatingjobs[$row[1]] = $row[0];
}
$associatedjobs = DBFindMany("ImportJob","from importjob where importid = '$IMPORT->id'");
$temparray = array();
foreach($associatedjobs as $jobs){
	$temparray[] = $jobs->jobid;
}
$associatedjobids = $temparray;

	
/****************** main message section ******************/
$form = "taskeditor";
$section = "main";
$reloadform = false;

if(CheckFormSubmit($form, $section))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($form))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = true;
	}
	else
	{
		MergeSectionFormData($form, $section);
		if( CheckFormSection($form, $section) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (CheckFormSubmit($form, $section)) {
			if (QuickQuery("select count(*) from import where name = '" . DBSafe(GetFormData($form, $section, 'name')) .
							"' and customerid = '$USER->customerid' and id != '$IMPORT->id'")) {
				error("Please choose a unique import task name. This one is already in use.");
			} else {
				$IMPORT->userid = $USER->id;
				$IMPORT->customerid = $USER->customerid;
				$IMPORT->name = GetFormData($form, $section, 'name');
				$IMPORT->description = GetFormData($form, $section, 'description');

				if (!$IMPORT->id)
					$IMPORT->uploadkey = substr(md5($CUSTOMERURL . microtime()),5,12);

				$IMPORT->updatemethod = GetFormData($form, $section, 'updatemethod');
				$IMPORT->status = 'idle';
				$IMPORT->ownertype = 'system';

				$IMPORT->type = GetFormData($form, $section, 'automaticimport') ? 'automatic' : 'manual';
				$IMPORT->update();
				
				$associated = GetFormData($form, $section, 'associatedjobs');
				if($associatedjobs){
					foreach($associatedjobs as $alreadyassociated){
						$notused = null;
						if($associated){
							$notused = array_search($alreadyassociated->jobid, $associated);
						}
						if($notused === null || $notused === false) {
							$alreadyassociated->destroy();
						}
					}
				}
				if($associated){
					foreach($associated as $job) {
						$used=false;
						if($associatedjobs){
							foreach($associatedjobs as $alreadyassociated){
								if($job == $alreadyassociated->jobid)
									$used = true;
							}
						}
						if($used != true){
							if(customerOwns("job", $job)) {
								$newjob = new Job($job);
								$scheduledays = DBFindMany("ScheduleDay", "from scheduleday
												 where scheduleid = '$newjob->scheduleid'");
								foreach($scheduledays as $scheduleday)
									$scheduleday->destroy();
								
								Query("Update schedule set nextrun = null where schedule.id = '$newjob->scheduleid'");
								$importjob = new ImportJob();
								$importjob->jobid = $job;
								$importjob->importid = $IMPORT->id;
								$importjob->create();
							}
						}
					}
				}

				$_SESSION['importid'] = $IMPORT->id; // Save import ID to the session
				//redirect("tasks.php");
			}
		}
	}
} else {
	$reloadform = true;
}

if( $reloadform )
{
	ClearFormData($form);
	PutFormData($form, $section, 'name', $IMPORT->name, 'text', 1, 50, true);
	PutFormData($form, $section, 'description', $IMPORT->description, 'text', 1, 50);
	PutFormData($form, $section, 'updatemethod', ($IMPORT->updatemethod != null ? $IMPORT->updatemethod : 'updateonly'), 'text');

	PutFormData($form, $section, 'automaticimport', ($IMPORT->type == 'automatic'), 'bool', 0, 1);
	PutFormData($form, $section, 'associatedjobs', $associatedjobids);
	$checked = false;
	if($associatedjobids)
		$checked = true;
	PutFormData($form, $section, 'trigger_checkbox', (bool)$checked,"bool",0,1);
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:taskmanager";
$TITLE = "Import Editor: " . ($IMPORT != null ? $IMPORT->name : 'New Import');

include_once("nav.inc.php");

NewForm($form);
buttons(submit($form, $section));
startWindow('Import Information ');
?>

<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td>Name:</td>
					<td><? NewFormItem($form, $section,"name","text", 30); ?></td>
				</tr>
				<tr>
					<td>Description:</td>
					<td><? NewFormItem($form, $section,"description","text", 50); ?></td>
				</tr>
				<tr>
					<td>Update Method:</td>
					<td><?
							NewFormItem($form, $section, 'updatemethod', 'selectstart');
							NewFormItem($form, $section, 'updatemethod', 'selectoption', "Update only", 'updateonly');
							NewFormItem($form, $section, 'updatemethod', 'selectoption', "Update & create", 'update');
							NewFormItem($form, $section, 'updatemethod', 'selectoption', "Update, create, delete", 'full');
							NewFormItem($form, $section, 'updatemethod', 'selectend');
						?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Automated upload:</th>
		<td class="bottomBorder">
			<table border="0" cellspacing="0" cellpadding="2">
				<tr><td>Import when uploaded</td><td><? NewFormItem($form, $section, 'automaticimport', 'checkbox'); ?> Import when remote uploads are complete <br>(uncheck this box when configuring import mapping or changing data fields)</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader">Associated Jobs</th>
		<td >
		<table>
			<tr>
				<td>
					<?
					NewFormItem($form, $section, "trigger_checkbox", "checkbox", null, null, "id='trigger_checkbox' onclick=\"clearAllIfNotChecked(this,'associated_jobs');\"");
					?>
				</td>
				<td style="vertical-align: top">
					<?
						$jobnames = array();
						foreach($repeatingjobs as $key => $jobid){
							$jobnames[$key] = $jobid;
						}
						NewFormItem($form, $section,"associatedjobs", "selectmultiple", null, $jobnames, "id=associated_jobs onmousedown=\"setChecked('trigger_checkbox');\"");
					?>
				</td>
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