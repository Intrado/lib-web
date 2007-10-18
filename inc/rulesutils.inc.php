<?

// reads form data for new rule
// if one exists, return the rule object
function getRuleFromForm($f, $s){
	
	$rule = null;
	$fieldnum = GetFormData($f,$s,"newrulefieldnum");
	
	if ($fieldnum != "" && $fieldnum != -1) {
		$type = GetFormData($f,$s,"newruletype");

		if ($type == "text")
			$logic = "and";
		else
			$logic = GetFormData($f,$s,"newrulelogical_$type");

		if ($type == "multisearch")
			$op = "in";
		else
			$op = GetFormData($f,$s,"newruleoperator_$type");

		$value = GetFormData($f,$s,"newrulevalue_" . $fieldnum);
		if (count($value) > 0) {
			$rule = new Rule();
			$rule->logical = $logic;
			$rule->op = $op;
			$rule->val = ($type == 'multisearch' && is_array($value)) ? implode("|",$value) : $value;
			$rule->fieldnum = $fieldnum;
		}
	}
	return $rule;
}


// putformdata for ruleeditform
function putRuleFormData($f, $s){
	PutFormData($f,$s,"newrulefieldnum","");
	PutFormData($f,$s,"newruletype","text","text",1,50);
	PutFormData($f,$s,"newrulelogical_text","and","text",1,50);
	PutFormData($f,$s,"newrulelogical_multisearch","and","text",1,50);
	PutFormData($f,$s,"newruleoperator_text","sw","text",1,50);
	PutFormData($f,$s,"newruleoperator_multisearch","in","text",1,50);
}

// Takes an array of rules and formats them for display in an array
function displayRules($rules){
	$rulesdisplay = array();
	foreach($rules as $rule){
		$fieldname = QuickQuery("select name from fieldmap where fieldnum = '$rule->fieldnum'");
		$rulesdisplay[] = htmlentities($fieldname . ": " . preg_replace("{\|}", ", ", $rule->val));
	}
	return $rulesdisplay;
}
?>