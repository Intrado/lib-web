<?
include_once("inc/common.inc.php");

include_once('inc/securityhelper.inc.php');
include_once('inc/content.inc.php');
include_once("obj/Content.obj.php");
include_once("obj/Message.obj.php");
include_once("obj/MessagePart.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/Voice.obj.php");
include_once("obj/FieldMap.obj.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if(isset($_GET['id'])) {
	$id = DBSafe($_GET['id']);
	if (userOwns("message",$id) || $USER->authorize('managesystem')) {
		$fields=array();
		for($i=1; $i <= 20; $i++){
			$fieldnum = sprintf("f%02d", $i);
			if(isset($_REQUEST[$fieldnum]))
				$fields[$fieldnum] = $_REQUEST[$fieldnum];
		}
		Message::playAudio($id, $fields);
	}
} elseif (isset($_GET['text'])&&isset($_GET['language'])&&isset($_GET['gender'])) {

	// GET does urldecoding automatically but adds two backslashes to apostrophe and quotation.
	// We have to stripp them here otherwise we add more slashes later 
	// NOTE: Do Not Put $_GET['text'] in database without escaping
	if(get_magic_quotes_gpc()) {
		$text = stripslashes($_GET['text']);
		$language = stripslashes($_GET['language']);
		$gender = stripslashes($_GET['gender']);
	} else {
		$text = $_GET['text'];
		$language = $_GET['language'];
		$gender = $_GET['gender'];
	}
	
	list($contenttype, $data) = renderTts($text, $language, $gender);

	if ($data !== false) {
		header("HTTP/1.0 200 OK");
		if (isset($_GET['download']))
			header('Content-type: application/x-octet-stream');
		else
			header("Content-Type: $contenttype");

		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-Length: " . strlen($data));
		header("Connection: close");

		echo $data;
	}	
}

?>
