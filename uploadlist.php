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
include_once("obj/Person.obj.php");



////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist') || !($USER->authorize('listuploadids') || $USER->authorize('listuploadcontacts'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$list = new PeopleList(getCurrentList());

$f = "list";
$s = "upload";
$reloadform = 0;

//should we check for an upload? (clicking a submit button is assumed)
if (isset($_FILES['listcontents']) && $_FILES['listcontents']['tmp_name']) {
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else { 
		MergeSectionFormData($f, $s);
		$newname = secure_tmpname("listupload",".csv");
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to use your selections', 'Please verify that all required field information has been entered properly');
			
			//the next check performs several actions that are preconditions of the save, relying on || conditional evaluation
		} else if (!move_uploaded_file($_FILES['listcontents']['tmp_name'],$newname) ||
				!is_file($newname) || 
				!is_readable($newname)) {
			error('Unable to complete file upload. Please try again.');
		} else {
			
			$type = GetFormData($f, $s, "type");
			
			//check for an import record or create one
			$importid = QuickQuery("select id from import where listid='$list->id'");
			if (!$importid) {
				$import = new Import();
				$import->userid = $USER->id;
				$import->listid = $list->id;
				$import->name = "User list import (" . $USER->login . ")";
				$import->description = substr("list (" . $list->name . ")", 0,50);
				$import->status = "idle";
				$import->type = "list";
				$import->datatype = "person";
				$import->scheduleid = NULL;
				$import->ownertype = "user";
				$import->updatemethod = $type == "ids" ? "updateonly" : "full"; //protect ids upload from every accidentally creating people.
				$import->create();
			} else {
				$import = new Import($importid);
				$import->name = "User list import (" . $USER->login . ")";
				$import->description = substr("list (" . $list->name . ")", 0,50);
				$import->updatemethod = $type == "ids" ? "updateonly" : "full"; //protect ids upload from every accidentally creating people.
				$import->update();
			}
			
			//update the import data with new file 
			$data = file_get_contents($newname);
			unlink($newname);
			if ($import->upload($data)) {
				//everything looks good, move on to next step
				if ($type == "contacts")
					redirect("uploadlistmap.php");
				else
					redirect("uploadlistpreview.php");
			} else {
				error('Unable to complete file upload. Please try again.');
				$reloadform = 1;
			}
		}
	}
} else if (CheckFormSubmit($f,'upload')) {
	//clicked upload but no file is present
	error("Please select a file to upload");
} else {
	$reloadform = 1;
}

if ($reloadform) {	
	ClearFormData($f);
	
	$validtypes = array();
	if ($USER->authorize('listuploadcontacts'))
		$validtypes[] = "contacts";
	if ($USER->authorize('listuploadids'))
		$validtypes[] = "ids";
	
	PutFormData($f, $s, "type", $validtypes[0], "array", $validtypes, "nomax", 1);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = "Upload List: " . escapehtml($list-> name);

include_once("nav.inc.php");

NewForm($f);

buttons(submit($f, 'upload','Preview'), button('Cancel',NULL,'list.php'));


startWindow('Upload Call List File');
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Upload Type:</th>
		<td class="bottomBorder">
			<table  border="0" cellpadding="3" cellspacing="0">
<?
	if ($USER->authorize('listuploadcontacts')) {
?>
				<tr>
					<td><label><? NewFormItem($f, $s, "type", "radio", null, "contacts"); ?>Contact&nbsp;data:</label></td>
					<td>
						File format must be a Comma Separated Value (CSV) text file. Once uploaded, the files columns can be mapped to names, destinations, and insertable fields for use in messages.
					</td>
				<tr>
<? } ?>
<? if ($USER->authorize('listuploadids')) { ?>
				<tr>
					<td><label><? NewFormItem($f, $s, "type", "radio", null, "ids"); ?>ID#&nbsp;lookup:</label></td>
					<td>File must be a list of ID#s only (one per line)</td>
				</tr>
<? } ?>
			</table>
		</td>
		<td>

		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader">Upload File:</th>
		<td>
			<input type="file" name="listcontents" size="30">
		</td>
	</tr>

</table>
<?
endWindow();

?><br><div style="margin-left: 10px;"><img src="img/bug_important.gif"> Please select a file to upload and then click Preview to continue.</div><?

buttons();
EndForm();

include_once("navbottom.inc.php");

?>
