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

if(isset($_GET["name"]) && isset($_GET["id"])) {
	exit();
}

$audio = new AudioFile();

$errormessage = "";
$audioid = "";
$audioname = "";

if (!empty($_POST) && empty($_FILES['audio'])) {
	$errormessage = ('Please select an audio file to upload');
} else if (!empty($_FILES['audio']) && isset($_SESSION['messagegroupid']) && userOwns('messagegroup', $_SESSION['messagegroupid'])) {
	$messagegroup = new MessageGroup($_SESSION['messagegroupid']);
	
	//submit changes
	$audio->userid = $USER->id;
	$audio->deleted = 0;
	$audio->permanent = $messagegroup->permanent;
	$audio->messagegroupid = $messagegroup->id;
	
	if (!$_FILES['audio']['name']) {
		$errormessage = _L("There was an error reading your audio file.\nPlease try another file");
	} else {
		$filename = $_FILES['audio']['name'];
		$path_parts = pathinfo($filename);

		// get file extension and verify it's one we support
		$ext = isset($path_parts['extension']) ? $path_parts['extension'] : "";
		if (strlen($ext) < 1 || !in_array(strtolower($ext),array('wav','aiff','au','aif','mp3'))) {
			// if uncertain, let's try the mime type
			if (isset($_FILES['audio']['type']) && $_FILES['audio']['type'] == 'audio/mpeg') {
				$ext = "mp3";
			} else {
				$ext = "wav"; // default
			}
		}
		$audio->recorddate = date("Y-m-d G:i:s");

		$source = $SETTINGS['feature']['tmp_dir'] . DIRECTORY_SEPARATOR . basename($_FILES['audio']['tmp_name']) . 'orig.' . $ext;
		$dest = $SETTINGS['feature']['tmp_dir'] . DIRECTORY_SEPARATOR . basename($_FILES['audio']['tmp_name']) . '.wav';
		if(!move_uploaded_file($_FILES['audio']['tmp_name'],$source)) {
			$errormessage = _L("There was an error reading your audio file.\nPlease try another file");
			unlink($source);
			unlink($dest);
		} else {
			$cmd = "sox \"$source\" -r 8000 -c 1 -s -w \"$dest\" ";
			$result = exec($cmd, $res1,$res2);

			if($res2 || !file_exists($dest)) {
				$errormessage = _L("There was an error reading your audio file.\nPlease try another file.\nSupported formats include: .wav, .aiff, .au, and .mp3");
				unlink($source);
				@unlink($dest);
			} else {
				$contentid = contentPut($dest,"audio/wav");

				unlink($source);
				unlink($dest);

				if ($contentid) {
					$audiofileids = MessageGroup::getReferencedAudioFileIDs($messagegroup->id);
					$duplicatenames = count($audiofileids) > 0 ? QuickQueryList('select name from audiofile where not deleted and id in (' . implode(',', $audiofileids) . ') and name like ?', false, false, array($filename . '%')) : array();

					// If there are any duplicate names, then find the largest sequence number so that we can set our final name to "$filename " . ($largestsequencenumber + 1)
					if (count($duplicatenames) > 0) {
						$largestsequencenumber = 1;

						foreach ($duplicatenames as $duplicatename) {
							if (preg_match('/ \d+$/', $duplicatename, $matches)) {
								$sequencenumber = intval($matches[0]);

								if ($sequencenumber > $largestsequencenumber) {
									$largestsequencenumber = $sequencenumber;
								}
							}
						}

						$finalaudiofilename = "$filename " . ($largestsequencenumber + 1);
					} else {
						$finalaudiofilename = $filename;
					}
					$audioname = $audio->name = $finalaudiofilename;
					$audio->contentid = $contentid;
					$audio->update();
					$audioid = $audio->id;
				} else {
					$errormessage = _L("There was an error uploading your audio file.\nPlease try again.\nSupported formats include: .wav, .aiff, and .au");
				}
			}
		}
	}
}
?>

<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
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

<body style="margin-left: 0px;  margin-bottom: 0px">

<form id="uploadform" style='margin:0;padding:0'action="uploadaudio.php?formname=<?=$_GET['formname']?>&itemname=<?=$_GET['itemname']?>" method="post" enctype="multipart/form-data" onsubmit="" >
	<!-- TODO: Might need maximum size -->
	<input id="audio" name="audio" type="file" onChange="window.parent.window.startAudioUpload('<?=$_GET['itemname']?>');this.form.submit();"/>	
</form>
<script language="javascript" type="text/javascript">
	window.parent.window.stopAudioUpload('<?=$audioid?>','<?= addslashes($audioname) ?>',<?=json_encode($errormessage)?>, '<?=$_GET['formname']?>', '<?=$_GET['itemname']?>');
</script> 
</body>
</html>
