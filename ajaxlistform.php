<?php
require_once("inc/common.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");

// @param $listid, assumed to be a valid list id.
function summarizeListName($listid) {
	global $RULE_OPERATORS;
	$list = new PeopleList($listid+0);
	$rules = DBFindMany('Rule', 'FROM rule r, listentry le WHERE le.ruleid=r.id AND le.listid=?', 'r', array($list->id));
	$fieldmaps = FieldMap::getAllAuthorizedFieldMaps();
	$summary = array();
	foreach ($rules as $rule) {
		$type = 'multisearch';
		if ($fieldmaps[$rule->fieldnum]->isOptionEnabled('text'))
			$type = 'text';
		else if ($fieldmaps[$rule->fieldnum]->isOptionEnabled('reldate'))
			$type = 'reldate';
		else if ($fieldmaps[$rule->fieldnum]->isOptionEnabled('numeric'))
			$type = 'numeric';
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
	$list->name = SmartTruncate(implode('; ', $summary),50);
	if (empty($rules))
		$list->name = _L('Please Add Rules to This List');
	$list->update();
}

function handleRequest() {
	if (!isset($_GET['type']))
		return false;
	global $USER;
	global $RULE_OPERATORS;
	
	switch($_GET['type']) {
		case 'createlist': // returns $list->id
			if (!$USER->authorize('createlist'))
				return false;
			// CREATE list
			$list = new PeopleList(null);
			$list->description = 'JobWizard List ' . date('Y M d, H:i:s', time());
			$list->userid = $USER->id;
			$list->name = _L('Please Add Rules to This List');
			$list->deleted = 1;
			$list->update();
			if (!$list->id)
				return false;
			return $list->id;
			
		case 'addrule':
			if (!$USER->authorize('createlist') || !isset($_POST['ruledata']) || !isset($_GET['listid']))
				return false;
			$data = json_decode($_POST['ruledata']);
			if (empty($data) || !userOwns('list', $_GET['listid']))
				return false;
			if (!isset($data->fieldnum, $data->type, $data->logical, $data->op, $data->val))
				return false;
			if (!$rule = Rule::initFrom($data->fieldnum, $data->type, $data->logical, $data->op, $data->val))
				return false;
			
			// CREATE rule.
			QuickUpdate('BEGIN');
				$rule->create();
				$le = new ListEntry();
				$le->listid = $_GET['listid']+0;
				$le->type = "R";
				$le->ruleid = $rule->id;
				$le->create();
			QuickUpdate('COMMIT');
			summarizeListName($_GET['listid']);
			return $rule->id;
		
		case 'deleterule':
			if (!$USER->authorize('createlist') || !isset($_POST['fieldnum']) || !isset($_GET['listid']))
				return false;
			if (empty($_POST['fieldnum']) || !userOwns('list', $_GET['listid']))
				return false;
			QuickUpdate("DELETE le.*, r.* FROM listentry le, rule r WHERE le.ruleid=r.id AND le.listid=? AND r.fieldnum=?", false, array($_GET['listid'], $_POST['fieldnum']));
			summarizeListName($_GET['listid']);
			return true;
			
		default:
			return false;
	}
}

header('Content-Type: application/json');
$data = handleRequest();
echo json_encode(!empty($data) ? $data : false);
?>

