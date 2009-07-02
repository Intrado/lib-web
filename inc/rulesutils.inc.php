<?

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
