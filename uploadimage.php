<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");

if (!isset($_GET['CKEditorFuncNum'])) {
	exit();
}

$imgsrc = ''; // This $imgsrc will be set if the file upload is successful.
$errormessage = '';

// User must be able to send email or post to facebook or twitter
$formitemname = 'upload'; // NOTE: CKEditor is hard coded to use 'upload' as the form item's name.
if ((		(getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) ||
		(getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) ||
		$USER->authorize("sendemail")) && isset($_FILES['upload'])) {
	$maxfilesize = 1024 * 750; // 750kb.
	$allowedext = array('.jpeg', '.jpg', '.png', '.gif', '.bmp');

	// SMK added scaling flag 2012-12-07 which may be in the FORM fields or on the URI querystring
	$scaleabove = (isset($_REQUEST['scaleabove'])) ? intval($_REQUEST['scaleabove']) : 0;
	$result = handleFileUpload($formitemname, $maxfilesize, null, $allowedext, false, null, $scaleabove);

	if (is_array($result)) {
		permitContent($result['contentid']);
		$imgsrc = "$BASEURL/viewimage.php?id=" . $result['contentid'];
	} else if (is_string($result)) {
		$errormessage = $result;
	}
}
?>
<script type='text/javascript'>
	// Update CKEditor's image upload dialog.
	window.parent.CKEDITOR.tools.callFunction('<?=$_GET['CKEditorFuncNum']?>', '<?=$imgsrc?>', '<?=addslashes($errormessage)?>');
</script>

