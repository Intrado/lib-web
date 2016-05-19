<?

class ValTimeWindowCallLate extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		global $ACCESS;
		
		$callearlyfield = 'callearly';
		if (isset($args['callearlyfield']))
			$callearlyfield= $args['callearlyfield'];
		
		$accessCalllate = (!isset($args['noProfile']) || $args['noProfile'] !== true)?$ACCESS->getValue("calllate"):false;
		if (!$accessCalllate)
			$accessCalllate = "11:59 pm";
		
		$callearly = strtotime($requiredvalues[$callearlyfield]);
		$value = strtotime($value);
		$isBothSet = $value != -1 && $value !== false && $callearly != -1 && $callearly !== false;
		if (!$isBothSet)
			return true;
		
		//give an exception for late calls, don't restrict to min hour window
		$isLateCall = $value >= strtotime($accessCalllate); 
		$isAfterStartTime = $value > $callearly;
		$isAtLeastAnHourAfterStartTime = $value >= $callearly + 3600;
		
		if (!$isAfterStartTime)
			return _L('\'%1$s\' must be after the start time.', $this->label);
		
		if (!$isLateCall && !$isAtLeastAnHourAfterStartTime)
			return _L('\'%1$s\' must be at least an hour after the start time', $this->label);
		
		return true;
	}
}

?>
