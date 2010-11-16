<?

class ValTimeWindowCallLate extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		global $ACCESS;
		
		$accessCalllate = $ACCESS->getValue("calllate");
		if (!$accessCalllate)
			$accessCalllate = "11:59 pm";
		
		//give an exception for late calls, don't restrict to min hour window
		$isLateCall = strtotime($value) >= strtotime($accessCalllate); 
		$isAfterStartTime = strtotime($value) > strtotime($requiredvalues['callearly']);
		$isAtLeastAnHourAfterStartTime = strtotime($value) >= strtotime($requiredvalues['callearly']) + 3600;
		
		if (!$isAfterStartTime)
			return _L('%1$s must be after the end time.', $this->label);
		
		if (!$isLateCall && !$isAtLeastAnHourAfterStartTime)
			return _L('%1$s must be at least an hour after the start time', $this->label);
		
		return true;
	}
}

?>
