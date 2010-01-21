<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
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
	if (!isset($_GET['type']) && !isset($_POST['type']))
		return false;
	global $USER;
	global $RULE_OPERATORS;
	global $RELDATE_OPTIONS;
	$type = isset($_GET['type'])?$_GET['type']:$_POST['type'];

	switch($type) {
		//--------------------------- SIMPLE OBJECTS, should mirror objects in obj/*.obj.php (simplified to _fieldlist) -------------------------------
		case 'lists':
			return cleanObjects(DBFindMany('PeopleList', ', (name+0) as lettersfirst from list where userid=? and not deleted order by lettersfirst,name', false, array($USER->id)));

		// Returns a map of audiofiles belonging to the current user; a messagegroupid may be specified, but the results will still include global audio files (where messagegroupid is null). Results are sorted by recorddate.
		case 'AudioFiles':
			$messagegroupid = !empty($_GET['messagegroupid']) ? $_GET['messagegroupid'] + 0 : 0;
			return cleanObjects(DBFindMany('AudioFile', 'from audiofile where userid=? and (messagegroupid=? or messagegroupid is null) and not deleted order by messagegroupid desc, recorddate desc', false, array($USER->id, $messagegroupid)));

		// Return an AudioFile object specified by its ID.
		case 'AudioFile':
			if (!isset($_GET['id']))
				return false;
			$audioFile = new AudioFile($_GET['id'] + 0);
			if ($audioFile->userid !== $USER->id)
				return false;
			return cleanObjects($audioFile);

		// Return a message object specified by it's ID
		case 'Message':
			if (!isset($_GET['id']))
				return false;
			$message = new Message($_GET['id'] + 0);
			if ($message->userid !== $USER->id)
				return false;
			return cleanObjects($message);

		// Return a specific message part by it's ID
		case "MessagePart":
			if (!isset($_GET['id']))
				return false;
			$mp =  new MessagePart($_GET['id'] + 0);
			// if the message part doesn't belong to any message, return false
			if (!$mp->messageid)
				return false;
			// if the message part doesn't belog to the current user, return false
			if (!userOwns("message", $mp->messageid))
				return false;
			return cleanObjects($mp);

		//--------------------------- COMPLEX OBJECTS -------------------------------
		// Return message parts belonging to a specific messageid
		case "MessageParts":
			if (!isset($_GET['id']))
				return false;
			if (!userOwns("message", $_GET['id']))
				return false;
			return cleanObjects(DBFindMany("MessagePart","from messagepart where messageid=? order by sequence", false, array($_GET['id'])));

		// Return messages for the current user, if userid is specified return that user's messages
		case "Messages":
			if(!isset($_GET['messagetype'])){
				return false;
			}
			$userid = $USER->id;
			if(isset($_GET['userid']) && $USER->id !== $_GET['userid']){
				if($USER->authorize(array('managesystem')))
					$userid = $_GET['userid'];
				else
					return false;
			}
			return QuickQueryList('select id,name from message where not deleted and userid=? and type=? order by id', true, false, array($userid, $_GET['messagetype']));

		//--------------------------- RPC -------------------------------
		case 'messagegroupsummary':
			if (!isset($_GET['messagegroupid']))
				return false;
			return MessageGroup::getSummary($_GET['messagegroupid']);

		case 'hasmessage':
			if (!isset($_GET['messagetype']) && !isset($_GET['messageid']))
				return false;
			if (isset($_GET['messagetype']))
				return QuickQuery("select count(id) from message where userid=? and not deleted and type=?", false, array($USER->id, $_GET['messagetype']))?true:false;
			if (isset($_GET['messageid']))
				return QuickQuery("select count(id) from message where userid=? and not deleted and id=?", false, array($USER->id, $_GET['messageid']))?true:false;

		case 'listrules':
			// $_GET['listids'] should be json-encoded array.
			if (!isset($_GET['listids']))
				return false;
			$listids = json_decode($_GET['listids']);
			if (!is_array($listids))
				return false;
			$listrules = array();
			$fieldmaps = FieldMap::getAllAuthorizedFieldMaps();
			foreach ($listids as $id) {
				if (!userOwns('list', $id))
					continue;
				$list = new PeopleList($id+0);
				$listrules[$id] = $list->getListRules();
				foreach ($listrules[$id] as $ruleid => $rule) {
					if (!$USER->authorizeField($rule->fieldnum))
						unset($listrules[$id][$ruleid]);
				}
			}
			return cleanObjects($listrules);

		case 'liststats':
			// $_GET['listids'] should be json-encoded array.
			if (!isset($_GET['listids']))
				return false;
			$listids = json_decode($_GET['listids']);
			if (!is_array($listids))
				return false;
			$stats = array();
			foreach ($listids as $id) {
				if (!userOwns('list', $id))
					continue;
				$list = new PeopleList($id+0);
				$renderedlist = new RenderedList($list);
				$renderedlist->calcStats();
				$stats[$list->id]= array(
					'name' => $list->name,
					'advancedlist' => !$list->deleted || $renderedlist->totalremoved || $renderedlist->totaladded,
					'totalremoved' => $renderedlist->totalremoved,
					'totaladded' => $renderedlist->totaladded,
					'totalrule' => $renderedlist->total - $renderedlist->totaladded + $renderedlist->totalremoved,
					'total' => $renderedlist->total);
			}
			return $stats;

		case 'getdatavalues':
			// if no fieldnum requested then return false
			if (!isset($_GET['fieldnum']))
				return false;

			$fieldnum = $_GET['fieldnum'];

			// if an f,g or c field was requested
			if (in_array(substr($fieldnum, 0, 1), array('f','g','c'))) {
				//  if the user is not authorized to it
				if (!$USER->authorizeField($fieldnum))
					return false;

				// query the field data values
				$limit = DBFind('Rule', 'from rule inner join userassociation on rule.id = userassociation.ruleid where userassociation.type = "rule" and userid=? and fieldnum=?', false, array($USER->id, $_GET['fieldnum']));
				$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
				return QuickQueryList("select value from persondatavalues where fieldnum=? $limitsql order by value", false, false, array($_GET['fieldnum']));

			// if it's an organization field
			} else if ($fieldnum == 'organization') {

				// find organization ids that the user is limited to see
				$limit = QuickQueryList("select organizationid from userassociation where userid = ? and type = 'organization' and organizationid not NULL", false, false, array($USER->id));

				// if there are assigned org ids, build a limit
				$orgids = array();
				if ($limit)
					foreach ($limit as $l)
						$orgids[] .= '?';

				$limitsql = ($orgids)? " and id in (" . implode(",", $orgids) . ") " : "";

				// select all organization values the user is authorized to see
				return QuickQueryList("select orgkey from organization where 1 " . $limitsql, false, false, $limit);

			// if it's a section field
			} else if ($fieldnum == 'section') {

				// find sections ids that the user is limited to see
				// TODO: Do sections need to be restricted by organization associations here or is that handled when the user associations are created?
				$limit = QuickQueryList("select sectionid from userassociation where userid = ? and type = 'section' and sectionid not NULL", false, false, array($USER->id));

				// if there are assigned section ids, build a limit
				$secids = array();
				if ($limit)
					foreach ($limit as $l)
						$secids[] .= '?';

				$limitsql = ($secids)? " and id in (" . implode(",", $secids) . ") " : "";

				// select all organization values the user is authorized to see
				return QuickQueryList("select skey from organization where 1 " . $limitsql, false, false, $limit);

			// unknown fieldnum?
			} else {
				return false;
			}

		case 'rulewidgetsettings':
			// check userassociations for org and section to see if we should show organization/section selection
			$userhasorg = (QuickQuery('select count(id) from userassociation where type = "organization" and organizationid not null and userid = ?', false, array($USER->id)) > 0);
			$userhassection = (QuickQuery('select count(id) from userassociation where type = "section" and sectionid not null and userid = ?', false, array($USER->id)) > 0);

			$custhasorg = (QuickQuery('select count(id) from organization where not deleted') > 0);
			$custhassection = (QuickQuery('select count(id) from section') > 0);

			return array(
				'operators' => $RULE_OPERATORS,
				'reldateOptions' => $RELDATE_OPTIONS,
				'fieldmaps' => cleanObjects(FieldMap::getAllAuthorizedFieldMaps()),
				'hasorg' => $hasorg,
				'hassection' => $hassection);

		// Return a whole message including it's message parts formatted into body text and any attachments.
		case 'previewmessage':
			if (!isset($_GET['id']))
				return false;
			$message = new Message($_GET['id']+0);
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
				"attachment"=>count($attachments)?cleanObjects($attachments):false,
				"body"=>count($parts)?$message->format($parts):""
			);

		case 'messagegrid':
			// TODO lookup default language code
			// TODO lookup display names for all language messages to display
			if (!isset($_GET['id']))
				return false;
			$cansendphone = $USER->authorize('sendphone');
			$cansendemail = $USER->authorize('sendemail');
			$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');
			$cansendmultilingual = $USER->authorize('sendmulti');
			$defaultlanguagecode = 'en';

			$result->headers = array();
			$result->headers[] = "Language";
			if($cansendphone)
				$result->headers[] = "Phone";
			if($cansendemail)
				$result->headers[] = "Email";
			if($cansendsms)
				$result->headers[] = "SMS";
			$query = "select l.name as language
						" . ($cansendphone?",sum(type='phone') as Phone":"") . "
						" . ($cansendemail?",sum(type='email') as Email":"") . "
						" . ($cansendsms?",sum(type='sms') as SMS":"") . "
						from message m, language l where m.messagegroupid = ?
						" . ($cansendmultilingual?"":"and m.languagecode = '$defaultlanguagecode'") . "
						and m.languagecode = l.code
						group by language order by language";
			$result->data = QuickQueryMultiRow($query,true,false,array($_GET['id']));
			return $result;
		default:
			error_log("No AJAX API for type=$type");
			return false;
	}
}

header('Content-Type: application/json');
$data = handleRequest();
echo json_encode(!empty($data) ? $data : false);
?>
