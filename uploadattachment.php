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


	$unsafeext = array(".ade", ".adp", ".asx", ".bas", ".bat", ".chm", ".cmd", ".com", ".cpl",
		".crt", ".dbx", ".exe", ".hlp", ".hta", ".inf", ".ins", ".isp", ".js", ".jse", ".lnk",
		".mda", ".mdb", ".mde", ".mdt", ".mdw", ".mdz", ".mht", ".msc", ".msi", ".msp", ".mst",
		".nch", ".ops", ".pcd", ".pif", ".prf", ".reg", ".scf", ".scr", ".sct", ".shb", ".shs",
		".url", ".vb", ".vbe", ".vbs", ".wms", ".wsc", ".wsf", ".wsh", ".zip", ".dmg", ".app");

	$maxfilesize = 50 * 1024 * 1024;
	$content = handleFileUpload($formitemname, $maxfilesize, $unsafeext, null, false, null);

	if (is_array($content)) {
		permitContent($content['contentid']);

		Query("BEGIN");

		$contentAttachment = new ContentAttachment();
		$contentAttachment->contentid = $content['contentid'];
		$contentAttachment->filename = $content['filename'];
		$contentAttachment->size = $content['sizebytes'];
		$contentAttachment->create();
		Query("COMMIT");

		$location = "$BASEURL/emailattachment.php?id=" . $contentAttachment->contentid;
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

