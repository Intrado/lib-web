<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");
include_once("inc/content.inc.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/MessageGroup.obj.php");
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");
include_once("obj/AudioConverter.obj.php");

if(isset($_GET["name"]) && isset($_GET["id"])) {
	exit();
}

global $USER;

$audio = new AudioFile();
$errorMessage = "";
$audioId = "";
$audioName = "";

if (!empty($_POST) && empty($_FILES['audio'])) {
	$errorMessage = ('Please select an audio file to upload');
} else if (!empty($_FILES['audio']) && isset($_SESSION['messagegroupid']) && userOwns('messagegroup', $_SESSION['messagegroupid'])) {
	$failedConversion = false;
	$converter = new AudioConverter();
	$convertedFile = false;
	try {
		$convertedFile = $converter->getMono8kPcm($_FILES['audio']['tmp_name'], $_FILES['audio']['type']);
		$audio->contentid = contentPut($convertedFile, 'audio/wav');
	} catch (Exception $e) {
		$failedConversion = true;
		error_log($e->getMessage());
	}
	@unlink($convertedFile);

	if ($failedConversion || !$audio->contentid) {
		$errorMessage = _L('There was an error reading your audio file. Please try another file. Supported formats include: %s',
			implode(', ', $converter->getSupportedFormats()));
	} else {
		$messagegroup = new MessageGroup($_SESSION['messagegroupid']);

		//attempt to submit changes
		$audio->userid = $USER->id;
		$audio->deleted = 0;
		$audio->permanent = $messagegroup->permanent;
		$audio->messagegroupid = $messagegroup->id;
		$audio->recorddate = date('Y-m-d G:i:s');

		$filename = $_FILES['audio']['name'];
		$audioFileIds = MessageGroup::getReferencedAudioFileIDs($messagegroup->id);
		$duplicateNames = count($audioFileIds) > 0 ? QuickQueryList('select name from audiofile where not deleted and id in (' . implode(',', $audioFileIds) . ') and name like ?', false, false, array($filename . '%')) : array();

		// If there are any duplicate names, then find the largest sequence number so that we can set our final name to "$filename " . ($largestSequenceNumber + 1)
		if (count($duplicateNames) > 0) {
			$largestSequenceNumber = 1;

			foreach ($duplicateNames as $duplicateName) {
				if (preg_match('/ \d+$/', $duplicateName, $matches)) {
					$sequenceNumber = intval($matches[0]);

					if ($sequenceNumber > $largestSequenceNumber) {
						$largestSequenceNumber = $sequenceNumber;
					}
				}
			}
			$finalAudioFilename = "$filename " . ($largestSequenceNumber + 1);
		} else {
			$finalAudioFilename = $filename;
		}
		$audioName = $audio->name = $finalAudioFilename;
		$audio->update();
		$audioId = $audio->id;
	}
}
?>

<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<link href="css.php" type="text/css" rel="stylesheet" media="screen, print">
	<link href="css.forms.php" type="text/css" rel="stylesheet" media="screen, print" />
	<style type="text/css">
		html, body, iframe {
			background: transparent;
			overflow: hidden;
			border: none;
		}
	</style>
</head>

<body style="margin-left: 0;  margin-bottom: 0">

<form id="uploadform" style='margin:0;padding:0' action="uploadaudio.php?formname=<?=$_GET['formname']?>&itemname=<?=$_GET['itemname']?>" method="post" enctype="multipart/form-data" onsubmit="" >
	<!-- TODO: Might need maximum size -->
	<input id="audio" name="audio" type="file" onChange="window.parent.window.startAudioUpload('<?=$_GET['itemname']?>');this.form.submit();"/>	
</form>
<script language="javascript" type="text/javascript">
	window.parent.window.stopAudioUpload('<?=$audioId?>','<?= addslashes($audioName) ?>',<?=json_encode($errorMessage)?>, '<?=$_GET['formname']?>', '<?=$_GET['itemname']?>');
</script> 
</body>
</html>
