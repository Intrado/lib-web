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
require_once("obj/Job.obj.php");

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
			return cleanObjects(DBFindMany('PeopleList',
					", (l.name+0) as lettersfirst
					from list l
						left join publish p on
							(l.id = p.listid and p.type = 'list' and p.action = 'subscribe')
					where (l.userid=? or p.userid=?)
						and l.type != 'alert'
						and not l.deleted
					order by lettersfirst,l.name", "l", array($USER->id,$USER->id)));

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
			$sqlargs = array($userid, $_GET['messagetype']);
			$extrasql = '';
			if(isset($_GET['languagecode'])) {
				$extrasql = "and m.languagecode = ?";
				$sqlargs[] = $_GET['languagecode'];
			}
			
			$query = "select m.id,mg.name from message m inner join messagegroup mg on (m.messagegroupid = mg.id) where mg.deleted = 0 and m.autotranslate not in ('source','translated') and mg.userid=? and m.type=? $extrasql order by id";
			return QuickQueryList($query, true, false,$sqlargs);

		//--------------------------- RPC -------------------------------
		case 'messagegroupsummary':
			// Check if has messagegroupid and that user either owns or subscribs to the message
			if (!isset($_GET['messagegroupid']))
				return false;
			if(!userOwns('messagegroup',$_GET['messagegroupid']) && !isSubscribed("messagegroup",$_GET['messagegroupid']))
				return false;
			$messagegroup = new MessageGroup($_GET['messagegroupid']);
			$summary = MessageGroup::getSummary($_GET['messagegroupid']);
			// parse the header data for each message.
			foreach ($summary as $i => $messagesummary) {
				$summary[$i]["data"] = (object)sane_parsestr($messagesummary["data"]);
			}
			return array('summary' => $summary,
				'defaultlanguagecode' => $messagegroup->defaultlanguagecode
			);

		case 'hasmessage':
			if (!isset($_GET['messagetype']) && !isset($_GET['messageid']))
				return false;
			if (isset($_GET['messagetype']))
				return QuickQuery("select count(id) from message where userid=? and not deleted and type=?", false, array($USER->id, $_GET['messagetype']))?true:false;
			if (isset($_GET['messageid']))
				return QuickQuery("select count(id) from message where userid=? and not deleted and id=?", false, array($USER->id, $_GET['messageid']))?true:false;

		// Returns an array of id,name,messagegroupid for audiofiles either belonging to this messagegroup or referenced by messages in this messagegroup.
		case 'getaudiolibrary':
			if (!isset($_GET['messagegroupid']) || !$_GET['messagegroupid'])
				return false;
			
			$messagegroupid = $_GET['messagegroupid'] + 0;
			
			if (!userOwns("messagegroup", $messagegroupid))
				return false;

			$audiofileids = MessageGroup::getReferencedAudioFileIDs($messagegroupid);
			if (count($audiofileids) > 0)
				return QuickQueryMultiRow('select id, name, messagegroupid from audiofile where not deleted and id in ('.implode(',', $audiofileids).') order by recorddate desc', true, false);
			else
				return false;

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
				if (!userOwns('list', $id) && !isSubscribed('list', $id))
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
				if (!userOwns('list', $id) && !isSubscribed("list", $id))
					continue;
				$list = new PeopleList($id+0);
				$renderedlist = new RenderedList2();
				$renderedlist->pagelimit = 0;
				$renderedlist->initWithList($list);
				$stats[$list->id]= array(
					'name' => $list->name,
					'advancedlist' => false, //TODO remove this
					'totalremoved' => $list->countRemoved(),
					'totaladded' => $list->countAdded(),
					'totalrule' => -999, //TOOD remove this
					'total' => $renderedlist->getTotal() + 0);
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
	
				// Get 'c' field values from the 'section' table, taking into account user section/organization restrictions.
				if ($fieldnum[0] == 'c') {
					$limitsql = $limit ? $limit->toSQL(false, false, false, true) : '';
					// Make sure fieldnum is 'c01' through 'c10', to safeguard against SQL injection.
					$number = substr($fieldnum, 1) + 0;
					if ($number < 1 ||
						$number > 10 ||
						$fieldnum != 'c' . str_pad($number, 2, '0', STR_PAD_LEFT))
						return false;
					
					// If the user is unrestricted, get values from all sections.
					// Otherwise, get the union of values from organization associations and section associations.
					if (QuickQuery('select 1 from userassociation where userid = ? and type in ("organization", "section") limit 1', false, array($USER->id))) {
						// Values from section and org associations.
						$query = "(select distinct $fieldnum as value
							from section
								inner join userassociation on (userassociation.sectionid = section.id)
							where userid=?
								$limitsql)
							union
							(select distinct $fieldnum as value
							from section
								inner join userassociation on (userassociation.organizationid = section.organizationid)
							where userid=?
								$limitsql)
							order by value";
						$values = QuickQueryList($query, false, false, array($USER->id, $USER->id));
						
						return $values;
					} else { // Unrestricted.
						return QuickQueryList("select distinct $fieldnum as value from section where 1 $limitsql order by value", false);
					}
				} else if ($fieldnum == FieldMap::getLanguageField()) {
					$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
					$languagecodes = QuickQueryList("select value from persondatavalues where fieldnum=? $limitsql order by value", false, false, array($_GET['fieldnum']));
					$languagenames = array();
					foreach ($languagecodes as $code) {
						$languagenames[$code] = Language::getName($code);
					}
					return $languagenames;
				} else {
					$limitsql = $limit ? $limit->toSQL(false, 'value', false, true) : '';
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
			
			// if the user has an association with this organization directly
			$userassociatedorg = QuickQuery("select 1 from userassociation where userid = ? and type = 'organization' and organizationid = ? limit 1", false, array($USER->id, $organizationid));
			
			// if user has no org or section associations
			$userhasassociations = QuickQuery("select 1 from userassociation where userid = ? and type in ('organization', 'section') limit 1", false, array($USER->id));
			
			if ($userassociatedorg || !$userhasassociations) {
				$sections = QuickQueryList("select id, skey from section where organizationid = ?", true, false, array($organizationid));
			} else {
				// get user associations with sections for this organization
				$sections = QuickQueryList(
						"select s.id, s.skey
						from section s
							inner join userassociation ua on
							(s.id = ua.sectionid)
						where s.organizationid = ?
							and ua.type = 'section'
							and ua.userid = ?
							order by s.skey", true, false, array($organizationid, $USER->id));
			}
			
			// if there are no sections to return, return false
			return ($sections?$sections:false);
			

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
			$attachments = DBFindMany("MessageAttachment","from messageattachment where messageid=?", false, array($_GET['id']));
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
			// Check if has messagegroupid
			if (!isset($_GET['id']) || !$_GET['id'])
				return false;
			
			if (isset($_GET['jobid'])) {
				// Check if job is assosiated with message and user can see job
				$job = new Job($_GET['jobid']);
				if ($job->messagegroupid != $_GET['id'] || !userCanSee("job", $_GET['jobid']))
					return false;
			} else {
				// check if the user can view the message group
				if (!userCanSee("messagegroup", $_GET['id']))
					return false;
			}
			
			$messagegroup = new MessageGroup($_GET['id']);
			
			$result->defaultlang = Language::getName(Language::getDefaultLanguageCode());
			$result->headers = array();
			
			if ($messagegroup->hasMessage("phone") || $USER->authorize('sendphone')) {
				$result->headers['phonevoice'] = _L("Phone");
			}
			
			// Show SMS column to users even if they are not authenicated if the messagegroup contains SMS.
			if (getSystemSetting('_hassms', false) && ($messagegroup->hasMessage("sms") || $USER->authorize('sendsms'))) {
				$result->headers['smsplain'] = _L("SMS");
			}
			
			if ($messagegroup->hasMessage("email") || $USER->authorize('sendemail')) {
				$result->headers['emailhtml'] = _L("HTML Email");
				$result->headers['emailplain'] = _L("Plain Email");
			}
			
			// facebook?
			// Show Facebook column to users even if they are not authenicated if the messagegroup contains Facebook.
			$showfacebook = getSystemSetting('_hasfacebook', false) && ($messagegroup->hasMessage("post","facebook") || $USER->authorize('facebookpost'));
			if ($showfacebook) {
				$result->headers["postfacebook"] = _L("Facebook");
			}
			// twitter?
			// Show Twitter column to users even if they are not authenicated if the messagegroup contains Twitter.
			$showtwitter = getSystemSetting('_hastwitter', false) && ($messagegroup->hasMessage("post","facebook") || $USER->authorize('twitterpost'));
			if ($showtwitter) {
				$result->headers["posttwitter"] = _L("Twitter");
			}
			
			// only add post type if facebook or twitter is available
			if ($showfacebook || $showtwitter) {
				$result->headers['postpage'] = _L("Page");
				$result->headers['postvoice'] = _L("Page Media");
			}
			
			
			// Since it is possible to subscribe to messages we cannot restrict based on $USER->authorize('sendmulti') here
			// instead check if each language contains a message 
			$customerlanguages = Language::getLanguageMap();
			unset($customerlanguages["en"]);
			$customerlanguages = array_merge(array("en" => "English"),$customerlanguages);
			foreach ($customerlanguages as $languagecode => $languagename) {
				if ($messagegroup->hasMessage("phone") || $USER->authorize('sendphone')) {
					$message = $messagegroup->getMessage('phone', 'voice', $languagecode);
					
					// Only show languages if allowed or it contains content
					if ($USER->authorize('sendmulti') || $message)
						$result->data[$languagename]['phonevoice'] = $message?$message->id:false;
				}
				if ($messagegroup->hasMessage("email") || $USER->authorize('sendemail')) {
					$message = $messagegroup->getMessage('email', 'html', $languagecode);
					
					// Only show languages if allowed or it contains content
					if ($USER->authorize('sendmulti') || $message)
						$result->data[$languagename]['emailhtml'] = $message?$message->id:false;
						
					$message = $messagegroup->getMessage('email', 'plain', $languagecode);
					
					// Only show languages if allowed or it contains content
					if ($USER->authorize('sendmulti') || $message)
						$result->data[$languagename]['emailplain'] = $message?$message->id:false;
				}
			}
			if (getSystemSetting('_hassms', false) && ($messagegroup->hasMessage("sms") || $USER->authorize('sendsms'))) {
				$message = $messagegroup->getMessage('sms', 'plain', 'en');
				$result->data['English']['smsplain'] = $message?$message->id:false;
			}
			if ($showfacebook) {
				$message = $messagegroup->getMessage('post', 'facebook', 'en');
				$result->data["English"]['postfacebook'] = $message?$message->id:false;
			}
			if ($showtwitter) {
				$message = $messagegroup->getMessage('post', 'twitter', 'en');
				$result->data["English"]['posttwitter'] = $message?$message->id:false;
			}
			if ($showfacebook || $showtwitter) {
				$message = $messagegroup->getMessage('post', 'page', 'en');
				$result->data["English"]['postpage'] = $message?$message->id:false;
				$message = $messagegroup->getMessage('post', 'voice', 'en');
				$result->data["English"]['postvoice'] = $message?$message->id:false;
			}
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
