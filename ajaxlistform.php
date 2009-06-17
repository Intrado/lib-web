<?php
require_once("inc/common.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");

function handleRequest() {
	if (!isset($_GET['type']))
		return false;
	global $USER;
	
	switch($_GET['type']) {
		case 'saverules': // returns $list->id
			if (!$USER->authorize('createlist') || !isset($_POST['ruledata']))
				return false;
			$ruledata = json_decode($_POST['ruledata']);
			if (!is_array($ruledata))
				return false;
				
			$summary = array();
			$rules = array();
			foreach ($ruledata as $data) {
				if (!isset($data->fieldnum, $data->type, $data->logical, $data->op, $data->val))
					return false;
				if (isset($rules[$data->fieldnum])) // Do not allow more than one rule per fieldnum
					return false;
				if (!$rule = Rule::initFrom($data->fieldnum, $data->type, $data->logical, $data->op, $data->val))
					return false;
				$fieldmaps = FieldMap::getAllAuthorizedFieldMaps();
				if (empty($fieldmaps))
					return false;
				$rules[$data->fieldnum] = $rule;
				$summary[] = $fieldmaps[$data->fieldnum]->name;
			}
			$summary = implode(', ', $summary);
			
			// CREATE list
			$list = new PeopleList(null);
			$list->name = $summary;
			$list->description = 'JobWizard List ' . date('Y M d, H:i:s', time());
			$list->userid = $USER->id;
			$list->deleted = 1;
			$list->update();
			if (!$list->id)
				return false;
				
			// CREATE rules.
			QuickUpdate('BEGIN');
			foreach ($rules as $rule) {
				$rule->create();
				
				$le = new ListEntry();
				$le->listid = $list->id;
				$le->type = "R";
				$le->ruleid = $rule->id;
				$le->create();
			}
			QuickUpdate('COMMIT');
			return $list->id;
			
		default:
			return false;
	}
}

header('Content-Type: application/json');
$data = handleRequest();
echo json_encode(!empty($data) ? $data : false);
?>
