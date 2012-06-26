<?
include_once("inc/common.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
include_once("obj/DMRoute.obj.php");

if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}
if(isset($_GET['dmid'])){
	$_SESSION['dmid'] = $_GET['dmid'] +0;
	redirect();
} else {
	$dmid = $_SESSION['dmid'];
}
$dmname = QuickQuery("select name from custdm where dmid = " . $dmid);

$f="uploadroutes";
$s="main";

$reloadform = 0;

//should we check for an upload?
if(isset($_FILES['routeupload']) && $_FILES['routeupload']['tmp_name'])
{
	$newname = secure_tmpname("routeupload",".csv");

	if(!move_uploaded_file($_FILES['routeupload']['tmp_name'],$newname)) {
		error('Unable to complete file upload. Please try again.');
	} else {
		if (is_file($newname) && is_readable($newname)) {
			$_SESSION['routeuploadfiles'][$dmid] =  $newname;
			if(CheckFormInvalid($f))
			{
				error('Form was edited in another window, reloading data');
			}
			else
			{
				redirect("uploadroutespreview.php");
			}
		} else {
			error('Unable to complete file upload. Please try again.');
		}
	}
} else if (CheckFormSubmit($f,'upload')) {
	error("Please select a file to upload");
}

ClearFormData($f);


$PAGE="admin:settings";
$TITLE="Upload Telco Settings: " . escapehtml($dmname);

NewForm($f);
include_once("nav.inc.php");

buttons(submit($f, "upload", "Preview"), icon_button(_L("Cancel"), "cross", null, "dmsettings.php"));
startWindow("Upload Routes");
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader">Upload File:</th>
		<td>
			<div>File format must be a Comma Separated Value (CSV) text file with the following field order:
				<br>Match, Strip, Prefix, Suffix
			</div>
			<br>
			<input type="file" name="routeupload" size="30">
		</td>
	</tr>
	</table>
<?
endWindow();
buttons();
include_once("navbottom.inc.php");
EndForm();
?>