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

		if (count($value) > 0) {
			$rule = new Rule();
			$rule->logical = $logic;
			$rule->op = $op;
			
			if ($op == "num_range") //if its a range, we need to get the other value too
				$rule->val = (ereg_replace("[^0-9\.-]*","",$value) + 0.0) . "|" . (ereg_replace("[^0-9\.-]*","",$value2) + 0.0);
			else if ($op == "date_range") { //if its a range, we need to get the other value too
				$t1 = strtotime($value2 == "" ? "today" : $value2);
				$t2 = strtotime($value3 == "" ? "today" : $value3);
				if ($t1 > $t2) { //ensure between order
					$tmp = $t1;
					$t1 = $t2;
					$t2 = $tmp;
				}
				$rule->val = date('m/d/Y', $t1) . "|" . date('m/d/Y', $t2);
			} else if ($type == "reldate" && $op == "eq")
				$rule->val = date('m/d/Y',strtotime($value2 == "" ? "today" : $value2));
			else if ($type == "reldate" && $op == "date_offset")
				$rule->val = (int)ereg_replace("[^0-9\.-]*","",$value4);
			else if ($type == "reldate" && $op == "reldate_range") {
				$value4 = (int)ereg_replace("[^0-9\.-]*","",$value4);
				$value5 = (int)ereg_replace("[^0-9\.-]*","",$value5);
				if ($value4 > $value5) { //ensure between order
					$tmp = $value4;
					$value4 = $value5;
					$value5 = $tmp;
				}
				$rule->val = "$value4|$value5";
			} else if (strpos($op,"num_") === 0)
				$rule->val = ereg_replace("[^0-9\.-]*","",$value) + 0.0;
			else if ($type == 'multisearch' && is_array($value))
				$rule->val = implode("|",$value);
			else
				$rule->val = $value;
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