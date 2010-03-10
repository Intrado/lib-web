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

if (isset($_GET['usetext']) && isset($_SESSION['ttstext']) && isset($_SESSION['ttslanguage']) && isset($_SESSION['ttsgender'])) {
	$text = $_SESSION['ttstext'];
	$language = strtolower($_SESSION['ttslanguage']);
	$gender = strtolower($_SESSION['ttsgender']);
	
	$fields=array();
	for($i=1; $i <= 20; $i++){
		$fieldnum = sprintf("f%02d", $i);
		if(isset($_REQUEST[$fieldnum]))
			$fields[$fieldnum] = $_REQUEST[$fieldnum];
	}
	
	$voiceid = false;
	if($gender == "male") {
		$voiceid = QuickQuery("select id from ttsvoice where languagecode=? and gender='male'",false,array($language));	
		if(!$voiceid) {
			$voiceid = QuickQuery("select id from ttsvoice where languagecode=? and gender='female'",false,array($language));
		}
	} else {
		$voiceid = QuickQuery("select id from ttsvoice where languagecode=? and gender='female'",false,array($language));
		if(!$voiceid) {
			$voiceid = QuickQuery("select id from ttsvoice where languagecode=? and gender='male'",false,array($language));	
		}
	}
			
	if($voiceid	=== false)
		$voiceid = 2; // default to english	female
				
	$message = new Message();
	$errors = array();	
	$parts = $message->parse($text,$errors,$voiceid);
	
	if(count($fields) == 0) {
		foreach($parts as $part) {
			if(isset($part->fieldnum)) {
				$fields[$part->fieldnum] = $part->defaultvalue;
			}
			if(!isset($part->voiceid))
				$part->voiceid = $voiceid;
		}	
	} else {
		foreach($parts as $part) {
			if(!isset($part->voiceid))
				$part->voiceid = $voiceid;
		}	
	}
	$renderedparts = Message::renderParts($parts,$fields);
	
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	Message::playParts($renderedparts,"mp3");
	exit();
	
} else if(isset($_GET['id'])) {
	//session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	$id = $_GET['id'] + 0;
	
	if (userOwns("message",$id) || $USER->authorize('managesystem') || isPublished("message", $id)) {
		$fields=array();
		for($i=1; $i <= 20; $i++){
			$fieldnum = sprintf("f%02d", $i);
			if(isset($_REQUEST[$fieldnum]))
				$fields[$fieldnum] = $_REQUEST[$fieldnum];
		}
		Message::playAudio($id, $fields,"mp3");
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
} 

header("HTTP/1.0 200 OK");
header("Content-Type: audio/mp3");
header("Content-disposition: attachment; filename=message.mp3");
header('Pragma: private');
header('Cache-control: private, must-revalidate');
header("Content-Length: 0");
header("Connection: close");


?>