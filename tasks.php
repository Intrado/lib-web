<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/Schedule.obj.php");
require_once("obj/Import.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
include_once("obj/FieldMap.obj.php");



////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$id = $_GET['delete'] + 0;
	$import = new Import($id);

	Query("BEGIN");
	// NOTE delete an import logic should be identical to running the same import without any data in it
	// only fullsync imports should delete data
	
	switch ($import->datatype) {
		// For user imports, the desired functionality is that the user accounts remain in the system
		// additionaly, for FULL type imports, we want the user account to be moved to disabled
		case "user" :
			// When removing a "Full-sync" user import, we should disable the linked user accounts
			if ($import->updatemethod == "full")
				$import->disableUsers();
			
			$import->unlinkUserAssociations();
			$import->removeRoles();
			$import->unlinkUsers();
			break;

		case "person" :
			// NOTE: "create only" and "create update" person imports don't delete any data when run with an empty file
			// Only remove data when deleting a "update, create, delete" import
			if ($import->updatemethod == "full") {
				$import->removePersonGuardians();
				$import->removePersonAssociations();
				$import->removeGroupData();
				
				$import->softDeletePeople();
				$import->recalculatePersonDataValues();
			}
			$import->unlinkPeople();
			break;
		
		case "section" :
			// delete all userassociation with this importid, DO NOT remove sectionid=0 do not inadvertently grant access to persons they should not see.
			$import->removeUserAssociations();
			$import->removePersonAssociations();
			$import->removeSections();
			break;
		
		case "enrollment" :
			$import->removePersonAssociations();
			break;
	}
	// NOTE do not hard delete import related data - feature request to someday soft delete imports CS-4473
	
	// import alert rules will be deleted when checked. 
	
	//delete import
	QuickUpdate("delete from importfield where importid = ?", false, array($import->id));
	QuickUpdate("delete from importjob where importid = ?", false, array($import->id));
	QuickUpdate("delete from importlogentry where importid = ?", false, array($import->id));
	QuickUpdate("delete from importmicroupdate where importid = ?", false, array($import->id));
	$import->destroy();
	
	Query("COMMIT");

	notice(_L("The import, %s, is now deleted.", escapehtml($import->name)));
	redirect();
}


$IMPORTS = DBFindMany("Import", "from import where ownertype != 'user' order by id");



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:taskmanager";
$TITLE = "Data Import Manager";

include_once("nav.inc.php");

startWindow('System Imports ' . help('Tasks_SystemTasks'), 'padding: 3px;');
?>
	<div class="feed_btn_wrap cf"><?= icon_button(_L('Add New Import'),"add",null,"task.php?id=new"). icon_button(_L('Refresh'),"arrow_refresh","window.location.reload()") ?></div>
<?


function fmt_datatype ($import,$field) {
	return ucfirst($import->$field);
}

function fmt_updatemethod ($import,$field) {
	if ($import->$field == "full") {
		if ($import->datatype == "person") {
			return "Update, create, delete";
		} else {
			return "Full Sync";
		}
	}
	else if ($import->$field == "update")
		return "Update & create";
	else if ($import->$field == "updateonly")
		return "Update only";
	else if ($import->$field == "createonly")
		return "Create only";
}


function fmt_datamodifiedtime ($import,$field) {
	if ($import->datamodifiedtime != null) {
		return date("M j, Y g:i a",strtotime($import->datamodifiedtime));
	} else {
		return "Not Found";
	}
}

function fmt_actions ($import,$dummy) {
	$deletewarning = "";
	switch ($import->datatype) {
	case "person" :
		$deletewarning = "This will deactivate all associated contact records!";
	break;
	case "user" :
		$deletewarning = "This will disable all associated users!";
	break;
	case "enrollment" :
		$deletewarning = "This will delete all enrollment data!";
	break;
	}

	$associatedjobcount = QuickQuery("Select count(*) from importjob where importid = '$import->id'");
	$confirm = "Are you sure you want to run this import now?";
	if($associatedjobcount > 0){
		$confirm = _L("Are you sure you want to run this import and the linked repeating %s now?", getJobTitle());
		$extra = _L('W A R N I N G:\nThis import has %s repeating %s linked to it that will automatically run if you click OK.\n',$associatedjobcount, getJobsTitle());
		$confirm = $extra. $confirm;
	}

	$links = array();

	$links[] = action_link(_L("Upload"), "folder", "taskupload.php?id=$import->id");

	if ($import->datamodifiedtime != null) {
		$links[] = action_link(_L("Download"), "disk", "taskdownload.php?id=$import->id");
		$links[] = action_link(_L("Run Now"), "database_go", "task.php?run=$import->id","return confirm('$confirm');");
	}

	if ($import->lastrun != null) {
		$links[] = action_link(_L("Log"), "application_view_list", "tasklog.php?id=$import->id");
	}

	$links[] = action_link(_L("Edit"), "pencil", "task.php?id=$import->id");
	$links[] = action_link(_L("Delete"), "cross", "tasks.php?delete=$import->id", "return confirm('Are you sure you want to delete this import item?\\n$deletewarning');");

	return action_links($links);
}


$titles = array("name" => "#Name",
				"description" => "#Description",
				"datatype" => "#Type",
				"updatemethod" => "#Method",
				"status" => "#Status",
				"lastrun" => "Last Run",
				"datamodifiedtime" => "File Date");

$titles['Actions'] = "Actions";


$formatters = array("datatype" => "fmt_datatype",
					"status" => "fmt_ucfirst",
					"lastrun" => "fmt_obj_date",
					"updatemethod" => "fmt_updatemethod",
					"datamodifiedtime" => "fmt_datamodifiedtime",
					"Actions" => "fmt_actions");


showObjects($IMPORTS, $titles,$formatters, false,  true);


endWindow();

include_once("navbottom.inc.php");
?>