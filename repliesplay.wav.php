<?

include_once("inc/common.inc.php");
include_once('inc/securityhelper.inc.php');
include_once('inc/content.inc.php');
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
			$size = strlen($data);

			header("HTTP/1.0 200 OK");
			if (isset($_GET['download'])){
				header("Content-disposition: attachment; filename=download_preview.wav");
				header('Content-type: application/x-octet-stream');
			}else
				header('Content-type: ' . $contenttype);

			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
			header("Content-Length: $size");
			header("Connection: close");
			echo $data;
		}
	}
}
?>