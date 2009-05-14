<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/AudioFile.obj.php");

if (isset($_GET['ajax']) && isset($_GET['type'])) {
	$type = $_GET['type'];
	$return = false;
	switch ($type) {
		case 'message':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || !isset($_GET['messageid']))
				break;
			$message = DBFind("Message","from message where userid=" . $USER->id ." and deleted=0 and id='".dbsafe($_GET['messageid'])."' order by name");
			if ($message && isset($_GET['parts']))
				$parts = DBFindMany("MessagePart","from messagepart where messageid='".dbsafe($_GET['messageid'])."' order by sequence");
			
			if ($message && isset($_GET['attachments']))
				$attachments = DBFindMany("messageattachment","from messageattachment where not deleted and messageid='" . DBSafe($_GET['messageid']) ."'");
			
			if ($message) {
				$message->readHeaders();
				$return = array('message'=>$message, 'parts'=>count(isset($parts)?$parts:array())?$parts:false, 'attachments'=>count(isset($attachments)?$attachments:array())?$attachments:false);
			} else {
				return false;
			}
			break;
			
		default;
			break;
	}
	
	header("Content-Type: application/json");
	echo json_encode($return);
	exit();	
}
?>
