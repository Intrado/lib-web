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

	switch ($import->datatype) {
	case "person" :
		//deactivate everyone with this importid
		QuickUpdate("update person set deleted=1, lastimport=now() where importid=$id");
		//TODO this doesnt seem to do anything since it doesn't check the deleted flag???
		QuickUpdate("delete le from listentry le
					left join person p on (p.id = le.personid)
					where p.id is null and le.personid is not null");

		//recalc pdvalues for all fields mapped
		$fieldnums = QuickQueryList("select distinct mapto from importfield where mapto like 'f%' and importid=$id");
		if (count($fieldnums) > 0) {
			$fields = DBFindMany("FieldMap", "from fieldmap where fieldnum in ('" . implode("','",$fieldnums) . "')");
			foreach ($fields as $field)
				$field->updatePersonDataValues();
		}
	break;
	case "user" :
		// disable all users with this importid and set importid to null
		QuickUpdate("update user set enabled=0, lastimport=now(), importid=null where importid=$id");

	break;
	case "association" :
		// TODO how to remove userrules

		// clear out data
		QuickUpdate("delete from personassociation");

		//recalc pdvalues for all fields mapped
		$fieldnums = QuickQueryList("select distinct mapto from importfield where mapto like 'c%' and importid=$id");
		if (count($fieldnums) > 0) {
			$fields = DBFindMany("FieldMap", "from fieldmap where fieldnum in ('" . implode("','",$fieldnums) . "')");
			foreach ($fields as $field)
				$field->updatePersonDataValues();
		}

	break;
	}

	//delete mappings
	QuickUpdate("delete from importfield where importid=$id");

	//delete import
	$import->destroy();
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
button_bar(button('Add New Import', null, "task.php?id=new"),button('Refresh', 'window.location.reload()'));

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
	case "association" :
		$deletewarning = "This will delete all association data!";
	break;
	}
	$associatedjobcount = QuickQuery("Select count(*) from importjob where importid = '$import->id'");
	$confirm = "Are you sure you want to run this import now?";
	if($associatedjobcount > 0){
		$confirm = "Are you sure you want to run this import and the linked repeating job(s) now?";
		$extra = 'W A R N I N G:\nThis import has ' . "$associatedjobcount" .' repeating job(s) linked to it that will automatically run if you click OK.\n';
		$confirm = $extra. $confirm;
	}
	$res = "<a href=\"taskupload.php?id=$import->id\">Upload</a>&nbsp;|&nbsp;";
	if ($import->datamodifiedtime != null) {
		$res .= "<a href=\"taskdownload.php?id=$import->id\">Download</a>&nbsp;|&nbsp;"
			 . "<a href=\"task.php?run=$import->id\" onclick=\"return confirm('$confirm');\">Run&nbsp;Now</a>&nbsp;|&nbsp;";
	}
	$res .= "<a href=\"task.php?id=$import->id\">Edit</a>&nbsp;|&nbsp;"
		 ."<a href=\"tasks.php?delete=" . $import->id . "\" onclick=\"return confirm('Are you sure you want to delete this import item?\\n"
		 . $deletewarning
		 . "');\">Delete</a>";
	return $res;
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