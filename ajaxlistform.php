<?
require_once("inc/common.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValSections.val.php");

// @param $listid, assumed to be a valid list id.
function summarizeListName($listid) {
	global $RULE_OPERATORS;
	
	$list = new PeopleList($listid+0);
	$list->modifydate = date("Y-m-d H:i:s");
	
	$summary = array();
	
	$sections = $list->getSections();
	
	if (count($sections) > 0) {
		$skeys = array();
		
		foreach ($sections as $section) {
			$skeys[] = $section->skey;
		}
		
		$summary[] = 'Section is ' . implode(', ', $skeys);
	} else {
		$rules = DBFindMany('Rule', 'FROM rule r, listentry le WHERE le.ruleid=r.id AND le.listid=?', 'r', array($list->id));
		$fieldmaps = FieldMap::getAllAuthorizedFieldMaps();
		foreach ($rules as $rule) {
			if (!$type = Rule::getType($rule->fieldnum))
				continue;
			$op = $RULE_OPERATORS[$type][$rule->op];
			if ($type == 'multisearch')
				$op = 'is';
			if ($rule->logical == 'and not')
				$op = 'is NOT';
			$val = $rule->val;
			if ($type == 'multisearch')
				$val = str_replace('|', ', ', $val);
			else if ($type == 'reldate')
				$val = str_replace(',', ' and ', $val);
			$summary[] = $fieldmaps[$rule->fieldnum]->name . ' ' . $op . ' ' . $val;
		}
		
		$organizations = $list->getOrganizations();
		
		if (count($organizations) > 0) {
			$orgkeys = array();
			
			foreach ($organizations as $organization) {
				$orgkeys[] = $organization->orgkey;
			}
			
			$summary[] = getSystemSetting("organizationfieldname","Organization") . ' is ' . implode(', ', $orgkeys);
		}
	}
	
	if (count($summary) > 0) {
		$list->name = SmartTruncate(implode('; ', $summary),50);
	} else {
		$list->name = _L('Please Add Rules to This List');
	}
	
	$list->update();
}

