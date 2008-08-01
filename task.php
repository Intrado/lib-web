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
	}
	redirectToReferrer();
}

if (isset($_GET['id'])) {
	setCurrentImport($_GET['id']);
	redirect();
}

$id = $_SESSION['importid'];
$IMPORT = new Import($id);

$query= "select job.id, concat(job.name, ' (', user.login, ')' ) from job, user
				where job.userid = user.id
				and job.status = 'repeating'";
$repeatingjobs = QuickQueryList($query, true);

$associatedjobs = DBFindMany("ImportJob","from importjob where importid = '$IMPORT->id'");
$associatedjobids = array();
foreach($associatedjobs as $importjob){
	//import job id used on both sides because of form.inc.php's multiselect uses in_array()
	$associatedjobids[$importjob->jobid] = $importjob->jobid;
}

$associationdataexists = false;
if (QuickQuery("select count(*) from import where datatype='association'")) $associationdataexists = true;


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
							"' and id != '$IMPORT->id'")) {
				error("Please choose a unique import task name. This one is already in use.");
			} else if(GetFormData($form, $section, 'trigger_checkbox') && !GetFormData($form, $section, 'associatedjobs')){
				error("You must associate at least one job with this import by highlighting the job name; otherwise you must uncheck the Associated Jobs checkbox");
			} else {
				$IMPORT->userid = $USER->id;
				$IMPORT->name = GetFormData($form, $section, 'name');
				$IMPORT->description = GetFormData($form, $section, 'description');

				if (!$IMPORT->id) {
					$IMPORT->datatype = GetFormData($form, $section, 'datatype');
					$IMPORT->uploadkey = md5($CUSTOMERURL . microtime());
					// fill defaults
					$IMPORT->status = "idle";
					$IMPORT->type = "automatic";
					$IMPORT->ownertype = "system";
					$IMPORT->updatemethod = "full";
					$IMPORT->create();

					$_SESSION['importid'] = $IMPORT->id; // Save import ID to the session
					redirect();
				}
				// else editing existing import

				$IMPORT->skipheaderlines = GetFormData($form, $section, 'skipheaderlines');

				$IMPORT->updatemethod = GetFormData($form, $section, 'updatemethod');
				$IMPORT->status = 'idle';
				$IMPORT->ownertype = 'system';

				$IMPORT->type = GetFormData($form, $section, 'automaticimport') ? 'automatic' : 'manual';

				$IMPORT->notes = GetFormData($form, $section, 'notes');
				$IMPORT->update();

				$checked = GetFormData($form, $section, 'trigger_checkbox');
				if ($checked) {
					$associated = GetFormData($form, $section, 'associatedjobs');
				} else {
					$associated = array();
				}
				if(count($associated)==0) {
					$query = "Delete from importjob where importid = '$IMPORT->id'";
					QuickUpdate($query);
				} else {
					$query = "Delete from importjob where importid = '$IMPORT->id'
								and jobid not in (". implode(',', $associated) . " )";
					QuickUpdate($query);
					$existingids = QuickQueryList("Select jobid from importjob where importid = '$IMPORT->id'
													and jobid in (". implode(',', $associated) . " )" );
					$newjobids = array_diff($associated, $existingids);
					foreach($newjobids as $jobid) {
						$newjob = new Job($jobid);
						$schedule = new Schedule($newjob->scheduleid);
						$schedule->nextrun = null;
						$schedule->update();
						$importjob = new ImportJob();
						$importjob->jobid = $jobid;
						$importjob->importid = $IMPORT->id;
						$importjob->create();
					}
				}

				$_SESSION['importid'] = $IMPORT->id; // Save import ID to the session

				redirect("tasks.php");
			}
		}
	}
} else {
	$reloadform = true;
}

