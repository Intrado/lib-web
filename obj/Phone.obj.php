<?

class Phone extends DBMappedObject {

	var $personid;
	var $phone;
	var $sequence;
	var $editlock;

	function Phone ($id = NULL) {
		$this->_tablename = "phone";
		$this->_fieldlist = array("personid", "phone", "sequence","editlock");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	static function format ($phone) {
		if (strlen($phone) == 10)
			return "(" . substr($phone,0,3) . ") " . substr($phone,3,3) . "-" . substr($phone,6,4);
		else if (strlen($phone) == 7)
			return  substr($phone,0,3) . "-" . substr($phone,3,4);
		else
			return $phone;
	}

	static function parse ($phone) {
		return ereg_replace("[^0-9]*","",$phone);
	}

	static function validateEasyCall($phone) {
		global $IS_COMMSUITE;

		if ($IS_COMMSUITE || getSystemSetting('_dmmethod') != 'asp') {
			$min = getSystemSetting('easycallmin', 10);
			$max = getSystemSetting('easycallmax', 10);
		} else {
			$min = 10;
			$max = 10;
		}
		return validate($phone, $min, $max);
	}

	static function validate ($phone, $min=10, $max=10) {
		$phone = Phone::parse($phone);
		$length = strlen($phone);
		$error=array();
		if(!(($length >= $min && $length <= $max) || $length == 10)){
			if($min == $max) {
				if($max == 10) {
					$error[] = 'The phone number must be exactly 10 digits long (including area code)';
					$error[] = 'You do not need to include a 1 for long distance';
				} else {
					$error[] = 'The phone number must be '. $max .' or 10 digits long (including area code)';
					$error[] = 'You do not need to include a 1 for long distance';
				}
			} else {
				if($max == 10 || $max == 9) {
					$error[] = 'The phone number must be '. $min .'-10 digits long (including area code)';
					$error[] = 'You do not need to include a 1 for long distance';
				} else {
					$error[] = 'The phone number must be '. $min .' - '. $max .' digits or exactly 10 digits long (including area code)';
					$error[] = 'You do not need to include a 1 for long distance';
				}
			}
		} else if ($length == 10) {
			if ($phone[0] < 2 || $phone[3] < 2) //check for valid looking area code and prefix
				$error[] = 'The phone number seems to be invalid';
		}
		return $error;
	}
}

?>