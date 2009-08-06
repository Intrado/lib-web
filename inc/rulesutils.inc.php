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

		// TODO: Why is this "if" check necessary?
		if (count($value) > 0) {
			$val = $value;
			if ($op == "num_range")
				$val = array($value, $value2);
			if ($type == "reldate" && $op != "reldate") {
				if ($op == "eq")
					$val = $value2;
				if ($op == "date_range")
					$val = array($value2, $value3);
				if ($op == "date_offset")
					$val = $value4;
				if ($op == "reldate_range")
					$val = array($value4,$value5);
			}
			$rule = Rule::initFrom($fieldnum, $logic, $op, prepareRuleVal($type, $op, $val));
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

function prepareRuleVal($type, $op, $values) {
	if ($op == "num_range" && is_array($values) && count($values) == 2) { //if its a range, we need to get the other value too
		return (ereg_replace("[^0-9\.-]*","",$values[0]) + 0.0) . "|" . (ereg_replace("[^0-9\.-]*","",$values[1]) + 0.0);
	} else if ($op == "date_range" && is_array($values) && count($values) == 2) { //if its a range, we need to get the other value too
		$t1 = strtotime($values[0] == "" ? "today" : $values[0]);
		$t2 = strtotime($values[1] == "" ? "today" : $values[1]);
		if ($t1 > $t2) { //ensure between order
			$tmp = $t1;
			$t1 = $t2;
			$t2 = $tmp;
		}
		return date('m/d/Y', $t1) . "|" . date('m/d/Y', $t2);
	} else if ($type == "reldate" && $op == "eq" && !is_array($values)) {
		return date('m/d/Y',strtotime($values == "" ? "today" : $values));
	} else if ($type == "reldate" && $op == "date_offset" && !is_array($values)) {
		return (int)ereg_replace("[^0-9\.-]*","",$values);
	} else if ($type == "reldate" && $op == "reldate_range" && is_array($values) && count($values) == 2) {
		$values[0] = (int)ereg_replace("[^0-9\.-]*","",$values[0]);
		$values[1] = (int)ereg_replace("[^0-9\.-]*","",$values[1]);
		if ($values[0] > $values[1]) { //ensure between order
			$tmp = $values[0];
			$values[0] = $values[1];
			$values[1] = $tmp;
		}
		return implode("|",$values);
	} else if (strpos($op,"num_") === 0 && !is_array($values)) {
		$numbers = array($values);
		if ($op == "num_range")
			$numbers = explode('|', $values);
		foreach ($numbers as $i => $num)
			$numbers[$i] = ereg_replace("[^0-9\.-]*","",$num) + 0.0;
		return implode('|', $numbers);
	} else if ($type == 'multisearch' && is_array($values)) {
		return implode("|",$values);
	} else {
		return $values;
	}
}

?>
