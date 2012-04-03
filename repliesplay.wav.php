<?

include_once("inc/common.inc.php");
include_once('inc/securityhelper.inc.php');
include_once('inc/content.inc.php');
include_once("inc/appserver.inc.php");
include_once('obj/VoiceReply.obj.php');
include_once('obj/Content.obj.php');

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if(isset($_GET['id'])) {
	$id = DBSafe($_GET['id']);

	if (userOwns("voicereply",$id)) {

		$vr = new VoiceReply($id);

		if ($c = contentGet($vr->contentid))
			list($contenttype,$data) = $c;

		if ($data) {
			$wavfile = writeWav($data);
			$outname = secure_tmpname("previewaudio",".mp3");
			$cmd = 'lame -S -b24 "' . $wavfile . '" "' . $outname . '"';
			$result = exec($cmd, $res1, $res2);
			unlink($wavfile);
			
			if (!$res2 && file_exists($outname)) {
				$data = file_get_contents ($outname); //readfile seems to cause problems
				header("HTTP/1.0 200 OK");
				header("Content-Type: audio/mpeg");
				if (isset($_GET['download']))
					header("Content-disposition: attachment; filename=messagereply.mp3");
				header('Pragma: private');
				header('Cache-control: private, must-revalidate');
				header("Content-Length: " . strlen($data));
				header("Connection: close");
				echo $data;
			} else {
				echo _L("An error occurred trying to generate the audio file. Please try again.");
			}
			unlink($outname);
		}
	}
}
?>
