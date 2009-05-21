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

function handleRequest() {
	global $USER;
	global $RULE_OPERATORS;
	global $RELDATE_OPTIONS;
	
	switch($_GET['type']) {
		//--------------------------- SIMPLE OBJECTS, should mirror objects in obj/*.obj.php -------------------------------
		case 'lists':
			if (!$USER->authorize('createlist'))
				return false;
			return DBFindMany('PeopleList', ', (name+0) as lettersfirst from list where userid=? and deleted=0 order by lettersfirst,name', false, array($USER->id));
			
		case 'message':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || !isset($_GET['messageid']))
				return false;
			$message = DBFind("Message","from message where userid=" . $USER->id ." and deleted=0 and id='".dbsafe($_GET['messageid'])."' order by name");
			$message->readHeaders();
			return $message;
			
		//--------------------------- COMPLEX OBJECTS -------------------------------

		//--------------------------- RPC -------------------------------
		case 'fieldmapnames':
			if (!isset($_GET['fieldnum']))
				return false;
			return FieldMap::getAuthorizedMapNames();
			
		case 'hasmessage':
			if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms')) || (!isset($_GET['messagetype']) || !isset($_GET['messageid'])))
				return false;
			if (isset($_GET['messagetype']))
				return QuickQuery("select count(id) from message where userid=" . $USER->id ." and not deleted and type='".dbsafe($_GET['messagetype'])."'");
			if (isset($_GET['messageid']))
				return QuickQuery("select count(id) from message where userid=" . $USER->id ." and not deleted and messageid='".dbsafe($_GET['messageid'])."'");
			
		case 'liststats':
			// Assumes $_GET['listids'] is json-encoded array.
			
			if (!$USER->authorize('createlist') || !isset($_GET['listids']))
				return false;
			$stats = array();
			$listids = json_decode($_GET['listids']);
			foreach ($listids as $id) {
				$list = new PeopleList($id);
				$renderedlist = new RenderedList($list);
				$renderedlist->calcStats();
				$stats[]= array(
					'id' => $list->id,
					'name' => $list->name,
					'total' => $renderedlist->total);
			}
			return $stats;

		case 'persondatavalues':
			if (!$USER->authorize('createlist') || !isset($_GET['fieldnum']))
				return false;
			$limit = DBFind('Rule', 'from rule inner join userrule on rule.id = userrule.ruleid where userid=? and fieldnum=?', false, array($USER->id, $_GET['fieldnum']));
			$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
			return QuickQueryList("select value from persondatavalues where fieldnum=? $limitsql order by value", false, false, array($_GET['fieldnum']));
			
		case 'rulewidgetsettings':
			if (!$USER->authorize('createlist'))
				return false;
			return array(
				'operators' => $RULE_OPERATORS,
				'reldateOptions' => $RELDATE_OPTIONS,
				'fieldmaps' => FieldMap::getAllAuthorizedFieldMaps());
				
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
				
		default:
			return false;
	}
}

header('Content-Type: application/json');
$data = handleRequest();
echo json_encode(!empty($data) ? $data : false);
?>
