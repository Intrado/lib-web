<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");

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
					"attachment"=>count($attachments)?$attachments:false,
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
		
		// NOTE: Should this be broken up into cases for Operators, ReldateOptions, and Fieldmaps? For now, it's clumped together for convenience 
		// USED IN: RuleWidget.js
		case 'fieldmapsdata':
			if (!$USER->authorize('createlist'))
				break;

			$fFields = FieldMap::getAuthorizedFieldMapsLike("f%");
			$gFields = FieldMap::getAuthorizedFieldMapsLike("g%");
			$cFields = FieldMap::getAuthorizedFieldMapsLike("c%");

			$return = array("operators" => $RULE_OPERATORS, "reldateOptions" => $RELDATE_OPTIONS, "fieldmaps" => $fFields + $gFields + $cFields);
			
			break;
			
		// USED IN: RuleWidget.js
		case 'persondatavalues':
			if (!$USER->authorize('createlist') || !isset($_GET['fieldnum']))
				break;
			
			// Adapted from ruleeditform.inc.php
			$fieldnum = dbsafe($_GET['fieldnum']);
			
			$limit = DBFind('Rule', "from rule inner join userrule on rule.id = userrule.ruleid where userid = $USER->id and fieldnum = '$fieldnum'");
			$limitsql = $limit ? $limit->toSQL(false, "value", false, true) : "";
			$return = QuickQueryList("select value from persondatavalues where fieldnum='$fieldnum' $limitsql order by value");
			if (empty($return)) {
				$return = false;
				break;
			}

			// Clean values, needs to be utf8 or will show up blank when json-encoded.
			foreach ($return as &$value) {
				// NOTE: Be careful of htmlentities() when displaying in web browser.
				// TODO: Decide whether to add htmlentities() here or in javascript.
				$value = utf8_encode($value);
			}
			break;
			
		// USED IN: ListForm.php
		case 'lists':
			if (!$USER->authorize('createlist'))
				break;
			
			$deleted = isset($_GET['deleted']) ? '1' : '0';
			$return = DBFindMany("PeopleList", "from list where userid='" . $USER->id . "' and deleted=$deleted order by name");
			break;
			
		// USED IN: ListForm.php
		case 'liststats':
			if (!$USER->authorize('createlist') || !isset($_GET['listid']))
				break;
				
			$list = new PeopleList($_GET['listid']);
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			
			$return = array('id'=>$list->id, 'name'=>$list->name, 'total'=>$renderedlist->total);
			break;
			
		default;
			break;
	}
	
	header("Content-Type: application/json");
	echo json_encode($return);
	exit();	
}
?>
