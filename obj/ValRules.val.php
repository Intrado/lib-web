<?
// @param optional $args["allowedFieldTypes"], example: array('f','g','c')
class ValRules extends Validator {
	var $onlyserverside = true;
	
	function validate ($valueJSON, $args) {
		$msgRuleAlreadyExists = addslashes(_L("Rule already exists"));

		$ruledata = json_decode($valueJSON);
		
		if ((!isset($ruledata->fieldnum) && !is_array($ruledata)) || empty($ruledata)) // Do not complain if no rules are specified
			return true;
		
		if (is_array($ruledata)) {
			foreach ($ruledata as $data) {
				if (isset($data->fieldnum) && isset($rulesfor[$data->fieldnum])) { // Do not allow more than one rule per fieldnum
					return $msgRuleAlreadyExists;
				} else {
					$validation = $this->validateOneRule($data, $args);
					if ($validation !== true)
						return $validation;
				}
			}
		} else {
			return $this->validateOneRule($ruledata, $args);
		}
		
		return true;
	}
	
	function validateOneRule($data, $args) {
		global $USER;
		
		// get the allowed field types if they are set
		$allowedFieldTypes = isset($args['allowedFieldTypes'])?$args['allowedFieldTypes']:array();
		
		// get the current field's type. F, G and C fields just use the first character of the fieldnum
		$fieldtype = ($data->fieldnum == 'organization')?'organization':$data->fieldnum[0];
		
		$msgIncompleteRule = addslashes(_L("Incomplete rule data"));
		$msgUnauthorizedFieldmap = addslashes(_L("Unauthorized fieldmap"));
		$msgUnauthorizedOrganization = addslashes(_L("Unauthorized organization"));
		
		// if any of the rule data isn't set
		if (!isset($data->fieldnum, $data->logical, $data->op, $data->val)) {
			return $msgIncompleteRule;
		// if the field is disallowed due to allowed field types specified and it not being present
		} else if ($allowedFieldTypes && !in_array($fieldtype, $allowedFieldTypes)) {
			return $msgUnauthorizedFieldmap;
		// if the requested field is an organization, be sure the user has access to the specified organizations
		} else if ($data->fieldnum == 'organization') {
		
			$validorgkeys = Organization::getAuthorizedOrgKeys();
			
			foreach ($data->val as $id) {
				if (!isset($validorgkeys[$id]))
					return $msgUnauthorizedOrganization;
			}
		// finally, for any non organization fields, check that it is a valid rule by initing it
		} else if (!Rule::initFrom($data->fieldnum, $data->logical, $data->op, $data->val)) {
			return $msgUnauthorizedFieldmap;
		}
		
		return true;
	}
}
?>