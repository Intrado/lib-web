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
		Message::playAudio($message->id, $fields);
		exit();
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
		convertWavToMp3($mediapath . $mediafile);
		exit();
		
	} else {
		$mediafile = strrchr($mediafile,'/'); //FIXME this is a mess
		if($mediafile !== false) {
			$mediafile = substr($mediafile,1);
			if($mediafile == "DefaultIntro.wav" || $mediafile == "EmergencyIntro.wav") {
				convertWavToMp3($mediapath . $mediafile);
				exit();
			}
		}
	}
} 


header("HTTP/1.0 200 OK");
header("Content-Type: audio/mpeg");
header("Content-disposition: attachment; filename=message.mp3");
header('Pragma: private');
header('Cache-control: private, must-revalidate');
header("Content-Length: 0");
header("Connection: close");


?>