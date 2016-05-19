<?

class ValTimeWindowCallEarly extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		global $ACCESS;
		
		$calllatefield = 'calllate';
		if (isset($args['calllatefield']))
			$calllatefield= $args['calllatefield'];
		
		$accessCalllate = (!isset($args['noProfile']) || $args['noProfile'] !== true)?$ACCESS->getValue("calllate"):false;		
		if (!$accessCalllate)
			$accessCalllate = "11:59 pm";
		
		$calllate = strtotime($requiredvalues[$calllatefield]);
		$value = strtotime($value);
		$isBothSet = $value != -1 && $value !== false && $calllate != -1 && $calllate !== false;
		if (!$isBothSet)
			return true;
		
		//give an exception for late calls, don't restrict to min hour window
		$isLateCall = $calllate >= strtotime($accessCalllate); 
		$isBeforeEndTime = $value < $calllate;
		$isAtLeastAnHourBeforeEndTime = $value <= $calllate - 3600;
		
		if (!$isBeforeEndTime)
			return _L('\'%1$s\' must be before the end time.', $this->label);
	
		if (!$isLateCall && !$isAtLeastAnHourBeforeEndTime)
			return _L('\'%1$s\' must be at least an hour before the end time', $this->label);
		
		return true;
	}
}

?>
