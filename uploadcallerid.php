<?
include_once("inc/common.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
include_once("obj/DMCallerIDRoute.obj.php");

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

$f="uploadcalleridroutes";
$s="main";

$reloadform = 0;

//should we check for an upload?
if(isset($_FILES['calleridrouteupload']) && $_FILES['calleridrouteupload']['tmp_name'])
{
	$newname = secure_tmpname("calleridrouteupload",".csv");

	if(!move_uploaded_file($_FILES['calleridrouteupload']['tmp_name'],$newname)) {
		error('Unable to complete file upload. Please try again.');
	} else {
		if (is_file($newname) && is_readable($newname)) {
			$_SESSION['calleridrouteuploadfiles'][$dmid] =  $newname;
			if(CheckFormInvalid($f))
			{
				error('Form was edited in another window, reloading data');
			}
			else
			{
				redirect("uploadcalleridpreview.php");
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
$TITLE="Upload Caller ID Routes: " . escapehtml($dmname);

NewForm($f);
include_once("nav.inc.php");

buttons(submit($f, "upload", "Preview"), icon_button(_L("Cancel"), "cross", null, "calleridroute.php"));
startWindow("Upload Caller ID Routes");
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader">Upload File:</th>
		<td>
			<div>File format must be a csv file with format:
				<br>CallerID, Prefix
			</div>
			<br>
			<input type="file" name="calleridrouteupload" size="30">
		</td>
	</tr>
	</table>
<?
endWindow();
buttons();
include_once("navbottom.inc.php");
EndForm();
?>