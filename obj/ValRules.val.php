<?
// @param optional $args["allowedFieldTypes"], example: array('f','g','c')
class ValRules extends Validator {
	var $onlyserverside = true;
	
	function validate ($valueJSON, $args) {
		$msgPleaseFinish = addslashes(_L("Please finish adding your rule"));
		$msgRuleAlreadyExists = addslashes(_L("Rule already exists"));
		
		if ($valueJSON == 'pending')
			return $msgPleaseFinish;

		$ruledata = json_decode($valueJSON);
		
		if ((!isset($ruledata->fieldnum) && !is_array($ruledata)) || empty($ruledata)) // Do not complain if no rules are specified
			return true;
		
		if (is_array($ruledata)) {
			$rulesfor = array();
			foreach ($ruledata as $data) {
				if (isset($data->fieldnum) && isset($rulesfor[$data->fieldnum])) { // Do not allow more than one rule per fieldnum
					return $msgRuleAlreadyExists;
				} else {
					$error = $this->validateOneRule($data, $args);
					if ($error !== true)
						return $error;
				}
				
				$rulesfor[$data->fieldnum] = true;
			}
		} else {
			return $this->validateOneRule($ruledata, $args);
		}
		
		return true;
	}
	
	function validateOneRule($data, $args) {
		global $USER;
		
		$msgIncompleteRule = addslashes(_L("Incomplete rule data"));
		$msgUnauthorizedFieldmap = addslashes(_L("Unauthorized fieldmap"));
		$msgUnauthorizedOrganization = addslashes(_L("Unauthorized organization"));
		
		if (!isset($data->fieldnum, $data->logical, $data->op, $data->val)) {
			return $msgIncompleteRule;
		} else if ($data->fieldnum == 'organization') {
		
			$validorgkeys = Organization::getAuthorizedOrgKeys();
			
			foreach ($data->val as $id) {
				if (!ctype_digit($id) || !isset($validorgkeys[$id]))
					return $msgUnauthorizedOrganization;
			}
		} else if (isset($args['allowedFieldTypes']) && !in_array($data->fieldnum[0], $args['allowedFieldTypes'])) {
			return $msgUnauthorizedFieldmap;
		} else if (!Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
			return $msgUnauthorizedFieldmap;
		}
		
		return true;
	}
}
?>