<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Schedule.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Import.obj.php");
require_once("obj/Schedule.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

/*
//disable delete
if (isset($_GET['delete'])) {
	$delete = DBSafe($_GET['delete']);
	if(customerOwns("import",$delete)) {
		$deleteimport = DBFind("Import", "from import where id = '$delete' and customerid = '$USER->customerid'");
		$schedule = DBFind("Schedule", "from schedule where id = '$deleteimport->scheduleid'");

		if ($schedule) {
			QuickUpdate("delete from scheduleday where scheduleid=$schedule->id");
			$schedule->destroy();
		}
		QuickUpdate("delete from importfield where importid=$deleteimport->id");
		$deleteimport->destroy();
	}

	redirectToReferrer();
}
*/
if (isset($_GET['run'])) {
	$run = DBSafe($_GET['run']);
	if(customerOwns("import",$run)) {
		if (isset($_SERVER['WINDIR'])) {
			$cmd = "start php import.php -import=$run";
			pclose(popen($cmd,"r"));
		} else {
			$cmd = "php import.php -import=$run > /dev/null &";
			exec($cmd);
		}

	}
	redirectToReferrer();
}

if (isset($_GET['id'])) {
	setCurrentImport($_GET['id']);
	redirect();
}

$id = $_SESSION['importid'];
$IMPORT = new Import($id);


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
			if (QuickQuery("select * from import where name = '" . DBSafe(GetFormData($form, $section, 'name')) .
							"' and customerid = '$USER->customerid' and id != '$IMPORT->id'")) {
				error("Please choose a unique import task name. This one is already in use.");
			} else {
				$IMPORT->userid = $USER->id;
				$IMPORT->customerid = $USER->customerid;
				$IMPORT->name = GetFormData($form, $section, 'name');
				$IMPORT->description = GetFormData($form, $section, 'description');

				if (!$IS_COMMSUITE && $IMPORT->id == null)
					$IMPORT->uploadkey = substr(md5($CUSTOMERURL . microtime()),5,12);

				$IMPORT->updatemethod = GetFormData($form, $section, 'updatemethod');
				$IMPORT->status = 'idle';
				$IMPORT->ownertype = 'system';


				if ($IS_COMMSUITE) {
					$IMPORT->path = GetFormData($form, $section, 'path');

					if (GetFormData($form, $section, 'isscheduled')) {
						$IMPORT->type = 'automatic';
					} else {
						$IMPORT->type = 'manual';
					}

					$schedule = new Schedule($IMPORT->scheduleid);
					$schedule->userid = $USER->id;
					$schedule->triggertype = 'import';
					$schedule->type = 'R';
					if (GetFormData($form, $section,"scheduletime")) {
						$time = GetFormData($form, $section,"scheduletime");
					} else {
						$time = '12:00 am';	// Default if none was chosen in the UI
					}
					$schedule->time = date("H:i", strtotime($time));
					$schedule->update(); // Make sure the schedule object has a valid ID
					$IMPORT->scheduleid = $schedule->id;

					$IMPORT->update();



					// If the task is scheduled, set up the schedule data
					if (GetFormData($form, $section, 'isscheduled')) {
						$data = QuickQueryList("select dow from scheduleday where scheduleid=$schedule->id");
						for ($x = 1; $x < 8; $x++) {
							if(GetFormData($form, $section,"dow$x")) {
								if (!in_array($x,$data)) {
									QuickUpdate("insert into scheduleday (scheduleid, dow) values ($schedule->id,$x)");
								}
							} else {
								if (in_array($x,$data)) {
									QuickUpdate("delete from scheduleday where scheduleid=$schedule->id and dow=$x");
								}
							}
						}
					} else { // Delete the schedule data
						QuickUpdate("delete from scheduleday where scheduleid=$schedule->id");
						$schedule->type = 'O'; // Letter O as in "Oh", short for "Once" - If the task IS NOT scheduled set the schedule type to ONCE
					}

					$schedule->nextrun = $schedule->calcNextRun();
					$schedule->update();


				} else {
					$IMPORT->type = GetFormData($form, $section, 'automaticimport') ? 'automatic' : 'manual';
					$IMPORT->update();
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
	PutFormData($form, $section, 'name', $IMPORT->name, 'text', 1, 50, true);
	PutFormData($form, $section, 'description', $IMPORT->description, 'text', 1, 50);
	PutFormData($form, $section, 'updatemethod', ($IMPORT->updatemethod != null ? $IMPORT->updatemethod : 'updateonly'), 'text');


	if ($IS_COMMSUITE) {
		PutFormData($form, $section, 'path', $IMPORT->path, 'text', 1, 1000, true);
		PutFormData($form, $section, 'isscheduled', ($IMPORT->type == 'automatic'), 'bool', 0, 1);


		$schedule = new Schedule($IMPORT->scheduleid);
		$scheduledows = array();
		$data = QuickQueryList("select dow from scheduleday where scheduleid=$schedule->id");
		for ($x = 1; $x < 8; $x++) {
			$scheduledows[$x] = in_array($x,$data);
		}
		for ($x = 1; $x < 8; $x++) {
			PutFormData($form, $section, "dow$x",$scheduledows[$x],"bool",0,1);
		}
		if ($schedule->time != null) {
			PutFormData($form, $section, "scheduletime",date("g:i a", strtotime($schedule->time)),"text",1,50,true);
		} else {
			PutFormData($form, $section, "scheduletime",date("g:i a", strtotime('12:00 am')),"text",1,50,true);
		}
	} else {
		PutFormData($form, $section, 'automaticimport', ($IMPORT->type == 'automatic'), 'bool', 0, 1);
	}
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
<? if ($IS_COMMSUITE) { ?>
				<tr>
					<td>File:</td>
					<td><? NewFormItem($form, $section,"path","text", 100); ?></td>
				</tr>
<? } ?>
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
<? if ($IS_COMMSUITE) { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Schedule:</th>
		<td class="bottomBorder">
			<table border="0" cellspacing="0" cellpadding="2">
				<tr><td colspan="9"><? NewFormItem($form, $section, 'isscheduled', 'checkbox', null, null, 'onclick="toggleDayState(this.checked)"'); ?></td><td>Scheduled &nbsp; </td>
					<td colspan="9">Repeat this task every:</td>
					<td>
						<table border="0" cellpadding="2" cellspacing="1" class="list" width=100%>
							<tr class="listHeader" align="left" valign="bottom"><td>Su</td>
								<th>M</th>
								<th>Tu</th>
								<th>W</th>
								<th>Th</th>
								<th>F</th>
								<th>Sa</th>
								<th>Time</th>
							</tr>
							<tr>
								<? if ($IMPORT->type != 'automatic') {
										$isDisabled = 'DISABLED';
									} ?>
								<td><? NewFormItem($form, $section,"dow1","checkbox", null, null, 'id="dow1" ' . $isDisabled); ?></td>
								<td><? NewFormItem($form, $section,"dow2","checkbox", null, null, 'id="dow2" ' . $isDisabled); ?></td>
								<td><? NewFormItem($form, $section,"dow3","checkbox", null, null, 'id="dow3" ' . $isDisabled); ?></td>
								<td><? NewFormItem($form, $section,"dow4","checkbox", null, null, 'id="dow4" ' . $isDisabled); ?></td>
								<td><? NewFormItem($form, $section,"dow5","checkbox", null, null, 'id="dow5" ' . $isDisabled); ?></td>
								<td><? NewFormItem($form, $section,"dow6","checkbox", null, null, 'id="dow6" ' . $isDisabled); ?></td>
								<td><? NewFormItem($form, $section,"dow7","checkbox", null, null, 'id="dow7" ' . $isDisabled); ?></td>
								<td><? time_select($form, $section,"scheduletime", null, null, null, null, $isDisabled); ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
<? } else { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader">Automated upload:</th>
		<td class="">
			<table border="0" cellspacing="0" cellpadding="2">
				<tr><td>Import when uploaded</td><td><? NewFormItem($form, $section, 'automaticimport', 'checkbox', null, null, 'onclick="toggleDayState(this.checked)"'); ?> Import when remote uploads are complete <br>(uncheck this box when configuring import mapping or changing data fields)</td>
				</tr>
			</table>
		</td>
	</tr>
<? } ?>
</table>
<?
endWindow();
buttons();
EndForm();
?>

<script language="javascript">
function toggleDayState(isChecked) {
	if (isChecked) { // Enable
		for (i = 1; i < 8; i++) {
			var box = new getObj('dow' + i);
			box.obj.disabled = false;
		}

		var time = new getObj('scheduletime');
		time.obj.disabled = false;
	} else { // Disable
		for (i = 1; i < 8; i++) {
			var box = new getObj('dow' + i);
			box.obj.checked = false;
			box.obj.disabled = true;
		}

		var time = new getObj('scheduletime');
		time.obj.disabled = true;
	}
}
</script>
<?
include_once("navbottom.inc.php");

?>