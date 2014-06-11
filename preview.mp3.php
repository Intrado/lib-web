<?
require_once("inc/common.inc.php");

require_once('inc/securityhelper.inc.php');
require_once('inc/content.inc.php');
require_once('inc/appserver.inc.php');
require_once("obj/Content.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Job.obj.php");

$data = "";
if(isset($_GET['id'])) {
	//session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	$message = new Message($_GET['id'] + 0);
		
	if (userCanSee("message",$message->id) || $USER->authorize('managesystem')) {
		$fields=array();
		$languagefield = FieldMap::getLanguageField();
		for($i=1; $i <= 20; $i++){
			$fieldnum = sprintf("f%02d", $i);
			if(isset($_REQUEST[$fieldnum])) {
				if($languagefield == $fieldnum) {
					$languages = QuickQueryList("select code,name from language order by name",true);
					$fields[$fieldnum] = $languages[$_REQUEST[$fieldnum]];
				} else
					$fields[$fieldnum] = $_REQUEST[$fieldnum];
			}
		}

		$audioFull = Message::getMp3AudioFull($message->id, $fields);

		if ($audioFull) {
			$data = $audioFull->data;
		} else {
			$data = false;
		}
	}
} else if(isset($_GET['mediafile'])) {
	$mediapath = "media/";
	$mediafile = $_GET['mediafile'];
	if(in_array($mediafile, array(
			"DefaultIntro.wav",
			"EmergencyIntro.wav",
			"es/DefaultIntro.wav",
			"es/EmergencyIntro.wav"
		))) {
		$data = convertWavToMp3($mediapath . $mediafile);

	} else {
		$mediafile = strrchr($mediafile,'/'); //FIXME this is a mess
		if($mediafile !== false) {
			$mediafile = substr($mediafile,1);
			if($mediafile == "DefaultIntro.wav" || $mediafile == "EmergencyIntro.wav") {
				$data = convertWavToMp3($mediapath . $mediafile);
			}
		}
	}
}

if ($data === false) {
	header("HTTP/1.0 404 Not Found");
} else {
	header("HTTP/1.0 200 OK");
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	if (isset($_GET['download']))
		header("Content-disposition: attachment; filename=message.mp3");
	header("Content-Type: audio/mpeg");
	header("Content-Length: " . strlen($data));
	header("Connection: close");

	echo $data;
}
?>