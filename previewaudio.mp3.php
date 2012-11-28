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

if(isset($_GET['partnum'])) {
	$partnum = $_GET['partnum'] - 1;
	$part = $_SESSION["previewmessage"]["parts"][$partnum];
	
	switch($part["type"]) {
		case "T":
			$voice = new Voice($part["voiceid"]);
			$audiopart = ttsGetForTextLanguageGenderFormat($part["txt"], $voice->language, $voice->gender, "mp3");
			break;
		case "A":
			$contentid = QuickQuery("select contentid from audiofile where id=?",false,array($part["audiofileid"]));
			$audiopart = audioFileGetForFormat($contentid, "mp3");
			break;
	}
	if (!$audiopart) {
		header("HTTP/1.0 404 Not Found");
	} else {
		header("HTTP/1.0 200 OK");
		header("Content-Type: $audiopart->contenttype");
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-Length: " . strlen($audiopart->data));
		header("Connection: close");
		echo $audiopart->data;
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
			Message::playParts(array(),"mp3",$mediapath . $mediafile);		
			exit();
			
		} else {
			$mediafile = strrchr($mediafile,'/');
			if($mediafile !== false) {
				$mediafile = substr($mediafile,1);
				if($mediafile == "DefaultIntro.wav" || $mediafile == "EmergencyIntro.wav") {
					Message::playParts(array(),"mp3",$mediapath . $mediafile);				
					exit();
				}
			}
		}
} else {
	$messagepartdtos = array();
	$parts = $_SESSION["previewmessage"]["parts"];
	
	foreach ($parts as $part) {
		$messagepartdto = new commsuite_MessagePartDTO();
		
		switch($part["type"]) {
			case "T":
				$messagepartdto->type = commsuite_MessagePartTypeDTO::T;
				$voice = new Voice($part["voiceid"]);
				$messagepartdto->gender = $voice->gender;
				$messagepartdto->languagecode = $voice->language;
				$messagepartdto->txt = $part["txt"];
				break;
			case "A":
				$messagepartdto->type = commsuite_MessagePartTypeDTO::A;
				$messagepartdto->contentid = QuickQuery("select contentid from audiofile where id=?",false,array($part["audiofileid"])) + 0;
				break;
		}
		
		$messagepartdto->defaultvalue = "";
		$messagepartdto->fieldnum = "";
		
		$messagepartdtos[] = $messagepartdto;
	}
	
	$audiofull = phoneMessageGetMp3AudioFile($messagepartdtos);
	
	if (!$audiofull) {
		header("HTTP/1.0 404 Not Found");
	} else {
		header("HTTP/1.0 200 OK");
		header("Content-Type: $audiofull->contenttype");
		if (isset($_GET['download']))
			header("Content-disposition: attachment; filename=message.mp3");
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-Length: " . strlen($audiofull->data));
		header("Connection: close");
		echo $audiofull->data;
	}
} 

?>
