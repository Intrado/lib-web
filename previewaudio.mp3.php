<?
require_once("inc/common.inc.php");
require_once("obj/Voice.obj.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/MessagePart.obj.php");
require_once("inc/appserver.inc.php");
require_once("obj/Language.obj.php");
require_once("obj/Message.obj.php");


if(!isset($_GET['uid']) && !isset($_SESSION["previewmessage"])) {
	exit();
} else {
	$uid = $_GET['uid'];
	
	$hascorrectuid = isset($_SESSION["previewmessage"]["uid"]) && $_SESSION["previewmessage"]["uid"] == $uid;
	$ismissingpart = isset($_GET['partnum']) && !isset($_SESSION["previewmessage"]["parts"][$_GET['partnum'] - 1]);
	$ismediafile = isset($_SESSION["previewmessage"]["mediafile"]);

	if (!$hascorrectuid || (!$ismediafile && $ismissingpart)) {
		exit();
	}
}

$data = "";
if(isset($_GET['partnum'])) {
	$partnum = $_GET['partnum'] - 1;
	$part = $_SESSION["previewmessage"]["parts"][$partnum];
	
	switch($part["type"]) {
		case "T":
			$voice = new Voice($part["voiceid"]);
			$audiopart = ttsGetForTextLanguageGenderNameFormat($part["txt"], $voice->language, $voice->gender, $voice->name, "mp3");
			break;
		case "A":
			$contentid = QuickQuery("select contentid from audiofile where id=?",false,array($part["audiofileid"]));
			$audiopart = audioFileGetForFormat($contentid, "mp3");
			break;
	}
	if (!$audiopart) {
		$data = false;
	} else {
		$data = $audiopart->data;
	}
} else if($ismediafile) {
		$mediapath = "media/";
		$mediafile = $_SESSION["previewmessage"]["mediafile"];
		if(in_array($mediafile, array(
					"DefaultIntro.wav",
					"EmergencyIntro.wav",
					"es/DefaultIntro.wav",
					"es/EmergencyIntro.wav"
				))) {
			$data = convertWavToMp3($mediapath . $mediafile);
		} else {
			$mediafile = strrchr($mediafile,'/');
			if($mediafile !== false) {
				$mediafile = substr($mediafile,1);
				if($mediafile == "DefaultIntro.wav" || $mediafile == "EmergencyIntro.wav") {
					$data = convertWavToMp3($mediapath . $mediafile);
				}
			}
		}
} else {
	//FIXME use Message::playAudio() by passing parts instead of messageid?
	$messagepartdtos = array();
	$parts = $_SESSION["previewmessage"]["parts"];
	
	foreach ($parts as $part) {
		$messagepartdto = new \commsuite\MessagePartDTO();
		
		switch($part["type"]) {
			case "T":
				$messagepartdto->type = \commsuite\MessagePartTypeDTO::T;
				$voice = new Voice($part["voiceid"]);
				$messagepartdto->name = $voice->name;
				$messagepartdto->gender = $voice->gender;
				$messagepartdto->languagecode = $voice->language;
				$messagepartdto->txt = $part["txt"];
				break;
			case "A":
				$messagepartdto->type = \commsuite\MessagePartTypeDTO::A;
				$messagepartdto->contentid = QuickQuery("select contentid from audiofile where id=?",false,array($part["audiofileid"])) + 0;
				break;
		}
		
		$messagepartdto->defaultvalue = "";
		$messagepartdto->fieldnum = "";
		
		$messagepartdtos[] = $messagepartdto;
	}
	
	$audiofull = phoneMessageGetMp3AudioFile($messagepartdtos);
	
	if (!$audiofull) {
		$data = false;
	} else {
		$data = $audiofull->data;
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
