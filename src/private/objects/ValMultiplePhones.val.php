<?

class ValMultiplePhones extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$numbers = explode("\n",$value);
		if (!is_array($numbers)) {
			return "invalid format. Please insert a comma seperated list of phone numbers";
		}

		$parsednumbers = array();
		foreach ($numbers as $number) {
			if ($err = Phone::validate($number)) {
				$errmsg = "$this->label contains the invalid phone number: $number. ";
				foreach ($err as $e) {
					$errmsg .= $e . " ";
				}
				return $errmsg;
			} else {
				$parsednumbers[] = Phone::parse($number);
			}
		}
		return true;
	}
}

?>