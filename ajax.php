<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/MessageAttachment.obj.php");

if (isset($_GET['ajax']) && isset($_GET['type'])) {
	$type = $_GET['type'];
	$return = "";
	switch ($type) {
		case 'message':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || !isset($_GET['messagetype']))
				exit();
			$message = DBFind("Message","from message where userid=" . $USER->id ." and deleted=0 and id='".dbsafe($_GET['id'])."' and type='".dbsafe($_GET['messagetype'])."' order by name");
			$return = $message;
			break;
	
		case 'messagepart':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || !isset($_GET['messageid']))
				exit();
			$return = DBFind("MessagePart","from message where userid=" . $USER->id ." and deleted=0 and messageid='".dbsafe($_GET['messageid'])."' order by name");
			break;
			
		default;
			exit();
	}
	
	header("Content-Type: application/json");
	echo json_encode($return);
	exit();	
}
?>
