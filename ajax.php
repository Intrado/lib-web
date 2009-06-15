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

function cleanObj ($obj) {
	if (!get_class($obj))
		return false;
	$simpleObj = array();
	foreach ($obj->_fieldlist as $field) {
		if (get_class($obj->$field))
			$simpleObj[$field] = cleanObject($obj->$field);
		else
			$simpleObj[$field] = $obj->$field;
	}
	return $simpleObj;
}

function handleRequest() {
	if (!isset($_GET['type']) && !isset($_POST['type']))
		return false;
	global $USER;
	global $RULE_OPERATORS;
	global $RELDATE_OPTIONS;
	$type = isset($_GET['type'])?$_GET['type']:$_POST['type'];
	error_log("AjaxRequest: ".$type);
	
	switch($type) {
		//--------------------------- SIMPLE OBJECTS, should mirror objects in obj/*.obj.php (simplified to _fieldlist) -------------------------------
		case 'lists':
			$lists =  DBFindMany('PeopleList', ', (name+0) as lettersfirst from list where userid=? and not deleted order by lettersfirst,name', false, array($USER->id));
			$simpleLists = array();
			foreach ($lists as $list)
				$simpleLists[$list->id] = cleanObj($list);
			return $simpleLists;
			
		// Return a message object specified by it's ID
		case 'Message':
			$message = new Message($_GET['id'] + 0);
			if ($message->userid !== $USER->id)
				return false;
			$message->readheaders();
			return cleanObj($message);
		
		// Return a specific message part by it's ID
		case "MessagePart":
			$mp =  new MessagePart($_GET['id'] + 0);
			// if the message part doesn't belong to any message, return false
			if (!$mp->messageid) 
				return false;
			// if the message part doesn't belog to the current user, return false
			if (!userOwns("message", $mp->messageid))
				return false;
			return cleanObj($mp);
		
		//--------------------------- COMPLEX OBJECTS -------------------------------
		// Return message parts belonging to a specific messageid
		case "MessageParts":
			if (!userOwns("message", $_GET['id']))
				return false;
			$mps = DBFindMany("MessagePart","from messagepart where messageid=? order by sequence", false, array($_GET['id']));
			$simpleMPs = array();
			foreach ($mps as $mp)
				$simpleMPs[$mp->id] = cleanObj($mp);
			if (!$simpleMPs)
				return false;
			return $simpleMPs;
		
		// Return messages for the current user, if userid is specified return that user's messages
		case "Messages":
			if(!isset($_GET['messagetype'])){
				return false;
			}
			$userid = $USER->id;
			if(isset($_GET['userid']) && $USER->id !== $_GET['userid']){
				if($USER->authorize(array('managesystem')))
					$userid = DBSafe($_GET['userid']);
				else
					return false;
			} 
			$messages = DBFindMany("Message", "from message where not deleted and userid=? and type=? order by id", false, array($userid, $_GET['messagetype']));
			$simpleMessages = array();
			foreach ($messages as $message)
				$simpleMessages[$message->id] = cleanObj($message);
			if (!$simpleMessages)
				return false;
			return $simpleMessages;
			
		//--------------------------- RPC -------------------------------
		case 'authorizedmapnames':
			if (!isset($_GET['fieldnum']))
				return false;
			return FieldMap::getAuthorizedMapNames();
			
		case 'hasmessage':
			if (!isset($_GET['messagetype']) && !isset($_GET['messageid']))
				return false;
			if (isset($_GET['messagetype']))
				return QuickQuery("select count(id) from message where userid=? and not deleted and type=?", false, array($USER->id, $_GET['messagetype']))?true:false;
			if (isset($_GET['messageid']))
				return QuickQuery("select count(id) from message where userid=? and not deleted and id=?", false, array($USER->id, $_GET['messageid']))?true:false;
			
		case 'listrules':
			// $_GET['listids'] should be json-encoded array.
			if (!$USER->authorize('createlist') || !isset($_GET['listids']))
				return false;
			$listids = json_decode($_GET['listids']);
			if (!is_array($listids))
				return false;
			$listrules = array();
			foreach ($listids as $id) {
				// Check for bad ID and ownership
				if (($id + 0 !== $id) || !userOwns('list', $id))
					continue;
				$list = new PeopleList($id);
				if ($list) {
					// TODO: Check rules against FieldMap::getAuthorizedNames(), set $listrules[$id][$fieldnum] = 'unauthorized', examine $list->getListRules() for details.
					$listrules[$id] = $list->getListRules();
				}
			}
			return $listrules;
			
		case 'liststats':
			// $_GET['listids'] should be json-encoded array.
			if (!$USER->authorize('createlist') || !isset($_GET['listids']))
				return false;
			$listids = json_decode($_GET['listids']);
			if (!is_array($listids))
				return false;
			$stats = array();
			foreach ($listids as $id) {
				// Check for bad ID and ownership
				if (($id + 0 !== $id) || !userOwns('list', $id))
					continue;
				$list = new PeopleList($id);
				if ($list) {
					$renderedlist = new RenderedList($list);
					$renderedlist->calcStats();
					$stats[]= array(
						'id' => $list->id,
						'name' => $list->name,
						'removed' => $renderedlist->totalremoved,
						'added' => $renderedlist->totaladded,
						'total' => $renderedlist->total);
				}
			}
			return $stats;
			
		case 'persondatavalues':
			if (!isset($_GET['fieldnum']) || !array_key_exists($_GET['fieldnum'], FieldMap::getAuthorizedMapNames()))
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
			if ($message->userid !== $USER->id)
				return false;
			$message->readHeaders();
			$parts = DBFindMany("MessagePart","from messagepart where messageid=? order by sequence", false, array($_GET['id']));
			$attachments = DBFindMany("MessageAttachment","from messageattachment where not deleted and messageid=?", false, array($_GET['id']));
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
			$messagefields = DBFindMany("FieldMap", "from fieldmap where fieldnum in (select distinct fieldnum from messagepart where messageid=?)", false, array($_GET['id']));
			if (count($messagefields) > 0) {
				foreach ($messagefields as $fieldmap) {
					$fields[$fieldmap->fieldnum] = $fieldmap;
					$fields[$fieldmap->fieldnum]->optionsarray = explode(",",$fieldmap->options);
				}
			}
			return count($fields)?$fields:false;
	}
}

header('Content-Type: application/json');
$data = handleRequest();
echo json_encode(!empty($data) ? $data : false);
?>
