<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");
require_once("obj/ContentAttachment.obj.php");

if (!isset($_GET['CKEditorFuncNum'])) {
	exit();
}

$results = '';
$errormessage = '';

// User must be able to send email or post to facebook or twitter
$formitemname = 'upload'; // NOTE: CKEditor is hard coded to use 'upload' as the form item's name.
if (((getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) ||
		(getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) ||
		$USER->authorize("sendemail")) && isset($_FILES['upload'])
) {


	//TODO: what else to add?
	$allowedext = array('.pdf', '.jpeg', '.jpg', '.png', '.gif', '.bmp');
	//TODO: what should be the max size?
	$maxfilesize = 50 * 1024 * 1024;
	$content = handleFileUpload($formitemname, $maxfilesize, null, $allowedext, false, null);

	if (is_array($content)) {
		permitContent($content['contentid']);

		Query("BEGIN");

		$contentAttachment = new ContentAttachment();
		$contentAttachment->contentid = $content['contentid'];
		$contentAttachment->filename = $content['filename'];
		$contentAttachment->size = $content['sizebytes'];
		$contentAttachment->create();

		Query("COMMIT");
		permitContent($contentAttachment->id);

		$location = "$BASEURL/emailattachment.php?id=" . $contentAttachment->id;
		$data = array("location" => $location, "id" => $contentAttachment->id, "contentid" => $contentAttachment->contentid, "filename" => $contentAttachment->filename);
		$results = json_encode($data);

	} else if (is_string($content)) {
		$errormessage = $content;
	}
}

?>
<script type='text/javascript'>
	// Update CKEditor's image upload dialog.
	window.parent.CKEDITOR.tools.callFunction('<?=$_GET['CKEditorFuncNum']?>', '<?=$results?>', '<?=addslashes($errormessage)?>');
</script>

