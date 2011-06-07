<?
require_once("inc/common.inc.php");
require_once("obj/Voice.obj.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/MessagePart.obj.php");
require_once("inc/appserver.inc.php");
require_once("obj/Language.obj.php");


require_once('thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once("inc/thrift.inc.php");
require_once $GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php';


if(!isset($_GET['uid']) && !isset($_SESSION["previewmessage"])) {
	exit();
} else {
	$uid = $_GET['uid'];
	
	$hascorrectuid = isset($_SESSION["previewmessage"]["uid"]) && $_SESSION["previewmessage"]["uid"] == $uid;
	$ismissingpart = isset($_GET['partnum']) && !isset($_SESSION["previewmessage"]["parts"][$_GET['partnum'] - 1]);
	
	if (!$hascorrectuid || $ismissingpart) {
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
			$contentid = QuickQuery("select contentid from audiofile where id=? and userid=?",false,array($part["audiofileid"],$USER->id));
			$audiopart = audioFileGetForFormat($contentid, "mp3");
			break;
	}
	header("HTTP/1.0 200 OK");
	header("Content-Type: " . $audiopart->contenttype);
	header("Content-disposition: attachment; filename=message.mp3");
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	header("Content-Length: " . strlen($audiopart->data));
	header("Connection: close");
	echo $audiopart->data;
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
				$messagepartdto->contentid = QuickQuery("select contentid from audiofile where id=? and userid=?",false,array($part["audiofileid"],$USER->id)) + 0;
				break;
		}
		
		$messagepartdto->defaultvalue = "";
		$messagepartdto->fieldnum = "";
		
		$messagepartdtos[] = $messagepartdto;
	}
	
	$audiofull = phoneMessageGetMp3AudioFile($messagepartdtos);
	
	header("HTTP/1.0 200 OK");
	if (isset($_GET['download']))
		header('Content-type: application/x-octet-stream');
	else {
		header("Content-Type: " . $audiofull->contenttype);
	}
	header("Content-disposition: attachment; filename=message.mp3");
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	header("Content-Length: " . strlen($audiofull->data));
	header("Connection: close");
	echo $audiofull->data;
}

?>
