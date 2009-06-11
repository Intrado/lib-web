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
	$type = isset($_GET['type'])?$_GET['type']:$_POST['type'];
	error_log("AjaxRequest: ".$type);
	
	switch($type) {
		//--------------------------- SIMPLE OBJECTS, should mirror objects in obj/*.obj.php -------------------------------
		case 'lists':
			if (!$USER->authorize('createlist'))
				return false;
			return DBFindMany('PeopleList', ', (name+0) as lettersfirst from list where userid=? and deleted=0 order by lettersfirst,name', false, array($USER->id));
		
		// Return a message object specified by it's ID
		case 'Message':
			$message = new Message($_GET['id']);
			$message->readheaders();
			return $message;
		
		// Return a specific message part by it's ID
		case "MessagePart":
			return new MessagePart($_GET['id']);
		
		//--------------------------- COMPLEX OBJECTS -------------------------------
		// Return message parts belonging to a specific messageid
		case "MessageParts":
			return DBFindMany("MessagePart","from messagepart where messageid='".dbsafe($_GET['id'])."' order by sequence");
		
		// Return messages for the current user or user(userid) if the current user has manager rights
		case "Messages":
			if(!isset($_GET['messagetype'])){
				return false;
			}
			if(!isset($_GET['userid'])){
				return QuickQueryList("select id,name from message where deleted=0 and userid=? and type=? order by id", true,false,array($USER->id, $_GET['messagetype']));		
			}
			if ($USER->id != $_GET['userid'] && !$USER->authorize(array('managesystem','manageaccount'))){
				return false;
			}
			return QuickQueryList("select id,name from message where deleted=0 and userid=? and type=? order by id", true,false,array($_GET['userid'], $_GET['messagetype']));		
		//--------------------------- RPC -------------------------------
		case 'authorizedmapnames':
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
			
		case 'listrules':
			// Assumes $_GET['listids'] is json-encoded array.
			if (!$USER->authorize('createlist') || !isset($_GET['listids']))
				return false;
			$listrules = array();
			$listids = json_decode($_GET['listids']);
			foreach ($listids as $id) {
				$list = new PeopleList($id);
				$listrules[$id] = $list->getListRules();
			}
			return $listrules;
			
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
					'removed' => $renderedlist->totalremoved,
					'added' => $renderedlist->totaladded,
					'total' => $renderedlist->total);
			}
			return $stats;
			
		case 'persondatavalues':
			if (!isset($_GET['fieldnum']))
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
		
		// Return a whole message including it's message parts formatted into body text and any attachments.
		case 'previewmessage':
			$message = new Message($_GET['id']);
			$message->readHeaders();
			$parts = DBFindMany("MessagePart","from messagepart where messageid='".dbsafe($_GET['id'])."' order by sequence");
			$attachments = DBFindMany("messageattachment","from messageattachment where not deleted and messageid='" . DBSafe($_GET['id']) ."'");
			$simple = false;
			if (count($parts) == 1) 
				foreach ($parts as $id => $part)
					if ($part->type == "A") $simple = true;
			
			return array(
				"lastused"=>$message->lastused,
				"description"=>$message->description,
				"fromname"=>$message->fromname,
				"fromemail"=>$message->fromemail,
				"subject"=>$message->subject,
				"type"=>$message->type,
				"simple"=>$simple,
				"attachment"=>count($attachments)?$attachments:false,
				"body"=>count($parts)?$message->format($parts):""
			);
		
		case "messagefields":
			$fields = array();
			$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (select distinct fieldnum from messagepart where messageid='".dbsafe($_GET['id'])."')");
			if (count($messagefields) > 0) {
				foreach ($messagefields as $fieldmap) {
					$fields[$fieldmap->fieldnum] = $fieldmap;
					$fields[$fieldmap->fieldnum]->optionsarray = explode(",",$fieldmap->options);
				}
			}
			return count($fields)?$fields:false;
		
		// return an array of the requested fields and their person data values
		case "fieldvalues":
			$requestfields = json_decode($_POST['fields']);
			$fielddata = array();
			foreach ($requestfields as $field) {
				$limit = DBFind('Rule', 'from rule inner join userrule on rule.id = userrule.ruleid where userid=? and fieldnum=?', false, array($USER->id, $field));
				$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
				$fielddata[$field] = QuickQueryList("select value from persondatavalues where fieldnum=? $limitsql order by value", false, false, array($field));
			}
			return $fielddata;
			
		default:
			return false;
	}
}

header('Content-Type: application/json');
$data = handleRequest();
echo json_encode(!empty($data) ? $data : false);
?>
