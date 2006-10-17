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
require_once("obj/ImportField.obj.php");
require_once("obj/Schedule.obj.php");
include_once("obj/PeopleList.obj.php");
require_once("inc/ftpfile.inc.php");




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
if(isset($_FILES['taskcontents']) && $_FILES['taskcontents']['tmp_name'])
{
	$newname = tempnam("tmp","taskupload");
	if(!move_uploaded_file($_FILES['taskcontents']['tmp_name'],$newname)) {
		error('Unable to complete file upload. Please try again.');
	} else {
		if (is_file($newname) && is_readable($newname)) {
			if ($IS_COMMSUITE) {
				$import->path = $newname;
				$import->update();
				redirect("tasks.php");
			} else {
				if (uploadImportFile($newname,$import->customerid,$import->id))
					redirect("taskmap.php?id=$import->id");
				else
					error('Unable to complete file upload. Please try again.');
			}
		} else {
			error('Unable to complete file upload. Please try again.');
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
$TITLE = "Import Upload: " . $import->name;

include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, 'upload','upload','upload'), button('cancel',NULL,'tasks.php'));


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

?><br><div style="margin-left: 10px;"><img src="img/bug_important.gif"> Please select a file to upload and then click Upload to continue.</div><?

buttons();
EndForm();

include_once("navbottom.inc.php");

?>