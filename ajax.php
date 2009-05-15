<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Voice.obj.php");

if (isset($_GET['ajax']) && isset($_GET['type'])) {
	$type = $_GET['type'];
	$return = false;
	switch ($type) {
		case 'message':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || !isset($_GET['messageid']))
				break;
			$message = DBFind("Message","from message where userid=" . $USER->id ." and deleted=0 and id='".dbsafe($_GET['messageid'])."' order by name");
			$message->readHeaders();
			if ($message)
				$return = $message;
			break;
		
		case 'wholemessage':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || !isset($_GET['messageid']))
				break;
			$message = DBFind("Message","from message where userid=" . $USER->id ." and deleted=0 and id='".dbsafe($_GET['messageid'])."' order by name");
			if ($message) {
				$message->readHeaders();
				$parts = DBFindMany("MessagePart","from messagepart where messageid='".dbsafe($_GET['messageid'])."' order by sequence");
				$attachments = DBFindMany("messageattachment","from messageattachment where not deleted and messageid='" . DBSafe($_GET['messageid']) ."'");
				if ($parts)
					$body = $message->format($parts);
				else
					$body = "";
				
				$return = array(
					"lastused"=>$message->lastused,
					"description"=>$message->description,
					"fromname"=>$message->fromname,
					"fromemail"=>$message->fromemail,
					"subject"=>$message->subject,
					"attachment"=>$attachments,
					"body"=>$body,);
			}
			break;
			
		case 'hasmessage':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || (!isset($_GET['messagetype']) || !isset($_GET['messageid'])))
				break;
			if (isset($_GET['messagetype']))
				$return = QuickQuery("select count(id) from message where userid=" . $USER->id ." and not deleted and type='".dbsafe($_GET['messagetype'])."'");
			if (isset($_GET['messageid']))
				$return = QuickQuery("select count(id) from message where userid=" . $USER->id ." and not deleted and messageid='".dbsafe($_GET['messageid'])."'");				
			break;
			
		case 'fieldmap':
			if (!isset($_GET['fieldnum']))
				break;
			$return = FieldMap::getAuthorizedMapNames();
			break;
		
		default;
			break;
	}
	
	header("Content-Type: application/json");
	echo json_encode($return);
	exit();	
}
?>
