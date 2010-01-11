<?php
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");

if (!isset($_GET['CKEditorFuncNum'])) {
	exit();
}

$imgsrc = ''; // This $imgsrc will be set if the file upload is successful.
$errormessage = '';

// User must be able to send email because this is only used for email messages.
$formitemname = 'upload'; // NOTE: CKEditor is hard coded to use 'upload' as the form item's name.
if ($USER->authorize("sendemail") && isset($_FILES['upload'])) {
	$maxfilesize = 2 * 1024 * 1024; // 2 megabytes.
	$allowedext = array('.jpeg', '.jpg', '.png', '.gif', '.bmp');
	
	$result = handleFileUpload($formitemname, $maxfilesize, null, $allowedext, false);

	if (is_array($result)) {
		permitContent($result['contentid']);
		$imgsrc = "viewimage.php?id=" . $result['contentid'];
	} else if (is_string($result)) {
		$errormessage = $result;
	}
}
?>
<script type='text/javascript'>
	// Update CKEditor's image upload dialog.
	window.parent.CKEDITOR.tools.callFunction('<?=$_GET['CKEditorFuncNum']?>', '<?=$imgsrc?>', '<?=addslashes($errormessage)?>');
</script>