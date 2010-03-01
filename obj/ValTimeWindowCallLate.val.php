<?

class ValTimeWindowCallLate extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		if ((strtotime($value) - 3600) < strtotime($requiredvalues['callearly']))
			return $this->label. " ". _L('There must be a minimum of one hour between start and end time');
		if(isset($requiredvalues['date'])) {
			$now = strtotime("now");
			if ((date('m/d/Y', $now) == $requiredvalues['date']) && (strtotime($value) -1800 < $now))
				return $this->label. " ". _L("There must be a minimum of one-half hour between now and end time to submit with today's date");
		}
		return true;
	}
}

?>
