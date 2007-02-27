<?

class Phone extends DBMappedObject {

	var $personid;
	var $phone;
	var $sequence;

	function Phone ($id = NULL) {
		$this->_tablename = "phone";
		$this->_fieldlist = array("personid", "phone", "sequence");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function format ($phone) {
		if (strlen($phone) == 10)
			return "(" . substr($phone,0,3) . ") " . substr($phone,3,3) . "-" . substr($phone,6,4);
		else if (strlen($phone) == 7)
			return  substr($phone,0,3) . "-" . substr($phone,3,4);
		else
			return $phone;
	}

	function parse ($phone) {
		return ereg_replace("[^0-9]*","",$phone);
	}

	function validate ($phone, $iseasycall = false) {
		global $IS_COMMSUITE;
		
		$phone = Phone::parse($phone);
		if ($iseasycall) {
			$min = getSystemSetting('easycallmin', $IS_COMMSUITE ? 2 : 10);
			$max = getSystemSetting('easycallmax', 10);
		} else {
			$min = getSystemSetting('easycallmin', $IS_COMMSUITE ? 2 : 10);
			$max = getSystemSetting('easycallmax', $IS_COMMSUITE ? 6 : 10);
		}
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
		}
		return $error;
	}
}

?>