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

$audio = new AudioFile('new');

$errormessage = "";
$contentid = "";
$filename = "";
$size = "";

if (!empty($_POST) && empty($_FILES['audio'])) {
	$errormessage = ('Please select an audio file to upload');
} else if (!empty($_FILES['audio']) && isset($_SESSION['messagegroupid']) && userOwns('messagegroup', $_SESSION['messagegroupid'])) {
	$messagegroup = new MessageGroup($_SESSION['messagegroupid']);
	
	//submit changes
	$audio->name = _L('Audio Upload - ') . date("F jS, Y h:i a");
	$audio->userid = $USER->id;
	$audio->deleted = 0;
	$audio->permanent = $messagegroup->permanent;
	$audio->messagegroupid = $messagegroup->id;
	
	if (!$_FILES['audio']['name']) {
		$errormessage = ('There was an error reading your audio file'."\n".'Please try another file');
	} else {
		$filename = $_FILES['audio']['name'];
		$path_parts = pathinfo($filename);
		
		$ext = isset($path_parts['extension'])?$path_parts['extension']:"wav";
		if (strlen($ext) < 1 || !in_array(strtolower($ext),array('wav','aiff','au','aif'))) {
			$ext = "wav";
		}
		$audio->recorddate = date("Y-m-d G:i:s");

		$source = $SETTINGS['feature']['tmp_dir'] . DIRECTORY_SEPARATOR . basename($_FILES['audio']['tmp_name']) . 'orig.' . $ext;
		$dest = $SETTINGS['feature']['tmp_dir'] . DIRECTORY_SEPARATOR . basename($_FILES['audio']['tmp_name']) . '.wav';
		if(!move_uploaded_file($_FILES['audio']['tmp_name'],$source)) {
			$errormessage = ('There was an error reading your audio file'."\n".'Please try another file');
			unlink($source);
			unlink($dest);
		} else {

			$cmd = "sox \"$source\" -r 8000 -c 1 -s -w \"$dest\" ";
			$result = exec($cmd, $res1,$res2);

			if($res2 || !file_exists($dest)) {
				$errormessage = ('There was an error reading your audio file'."\n".'Please try another file'."\n".
				'Supported formats include: .wav, .aiff, and .au');
				unlink($source);
				unlink($dest);
			} else {
				$contentid = contentPut($dest,"audio/wav");

				unlink($source);
				unlink($dest);

				if ($contentid) {
					$audio->contentid = $contentid;
					$audio->update();
				} else {
					$errormessage = ('There was an error uploading your audio file'."\n".'Please try again'."\n".
					'Supported formats include: .wav, .aiff, and .au');
				}
			}
		}
	}
}
?>

<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print">
	<link href="css/form.css.php" type="text/css" rel="stylesheet">
</head>

<body style="margin-left: 0px; margin-top: 1px; margin-bottom: 0px">

<form id="uploadform" style='margin:0;padding:0'action="uploadaudio.php?formname=<?=$_GET['formname']?>&itemname=<?=$_GET['itemname']?>" method="post" enctype="multipart/form-data" onsubmit="" >
	<?=_L('Upload an audio file')?>
	<br/>
	<!-- TODO: Might need maximum size -->
	<input id="audio" name="audio" type="file" onChange="window.top.window.startUpload();this.form.submit();"/>	
</form>
<script language="javascript" type="text/javascript">
	window.top.window.stopAudioUpload('<?=$contentid?>','<?= addslashes($filename) ?>','<?= $size ?>','<?= addslashes($errormessage) ?>', '<?=$_GET['formname']?>', '<?=$_GET['itemname']?>');
</script> 
</body>
</html>
