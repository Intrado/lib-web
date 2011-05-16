<?
require_once("inc/common.inc.php");
require_once("obj/Voice.obj.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/MessagePart.obj.php");
require_once("inc/appserver.inc.php");
require_once("inc/thrift.inc.php");
require_once $GLOBALS['THRIFT_ROOT'].'/packages/commsuite/CommSuite.php';


if(!isset($_GET['uid']) && !isset($_SESSION["previewmessage"])) {
	exit();
} else {
	$uid = $_GET['uid'];
	$partnum = $_GET['partnum'] - 1;
	
	$hascorrectuid = isset($_SESSION["previewmessage"]["uid"]) && $_SESSION["previewmessage"]["uid"] == $uid;
	$ismissingpart = isset($partnum) && !isset($_SESSION["previewmessage"]["parts"][$partnum]);
	
	if (!$hascorrectuid || $ismissingpart) {
		exit();
	}
}

if(isset($partnum)) {
	$part = $_SESSION["previewmessage"]["parts"][$partnum];
	
	switch($part->type) {
		case "T":
			$voice = new Voice($part->voiceid);
			$audiopart = ttsGetForTextLanguageGenderFormat($part->txt, $voice->language, $voice->gender, "mp3");
			break;
		case "A":
			$contentid = QuickQuery("select contentid from audiofile where id=? and userid=?",false,array($part->audiofileid,$USER->id));
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
	$audiopart = phoneMessageGetMp3AudioFile($_SESSION["previewmessage"]["parts"]);
	
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
