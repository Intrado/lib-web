<?

class ValTimeWindowCallEarly extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		global $ACCESS;
		
		$accessCalllate = $ACCESS->getValue("calllate");
		if (!$accessCalllate)
			$accessCalllate = "11:59 pm";
			
		//give an exception for late calls, don't restrict to min hour window
		$isLateCall = strtotime($requiredvalues['calllate']) >= strtotime($accessCalllate); 
		$isBeforeEndTime = strtotime($value) < strtotime($requiredvalues['calllate']);
		$isAtLeastAnHourBeforeEndTime = strtotime($value) <= strtotime($requiredvalues['calllate']) - 3600;
		
		if (!$isBeforeEndTime)
			return _L('%1$s must be before the end time.', $this->label);
	
		if (!$isLateCall && !$isAtLeastAnHourBeforeEndTime)
			return _L('%1$s must be at least an hour before the end time', $this->label);

		return true;
	}
}

?>
