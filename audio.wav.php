<?

include_once("inc/common.inc.php");
include_once('inc/securityhelper.inc.php');
include_once('inc/content.inc.php');
include_once('obj/AudioFile.obj.php');
include_once('obj/Content.obj.php');

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if(isset($_GET['id'])) {
	$id = DBSafe($_GET['id']);
	if (userOwns("audiofile",$id)) {
		$af = new AudioFile($id);

		if ($c = contentGet($af->contentid)){
			list($contenttype,$data) = $c;
		}
		if ($data) {
			$wavfile = writeWav($data);
			$outname = secure_tmpname("previewaudio",".mp3");
			$cmd = 'lame -S -V3 "' . $wavfile . '" "' . $outname . '"';
			$result = exec($cmd, $res1, $res2);
			unlink($wavfile);
			
			if (!$res2 && file_exists($outname)) {
				$data = file_get_contents ($outname); //readfile seems to cause problems
				header("HTTP/1.0 200 OK");
				if (isset($_GET['download']))
					header('Content-type: application/x-octet-stream');
				else {
					header("Content-Type: audio/mpeg");
				} 
				header("Content-disposition: attachment; filename=audiopreview.mp3");
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
