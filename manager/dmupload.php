<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/formatters.inc.php");

if (!$MANAGERUSER->authorized("editdm"))
	exit("Not Authorized");

if(isset($_GET['dmid'])){
	$_SESSION['dmid'] = $_GET['dmid']+0;
	redirect();
}

if(!isset($_SESSION['dmid'])){
	echo "Please go back and choose a DM";
	exit();
}

list($dmname,$notes) = QuickQueryRow("select name,notes from dm where id=?",false,false,array($_SESSION['dmid']));

$f="dmupload";
$s="main";
$reloadform = 0;


if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(QuickQuery("select command from dm where id = " . $_SESSION['dmid']) != ""){
			error("This DM already has a command queued. Please try again in a few moments");
		} else {
			if(isset($_FILES['dmupload']) && $_FILES['dmupload']['tmp_name'])
			{
				$newname = secure_tmpname("dmupload",".dat");

				if(!move_uploaded_file($_FILES['dmupload']['tmp_name'],$newname)) {
					error('Unable to complete file upload. Please try again.');
				} else {
					if (is_file($newname) && is_readable($newname)) {
						$notes = GetFormData($f, $s, "notes");
						$b64data = base64_encode(file_get_contents($newname));
						
						QuickUpdate("begin");
						QuickUpdate("insert into dmdatfile (dmid, data, notes) values
									(?, ?, ?)", false, array($_SESSION['dmid'], $b64data, $notes));
						QuickUpdate("update dm set command = 'datfile' where id = ?", false, array($_SESSION['dmid']));
						QuickUpdate("commit");
						redirect("customerdms.php");
					} else {
						error('Unable to complete file upload. Please try again.');
					}
				}
			} else {
				error("Please select a file to upload");
			}
		}
	}
} else {
	$reloadform = 1;
}



if($reloadform){
	ClearFormData($f);
	PutFormData($f, $s, "notes", "", "text", "nomin", "nomax", true);
	PutFormData($f, $s, "submit", "");
}

include_once("nav.inc.php");
?>
<div>Upload File for:
<table><tr><td>Name: </td><td><?=$dmname?></td></tr><tr><td>Notes: </td><td><?=$notes?></td></tr></table>
</div>
<?
NewForm($f);
?>
<table>

	<tr>
		<td>Upload Notes:</td>
		<td><? NewFormItem($f, $s, "notes", "textarea", 40, 3) ?></td>
	</tr>
	<tr>
		<td>Input File:</td>
		<td>
			<input type="file" name="dmupload" size="30">
		</td>
	</tr>

</table>


<table>
	<tr>
		<td><? NewFormItem($f, $s, "submit", "submit", "Upload"); ?></td>
		<td><a href="customerdms.php">Cancel</a></td>
	</tr>
</table>
<?
EndForm();
include_once("navbottom.inc.php");
?>