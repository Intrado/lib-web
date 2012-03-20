<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Import.obj.php");
require_once("obj/ImportField.obj.php");
require_once("obj/Schedule.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Person.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	setCurrentImport($_GET['id']);
	redirect();
}

$id = $_SESSION['importid'];
$import = new Import($id);

$f = "task";
$s = "upload";
$reloadform = 0;

//should we check for an upload?
if(isset($_FILES['taskcontents']) && $_FILES['taskcontents']['tmp_name']) {

	$newname = secure_tmpname("taskupload",".csv");
	if(!move_uploaded_file($_FILES['taskcontents']['tmp_name'],$newname)) {
		error('Unable to complete file upload. Please try again');
	} else if (!is_file($newname) || !is_readable($newname)) {
		error('Unable to complete file upload. Please try again');
	} else {
		$data = file_get_contents($newname);
		unlink($newname);
		QuickQuery("BEGIN");
		if ($import->upload($data)) {
			QuickQuery("COMMIT");
			redirect("taskmap.php?id=$import->id");
		} else {
			error_log("Unable to upload import data, either the file was empty or there is a DB problem.");
			error('Unable to complete file upload. Please try again');
		}
	}
} else if (CheckFormSubmit($f,'upload')) {
	error("Please select a file to upload");
}

ClearFormData($f);

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:taskmanager";
$TITLE = "Import Upload: " . escapehtml($import->name);

include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, 'upload','Upload'), button('Cancel',NULL,'tasks.php'));


startWindow('Upload Import File');
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader">Upload File:</th>
		<td>
			<input type="file" name="taskcontents" size="30">
		</td>
	</tr>

</table>
<?
endWindow();

?><br><div style="margin-left: 10px;"><img src="img/bug_important.gif"> Please select a file to upload and then click Upload to continue.<br>NOTE: if uploading a ZIP file, please be sure you only have one CSV file in the zip archive.</div><?

buttons();
EndForm();

include_once("navbottom.inc.php");

?>
