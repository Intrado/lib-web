<?
require_once("../obj/MessagePart.obj.php");
require_once("../obj/Person.obj.php");

class MessagePlayback {
	var $jobid;					// job that sent this message
	var $userid;				// user who sent this message
	var $sequence;				// contact sequence (phone1, 2, ... used by voicereply)
	var $messageparts; 			// array of message parts
	var $person; 				// person message was sent for
	var $leavemessage; 			// job option
	var $messageconfirmation; 	// job option

}

?>
