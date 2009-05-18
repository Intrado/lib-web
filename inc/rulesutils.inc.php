<?

// reads form data for new rule
// if one exists, return the rule object
function getRuleFromForm($f, $s){
	
	$rule = null;
	$fieldnum = GetFormData($f,$s,"newrulefieldnum");
	
	if ($fieldnum != "" && $fieldnum != -1) {
		$type = GetFormData($f,$s,"newruletype");
		$logic = GetFormData($f,$s,"newrulelogical_$type");
		$op = GetFormData($f,$s,"newruleoperator_$type");
		$value = GetFormData($f,$s,"newrulevalue_" . $fieldnum);
		if(!is_array($value)) {
			$value = trim($value);
		}
		$value2 = TrimFormData($f,$s,"newrulevalue2_" . $fieldnum);
		$value3 = TrimFormData($f,$s,"newrulevalue3_" . $fieldnum);
		$value4 = TrimFormData($f,$s,"newrulevalue4_" . $fieldnum);
		$value5 = TrimFormData($f,$s,"newrulevalue5_" . $fieldnum);

		if (count($value) > 0)
			$rule = Rule::initFrom($fieldnum, $type, $logic, $op, array($value, $value2, $value3, $value4, $value5));
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
	PutFormData($f,$s,"newruleoperator_numeric","num_eq","text",1,50);
	PutFormData($f,$s,"newrulelogical_numeric","and","text",1,50);
}

// Takes an array of rules and formats them for display in an array
function displayRules($rules){
	$rulesdisplay = array();
	foreach($rules as $rule){
		$fieldname = QuickQuery("select name from fieldmap where fieldnum = '$rule->fieldnum'");
		$rulesdisplay[] = escapehtml($fieldname . ": " . preg_replace("{\|}", ", ", $rule->val));
	}
	return $rulesdisplay;
}
?>