function handleRequest() {
	if (!isset($_GET['type']))
		return false;
	global $USER;
	global $RULE_OPERATORS;
	
	switch($_GET['type']) {
		
		case 'saveandrename':
			if (!$USER->authorize('createlist') || !isset($_REQUEST['listid']))
				return false;
			
			$listid = $_REQUEST['listid']+0;

			if (!userOwns('list', $listid))
				return false;
			
			$name = substr($_REQUEST['name'],0,50);
			if (QuickQuery('select id from list where deleted=0 and id!=? and name=? and userid=?', false, array($listid, $name, $USER->id)))
				return array('error' => _L('There is already a list with this name'));;
			
			$list = new PeopleList($listid);
			$list->deleted = 0;
			$list->name = substr($_REQUEST['name'],0,50);
			$list->update();
			return true;
			break;
		case 'createlist': // returns $list->id
			if (!$USER->authorize('createlist')) {
				if (isset($_REQUEST['api'])) {
					return Array("status" => "accessDenied", "message" => "Insufficient privileges to create recipient list");
				} else {
					return false;
				}
			}
			
			if (isset($_POST['sectionids'])) {
				if (!is_array($_POST['sectionids']) || count($_POST['sectionids']) <= 0) {
					if (isset($_REQUEST['api'])) {
						return Array("status" => "invalidParameter", "message" => "Section(s) not specified");
					} else {
						return false;
					}
				}
					
				$valsection = new ValSections();
				$valsection->label = _L('Section');
				$errormessage = $valsection->validate($_POST['sectionids']);
				if ($errormessage !== true) {
					if (isset($_REQUEST['api'])) {
						return Array("status" => "invalidSection", "message" => $errormessage);
					} else {
						return array('error' => $errormessage);
					}
				}
			}

			// Accept a default name for this list via request parameter
			if (isset($_REQUEST['name'])) {
				$listName = substr($_REQUEST['name'],0,50);
				$explicitName = true;

				if ($_POST['save']) {
					if (QuickQuery('select id from list where deleted=0 and name=? and userid=?', false, array($listName, $USER->id))) {
						if (isset($_REQUEST['api'])) {
							return Array("status" => "nameNotAvailable", "message" => "Recipient list name " . $listName . " not available");
						} else {
							return array('error' => _L('Recipient list ' . $listName . ' already exists'));
						}
					}
				}
			} else {
				$listName = _L('Please Add Rules to This List');
			}

			// CREATE list
			$list = new PeopleList(null);
			$list->modifydate = date("Y-m-d H:i:s");
			$list->description = ($_POST['description'] ? $_POST['description'] : 'Created in MessageSender');
			$list->userid = $USER->id;
			$list->name = $listName;
			$list->deleted = ($_POST['save'] ? 0 : 1);
			$list->type = isset($_POST['sectionids']) ? 'section' : 'person';
			$list->update();
			if (!$list->id) {
				if (isset($_REQUEST['api'])) {
					return Array("status" => "internalError", "message" => "Recipient list update error");
				} else {
					return false;
				}
			}
				
			if (isset($_POST['sectionids'])) {
				foreach ($_POST['sectionids'] as $sectionid) {
					QuickUpdate('insert into listentry set type="section", listid=?, sectionid=?', false, array($list->id, $sectionid));
				}

				if (!$explicitName) {
					summarizeListName($list->id);
				}
			}
			$_SESSION['listid'] = $list->id;

			if (isset($_REQUEST['api'])) {
				$recipientList = cleanObjects($list);
				$recipientList['id'] = (int)$list->id;

				return Array("status" => "success", "list" => $recipientList);
			} else {
				return $list->id;
			}
			
		case 'addrule':
			if (!$USER->authorize('createlist')) {
				if (isset($_REQUEST['api'])) {
					return Array("status" => "accessDenied", "message" => "Insufficient privileges to update recipient list");
				} else {
					return false;
				}
			}

			if (!isset($_POST['ruledata'])) {
				if (isset($_REQUEST['api'])) {
					return Array("status" => "invalidParameter", "message" => "Rule(s) not specified");
				} else {
					return false;
				}
			}

			if (!isset($_REQUEST['listid'])) {
				if (isset($_REQUEST['api'])) {
					return Array("status" => "invalidParameter", "message" => "Recipient list not specified");
				} else {
					return false;
				}
			}
			
			$listid = $_REQUEST['listid']+0;

			$rules = json_decode($_POST['ruledata']);
			if (empty($rules)) {
				if (isset($_REQUEST['api'])) {
					return Array("status" => "invalidParameter", "message" => "Rule(s) not specified");
				} else {
					return false;
				}
			}

			if (!userOwns('list', $listid)) {
				if (isset($_REQUEST['api'])) {
					return Array("status" => "listNotFound", "message" => "Recipient list $listid not found");
				} else {
					return false;
				}

			}

			if (!is_array($rules)) {
				$rules = array($rules);
			}

			foreach($rules as $data) {
				if (!isset($data->fieldnum, $data->logical, $data->op, $data->val)) {
					if (isset($_REQUEST['api'])) {
						return Array("status" => "invalidRule", "message" => "Invalid rule " . json_encode($data));
					} else {
						return false;
					}
				}

				if ($data->fieldnum == 'organization') {
					QuickUpdate('BEGIN');
					QuickUpdate("DELETE FROM listentry WHERE type='organization' AND listid=?", false, array($listid));

					$validorgkeys = Organization::getAuthorizedOrgKeys();

					foreach ($data->val as $id) {
						$id = $id + 0;

						if (isset($validorgkeys[$id])) {
							$le = new ListEntry();
							$le->listid = $listid;
							$le->type = "organization";
							$le->organizationid = $id;
							$le->create();
						}
					}

					if (!$_POST['nosummary']) {
						summarizeListName($listid);
					}

					QuickUpdate('COMMIT');
				} else {
					if (!$type = Rule::getType($data->fieldnum)) {
						if (isset($_REQUEST['api'])) {
							return Array("status" => "invalidRule", "message" => "No type for rule " . json_encode($data));
						} else {
							return false;
						}
					}

					$data->val = prepareRuleVal($type, $data->op, $data->val);

					if (!$rule = Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
						if (isset($_REQUEST['api'])) {
							return Array("status" => "invalidRule", "message" => "Invalid rule " . json_encode(data));
						} else {
							return false;
						}
					}

					// CREATE rule.
					QuickUpdate('BEGIN');
					$rule->create();
					$le = new ListEntry();
					$le->listid = $listid;
					$le->type = "rule";
					$le->ruleid = $rule->id;
					$le->create();

					if (!$_POST['nosummary']) {
						summarizeListName($listid); //update list name, etc
					}

					QuickUpdate('COMMIT');
				}
			}

			if (isset($_REQUEST['api'])) {
				return Array("status" => "success");
			} else {
				return true;
			}
		
		case 'deleterule':
			if (!$USER->authorize('createlist') || !isset($_POST['fieldnum']) || !isset($_REQUEST['listid']))
				return false;
				
			$listid = $_REQUEST['listid'] + 0;
			$fieldnum = $_POST['fieldnum'];
			
			if ($fieldnum == "" || !userOwns('list', $listid))
				return false;
				
			if ($fieldnum == 'organization') {
				QuickUpdate("DELETE FROM listentry WHERE type='organization' AND listid=?", false, array($listid));
			} else {
				QuickUpdate("DELETE le.*, r.* FROM listentry le, rule r WHERE le.ruleid=r.id AND le.listid=? AND r.fieldnum=?", false, array($listid, $fieldnum));
			}
			
			summarizeListName($listid);
			return true;
			
		default:
			return false;
	}
}

header('Content-Type: application/json');
$data = handleRequest();

if (isset($_REQUEST['api'])) {
	echo json_encode($data);
} else {
	echo json_encode(!empty($data) ? $data : false);
}

?>

