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

function handleRequest($type) {
	global $USER;
	global $RULE_OPERATORS;
	global $RELDATE_OPTIONS;
	
	switch($type) {
		//--------------------------- SIMPLE OBJECTS -------------------------------
		case 'fieldmap':
			if (!isset($_GET['fieldnum']))
				return false;
			return FieldMap::getAuthorizedMapNames();
		
		case 'lists':
			if (!$USER->authorize('createlist'))
				return false;
			return DBFindMany('PeopleList', ', (name +0) as lettersfirst from list where userid=? and deleted=0 order by lettersfirst,name', false, array($USER->id));
			
		case 'message':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || !isset($_GET['messageid']))
				return false;
			$message = DBFind("Message","from message where userid=" . $USER->id ." and deleted=0 and id='".dbsafe($_GET['messageid'])."' order by name");
			$message->readHeaders();
			return $message;
			
		case 'persondatavalues':
			if (!$USER->authorize('createlist') || !isset($_GET['fieldnum']))
				return false;
			$limit = DBFind('Rule', 'from rule inner join userrule on rule.id = userrule.ruleid where userid=? and fieldnum=?', false, array($USER->id, $_GET['fieldnum']));
			$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
			return QuickQueryList("select value from persondatavalues where fieldnum=? $limitsql order by value", false, false, array($_GET['fieldnum']));
			
		//--------------------------- COMPLEX OBJECTS -------------------------------
		case 'fieldmapsdata': // USED IN: RuleWidget.js
			if (!$USER->authorize('createlist'))
				return false;
			return array(
				'operators' => $RULE_OPERATORS,
				'reldateOptions' => $RELDATE_OPTIONS,
				'fieldmaps' => FieldMap::getAuthorizedFieldMapsLike('%'));

		case 'liststats': // USED IN: ListForm.php
			if (!$USER->authorize('createlist') || !isset($_GET['listid']))
				return false;
			$list = new PeopleList($_GET['listid']);
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			return array(
				'id'=>$list->id,
				'name'=>$list->name,
				'total'=>$renderedlist->total);
				
		case 'wholemessage':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || !isset($_GET['messageid']))
				return false;
			$message = DBFind("Message","from message where userid=" . $USER->id ." and deleted=0 and id='".dbsafe($_GET['messageid'])."' order by name");
			if (!$message)
				return false;

			$message->readHeaders();
			$parts = DBFindMany("MessagePart","from messagepart where messageid='".dbsafe($_GET['messageid'])."' order by sequence");
			$attachments = DBFindMany("messageattachment","from messageattachment where not deleted and messageid='" . DBSafe($_GET['messageid']) ."'");
			if ($parts)
				$body = $message->format($parts);
			else
				$body = "";
			
			return array(
				"lastused"=>$message->lastused,
				"description"=>$message->description,
				"fromname"=>$message->fromname,
				"fromemail"=>$message->fromemail,
				"subject"=>$message->subject,
				"attachment"=>count($attachments)?$attachments:false,
				"body"=>$body);

		//--------------------------- RPC -------------------------------
		case 'hasmessage':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || (!isset($_GET['messagetype']) || !isset($_GET['messageid'])))
				return false;
			if (isset($_GET['messagetype']))
				return QuickQuery("select count(id) from message where userid=" . $USER->id ." and not deleted and type='".dbsafe($_GET['messagetype'])."'");
			if (isset($_GET['messageid']))
				return QuickQuery("select count(id) from message where userid=" . $USER->id ." and not deleted and messageid='".dbsafe($_GET['messageid'])."'");
			
		default:
			return false;
	}
}

header('Content-Type: application/json');
$data = handleRequest($_GET['type']);
echo json_encode(!empty($data) ? $data : false);
?>