if( $reloadform )
{
	ClearFormData($form);
	PutFormData($form, $section, 'datatype', $IMPORT->datatype, 'text');
	PutFormData($form, $section, 'name', $IMPORT->name, 'text', 1, 50, true);
	PutFormData($form, $section, 'description', $IMPORT->description, 'text', 1, 50);
	PutFormData($form, $section, 'updatemethod', ($IMPORT->updatemethod != null ? $IMPORT->updatemethod : 'updateonly'), 'text');

	PutFormData($form, $section, "skipheaderlines", $IMPORT->skipheaderlines, 1,10);

	PutFormData($form, $section, 'automaticimport', ($IMPORT->type == 'automatic'), 'bool', 0, 1);
	PutFormData($form, $section, 'associatedjobs', $associatedjobids, 'array', array_keys($repeatingjobs));
	$checked = false;
	if(count($associatedjobids))
		$checked = true;
	PutFormData($form, $section, 'trigger_checkbox', (bool)$checked,"bool",0,1);
	PutFormData($form, $section, 'notes', $IMPORT->notes, "text");
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:taskmanager";
$TITLE = "Import Editor: " . ($IMPORT->id ? $IMPORT->name : 'New Import');

include_once("nav.inc.php");

NewForm($form);
buttons(submit($form, $section));
startWindow('Import Information ');
?>

<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:<br><? print help('ImportEditor_Settings'); ?></th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0">
				<tr>
					<td>Data:</td>
					<td><?
						if (!$IMPORT->id) {
							NewFormItem($form, $section, 'datatype', 'selectstart');
							NewFormItem($form, $section, 'datatype', 'selectoption', "Person", 'person');
							NewFormItem($form, $section, 'datatype', 'selectoption', "User", 'user');
							if (!$associationdataexists)
								NewFormItem($form, $section, 'datatype', 'selectoption', "Association", 'association');
							NewFormItem($form, $section, 'datatype', 'selectend');
						} else {
							echo $IMPORT->datatype;
						}
						?>
					</td>
				</tr>
				<tr>
					<td>Name:</td>
					<td><? NewFormItem($form, $section,"name","text", 30); ?></td>
				</tr>
				<tr>
					<td>Description:</td>
					<td><? NewFormItem($form, $section,"description","text", 50); ?></td>
				</tr>

				<? if ($IMPORT->id) { ?>
				<tr>
					<td>Notes:</td>
					<td><? NewFormItem($form, $section,"notes","textarea", 60, 3); ?></td>
				</tr>
				<tr>
					<td>Update Method:</td>
					<td><?
							NewFormItem($form, $section, 'updatemethod', 'selectstart');
							if ($IMPORT->datatype == "person") {
								NewFormItem($form, $section, 'updatemethod', 'selectoption', "Update only", 'updateonly');
								NewFormItem($form, $section, 'updatemethod', 'selectoption', "Update & create", 'update');
								NewFormItem($form, $section, 'updatemethod', 'selectoption', "Update, create, delete", 'full');
							} else if ($IMPORT->datatype == "user") {
								NewFormItem($form, $section, 'updatemethod', 'selectoption', "Create only", 'createonly');
								NewFormItem($form, $section, 'updatemethod', 'selectoption', "Update, create, disable", 'full');
							} else if ($IMPORT->datatype == "association") {
								NewFormItem($form, $section, 'updatemethod', 'selectoption', "Update, create, delete", 'full');
							}
							NewFormItem($form, $section, 'updatemethod', 'selectend');
						?>
					</td>
				</tr>
				<tr>
					<td>Skip Header Lines:</td>
					<td><? NewFormItem($form, $section,"skipheaderlines","text", 10); ?></td>
				</tr>
				<? } ?>
			</table>
		</td>
	</tr>

	<? if ($IMPORT->id && $IMPORT->datatype == "person") { ?>

	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">*Automated upload:<br><? print help('ImportEditor_AutomatedUpload'); ?></th>
		<td class="bottomBorder">
			<table border="0" cellspacing="0" cellpadding="2">
				<tr><td><? NewFormItem($form, $section, 'automaticimport', 'checkbox'); ?> Automatically run data import when upload completes. <br>[Uncheck this box when configuring import mapping or changing data fields.]</td></tr>
			</table>
		</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader">*Associated Jobs:<br><? print help('ImportEditor_AssociatedJobs'); ?></th>
		<td >
<?
			if(count($repeatingjobs)==0){
				?>No Repeating Jobs<?
			} else {
?>
			<table>
				<tr>
					<td>
						<?
							NewFormItem($form, $section, "trigger_checkbox", "checkbox", null, null, "id='trigger_checkbox' onclick=\"clearAllIfNotChecked(this,'associated_jobs');\"");
						?>
					</td>
					<td style="vertical-align: top">
						<?
							NewFormItem($form, $section,"associatedjobs", "selectmultiple", "20", $repeatingjobs, "id=associated_jobs onmousedown=\"setChecked('trigger_checkbox');\"");
						?>
					</td>
				</tr>
			</table>
<?
			}
?>
		</td>
	</tr>
	<? } ?>
</table>
<?
endWindow();
?><br><div style="margin-left: 10px;">[*Note: This option does not apply to data files that are manually uploaded using the Browse option.]</div><?
buttons();
EndForm();

include_once("navbottom.inc.php");

?>