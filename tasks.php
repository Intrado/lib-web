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



////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$IMPORTS = DBFindMany("Import", "from import where ownertype != 'user' order by id");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:taskmanager";
$TITLE = "Data Import Manager";

include_once("nav.inc.php");

startWindow('System Imports ' . help('Tasks_SystemTasks', NULL, 'blue'), 'padding: 3px;');
button_bar(button('addnewimport', null, "task.php?id=new"),button('refresh', 'window.location.reload()'));

function fmt_updatemethod ($import,$field) {
	if ($import->$field == "full")
		return "Update, create, delete";
	else if ($import->$field == "update")
		return "Update & create";
	else if ($import->$field == "updateonly")
		return "Update only";
}


function fmt_datamodifiedtime ($import,$field) {
	if ($import->datamodifiedtime != null) {
		return date("M j, g:i a",strtotime($import->datamodifiedtime));
	} else {
		return "Not Found";
	}
}

function fmt_actions ($import,$dummy) {
	$associatedjobcount = QuickQuery("Select count(*) from importjob where importid = '$import->id'");
	$confirm = "Are you sure you want to run this import now?";
	if($associatedjobcount > 0){
		$confirm = "Are you sure you want to run this import and the linked repeating job(s) now?";
		$extra = 'W A R N I N G:\nThis import has ' . "$associatedjobcount" .' repeating job(s) linked to it that will automatically run if you click OK.\n';
		$confirm = $extra. $confirm;
	}
	return "<a href=\"taskupload.php?id=$import->id\">Upload</a>&nbsp;|&nbsp;<a href=\"task.php?run=$import->id\" onclick=\"return confirm('$confirm');\">Run&nbsp;Now</a>&nbsp;|&nbsp;<a href=\"task.php?id=$import->id\">Edit</a>&nbsp;|&nbsp;<a href=\"taskmap.php?id=$import->id\">Map&nbsp;Fields</a>";

	//disabled delete
	//&nbsp;|&nbsp;<a href=\"task.php?delete=$import->id\" onclick=\"return confirmDelete();\">Delete</a>
}


$titles = array("name" => "#Name",
				"description" => "#Description",
				"updatemethod" => "#Type",
				"uploadkey" => "Upload Key",
				"status" => "#Status",
				"lastrun" => "Last Run",
				"datamodifiedtime" => "File Date");

$titles['Actions'] = "Actions";


$formatters = array("updatemethod" => "fmt_updatemethod",
					"status" => "fmt_ucfirst",
					"lastrun" => "fmt_obj_date",
					"updatemethod" => "fmt_updatemethod",
					"datamodifiedtime" => "fmt_datamodifiedtime",
					"Actions" => "fmt_actions");


showObjects($IMPORTS, $titles,$formatters, false,  true);


endWindow();

include_once("navbottom.inc.php");
?>