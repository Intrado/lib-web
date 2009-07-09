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

if (isset($_SESSION['listuploadfiles'][$list->id])) {
	@unlink($_SESSION['listuploadfiles'][$list->id]);
	unset($_SESSION['listuploadfiles'][$list->id]);
}

$maxphones = GetSystemSetting("maxphones", 3);
$maxemails = GetSystemSetting("maxemails", 2);

$f = "list";
$s = "upload";
$reloadform = 0;

//should we check for an upload?
if(isset($_FILES['listcontents']) && $_FILES['listcontents']['tmp_name'])
{
	$newname = secure_tmpname("listupload",".csv");

	if(!move_uploaded_file($_FILES['listcontents']['tmp_name'],$newname)) {
		error('Unable to complete file upload. Please try again.');
	} else {
		if (is_file($newname) && is_readable($newname)) {
			$_SESSION['listuploadfiles'][$list->id] =  $newname;
			$_SESSION['listuploadfiles']['type'] = $_POST['type'];
			if(CheckFormInvalid($f))
			{
				error('Form was edited in another window, reloading data');
			}
			else
			{
				MergeSectionFormData($f, $s);

				//do check
				if( CheckFormSection($f, $s) ) {
					error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
				} else {
					$_SESSION['listupload']['phonefield'] = DBSafe(GetFormData($f, $s, "phonefield"));
					$_SESSION['listupload']['emailfield'] = DBSafe(GetFormData($f, $s, "emailfield"));
					redirect("uploadlistpreview.php");
				}
			}
		} else {
			error('Unable to complete file upload. Please try again.');
		}
	}
} else if (CheckFormSubmit($f,'upload')) {
	error("Please select a file to upload");
}

ClearFormData($f);
PutFormData($f, $s, "phonefield", "0", "number");
PutFormData($f, $s, "emailfield", "0", "number");

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
	$ischecked = false;
	if ($USER->authorize('listuploadcontacts')) {
		$ischecked = true;
?>
				<tr>
					<td>Contact&nbsp;data:</td>
					<td><input type="radio" name="type" size="30" value="contacts" checked onclick="if(this.checked){ $('mapping').show();}" ></td>
					<td width="100%">
						<table>
							<tr>
								<td>File format must be a Comma Separated Value (CSV) text file with the following field order:<br><code>First Name, Last Name, Phone w/ Area Code, Email Address (optional)</code></td>
							</tr>
							<tr>
								<td>
									<table id="mapping">
										<tr>
											<td>Phone Mapping:</td>
											<td>
<?
												NewFormItem($f, $s, "phonefield", "selectstart");
												for($i=0; $i<$maxphones; $i++){
													NewFormItem($f, $s, "phonefield", "selectoption", destination_label("phone", $i), $i);
												}
												NewFormItem($f, $s, "phonefield", "selectend");
?>
											</td>
										</tr>
<?
if($USER->authorize("sendemail")){
?>
										<tr>
											<td>Email Mapping:</td>
											<td>
<?
												NewFormItem($f, $s, "emailfield", "selectstart");
												for($i=0; $i<$maxemails; $i++){
													NewFormItem($f, $s, "emailfield", "selectoption", destination_label("email", $i), $i);
												}
												NewFormItem($f, $s, "emailfield", "selectend");
?>
											</td>
										</tr>
<?
}
?>
									</table>
								</td></td>
							</tr>
						</table>
					</td>
				<tr>
<? } ?>
<? if ($USER->authorize('listuploadids')) { ?>
				<tr>
					<td>ID#&nbsp;lookup:</td>
					<td><input type="radio" name="type" size="30" value="ids" <?= $ischecked ? "" : "checked" ?> onclick="if(this.checked && $('mapping')){ $('mapping').hide(); }" ></td>
					<td width="100%">File must be a list of ID#s only (one per line)</td>
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