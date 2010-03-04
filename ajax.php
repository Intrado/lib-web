<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Language.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Person.obj.php");
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

		// Returns a map of audiofiles belonging to a particular messagegroup. Results are sorted by recorddate.
		case 'AudioFiles':
			$messagegroupid = !empty($_GET['messagegroupid']) ? $_GET['messagegroupid'] + 0 : 0;
			return cleanObjects(DBFindMany('AudioFile', 'from audiofile where userid=? and messagegroupid=? and not deleted order by messagegroupid desc, recorddate desc', false, array($USER->id, $messagegroupid)));

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
			$messagegroup = new MessageGroup($_GET['messagegroupid']);
			return array('summary' => MessageGroup::getSummary($_GET['messagegroupid']),
				'defaultlanguagecode' => $messagegroup->defaultlanguagecode);

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
				
				$organizations = $list->getOrganizations();
				
				if (count($organizations) > 0) {
					$orgkeys = array();
					
					foreach ($organizations as $organization) {
						$orgkeys[$organization->id] = $organization->orgkey;
					}
					
					$listrules[$id]['organization'] = array(
						'fieldnum' => 'organization',
						'val' => $orgkeys
					);
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
				$renderedlist = new RenderedList2();
				$renderedlist->initWithList($list);
				$stats[$list->id]= array(
					'name' => $list->name,
					'advancedlist' => false, //TODO remove this
					'totalremoved' => $list->countRemoved(),
					'totaladded' => $list->countAdded(),
					'totalrule' => -999, //TOOD remove this
					'total' => $renderedlist->getTotal());
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

				// The user may be restricted to specific values.
				$limit = DBFind('Rule', 'from rule r inner join userassociation on r.id = userassociation.ruleid where userassociation.type = "rule" and userid=? and fieldnum=?', 'r', array($USER->id, $fieldnum));
				$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
				
				// Get 'c' field values from the 'section' table, taking into account user section/organization restrictions.
				if ($fieldnum[0] == 'c') {
					// Make sure fieldnum is 'c01' through 'c10', to safeguard against SQL injection.
					$number = substr($fieldnum, 1) + 0;
					if ($number < 1 ||
						$number > 10 ||
						$fieldnum != 'c' . str_pad($number, 2, '0', STR_PAD_LEFT))
						return false;
					
					// If the user is unrestricted, get values from all sections.
					// Otherwise, get the union of values from organization associations and section associations.
					if (QuickQuery('select 1 from userassociation where userid = ? limit 1', false, array($USER->id))) {
						// Values from section associations.
						$values = QuickQueryList("
							select distinct $fieldnum as value
							from section
								inner join userassociation on (userassociation.sectionid = section.id)
							where userid=? $limitsql",
							false, false, array($USER->id)
						);
						
						// Values from organization associations.
						$values += QuickQueryList("
							select distinct $fieldnum as value
							from section
								inner join userassociation on (userassociation.organizationid = section.organizationid)
							where userid=? $limitsql",
							false, false, array($USER->id)
						);
						
						return $values;
					} else { // Unrestricted.
						return QuickQueryList("select distinct $fieldnum as value from section where 1 $limitsql", false);
					}
				} else if ($fieldnum == FieldMap::getLanguageField()) {
					$languagecodes = QuickQueryList("select value from persondatavalues where fieldnum=? $limitsql order by value", false, false, array($_GET['fieldnum']));
					$languagenames = array();
					foreach ($languagecodes as $code) {
						$languagenames[$code] = Language::getName($code);
					}
					return $languagenames;
				} else {
					return QuickQueryList("select value from persondatavalues where fieldnum=? $limitsql order by value", false, false, array($_GET['fieldnum']));
				}
			// if it's an organization field
			} else if ($fieldnum == 'organization') {
				return Organization::getAuthorizedOrgKeys();
			} else { // Unknown fieldnum, return false.
				return false;
			}
			
		case 'getsections':
			if (!isset($_GET['organizationid']))
				return false;
			$organizationid = $_GET['organizationid'] + 0;
			
			// If the user is unrestricted or is associated with this organization, $validsectionids = all sections for this organization.
			// Otherwise if the user is associated to sections, $validsectionids = associated sections that are part of this organization.
			if (QuickQuery('select 1 from userassociation where userid = ? limit 1', false, array($USER->id))) {
				if (QuickQuery('select 1 from userassociation where userid = ? and organizationid = ? and type = "organization" limit 1', false, array($USER->id, $organizationid))) {
					return QuickQueryList('select id, skey from section where organizationid = ?', true, false, array($organizationid));
				} else {
					return QuickQueryList('
						select s.id, s.skey
						from userassociation ua
							inner join section s on (ua.sectionid = s.id)
						where ua.userid = ? and ua.type = "section" and ua.sectionid != 0 and s.organizationid = ?',
						true, false, array($USER->id, $organizationid)
					);
				}
			} else { // Unrestricted.
				return QuickQueryList('select id, skey from section where organizationid = ?', true, false, array($organizationid));
			}

		case 'rulewidgetsettings':
			return array(
				'operators' => $RULE_OPERATORS,
				'reldateOptions' => $RELDATE_OPTIONS,
				'fieldmaps' => cleanObjects(FieldMap::getAllAuthorizedFieldMaps()),
				'hasorg' => QuickQuery('select 1 from organization where not deleted limit 1') == 1,
				'languagefield' => FieldMap::getLanguageField(),
				'languagemap' => Language::getLanguageMap()
			);

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
			if (!isset($_GET['id']) && !userOwns('messagegroup',$_GET['id']))
				return false;

			$cansendphone = $USER->authorize('sendphone');
			$cansendemail = $USER->authorize('sendemail');
			$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');
			$cansendmultilingual = $USER->authorize('sendmulti');
			$deflanguagecode = 'en';//Language::getDefaultLanguageCode();

			$result->headers = array();
			$result->headers['language'] = "&nbsp;";

			$query = "select l.code,l.name, m.id, m.type, m.subtype from language l
						inner join message m on (l.code = m.languagecode and m.messagegroupid = ?)";
			$rows = QuickQueryMultiRow($query,true,false,array($_GET['id']));

			$hasvoicephone = false;
			$hashtmlemail = false;
			$hasplainemail = false;
			$hasplainsms = false;

			if($rows) {
				foreach($rows as $row) {
					$result->data[$row['code']][$row['subtype'] . $row['type']] = $row['id'];
					$result->data[$row['code']]['languagename'] = $row['name'];
					switch($row['subtype'] . $row['type']) {
						case 'voicephone':
							$hasvoicephone = true;
							break;
						case 'htmlemail':
							$hashtmlemail = true;
							break;
						case 'plainemail':
							$hasplainemail = true;
							break;
						case 'plainsms':
							$hasplainsms = true;
							break;
					}
				}
			}
			if($hasvoicephone)
				$result->headers['voicephone'] = "Phone";
			if($hashtmlemail)
				$result->headers['htmlemail'] = "Email (HTML)";
			if($hasplainemail)
				$result->headers['plainemail'] = "Email (Plain)";
			if($hasplainsms)
				$result->headers['plainsms'] = "SMS";
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
