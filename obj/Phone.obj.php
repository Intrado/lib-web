<?

class Phone extends DBMappedObject {

	var $personid;
	var $phone;
	var $sequence;
	var $editlock;
	var $editlockdate;
	
	function Phone ($id = NULL) {
		$this->_tablename = "phone";
		$this->_allownulls = true;
		$this->_fieldlist = array("personid", "phone", "sequence","editlock","editlockdate");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	function update ($specificfields = NULL, $updatechildren = false) {
		if (isset($this->id)) {
			$originalObject = new Phone($this->id);
			if (($originalObject->phone != $this->phone) ||
				($originalObject->editlock != $this->editlock)) {
					if ($this->editlock)
						$this->editlockdate = date("Y-m-d H:i:s", time());
					else
						$this->editlockdate = null;
			}
		} else {
					if ($this->editlock)
						$this->editlockdate = date("Y-m-d H:i:s", time());
					else
						$this->editlockdate = null;
		}
		DBMappedObject::update($specificfields, $updatechildren);
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

		if (getSystemSetting('_dmmethod') != 'asp') {
			$min = getSystemSetting('easycallmin', 10);
			$max = getSystemSetting('easycallmax', 10);
		} else {
			$min = 10;
			$max = 10;
		}
		$phone = Phone::parse($phone);
		$length = strlen($phone);
		$error = array();
		if (!(($length >= $min && $length <= $max) || $length == 10)) {
			if ($min == $max) {
				if ($max == 10) {
					$error[] = 'The phone number must be exactly 10 digits long (including area code)';
					$error[] = 'You do not need to include a 1 for long distance';
				} else {
					$error[] = 'The phone number must be '. $max .' or 10 digits long (including area code)';
					$error[] = 'You do not need to include a 1 for long distance';
				}
			} else {
				if ($max == 10 || $max == 9) {
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

	static function validate ($phone) {
		$phone = Phone::parse($phone);
		$length = strlen($phone);
		$error = array();
		if ($length == 10) {
			$areacode = $phone[0].$phone[1].$phone[2];
			$prefix = $phone[3].$phone[4].$phone[5];
			
			// based on North American Numbering Plan
			// read more at en.wikipedia.org/wiki/List_of_NANP_area_codes

			if (($phone[0] < 2) || // areacode cannot start with 0 or 1
				($phone[3] < 2) || // prefix cannot start with 0 or 1
				($phone[1] == 1 && $phone[2] == 1) || // areacode cannot be N11
				($phone[4] == 1 && $phone[5] == 1) || // prefix cannot be N11
				($areacode == 555) || // areacode cannot be 555
				($prefix == 555) // prefix cannot be 555
				) {
				// check special case N11 prefix with toll-free area codes
				// en.wikipedia.org/wiki/Toll-free_telephone_number
				if (($phone[4] == 1 && $phone[5] == 1) && (
					($areacode == '800') ||
					($areacode == '888') ||
					($areacode == '877') ||
					($areacode == '866') ||
					($areacode == '855') ||
					($areacode == '844') ||
					($areacode == '833') ||
					($areacode == '822') ||
					($areacode == '880') ||
					($areacode == '881') ||
					($areacode == '882') ||
					($areacode == '883') ||
					($areacode == '884') ||
					($areacode == '885') ||
					($areacode == '886') ||
					($areacode == '887') ||
					($areacode == '888') ||
					($areacode == '889')
					)) {
					return array(); // OK special case
				}
				$error[] = 'The phone number seems to be invalid';
			}
		} else {
				$error[] = 'The phone number must be exactly 10 digits long (including area code)';
				$error[] = 'You do not need to include a 1 for long distance';
		}
		return $error;
	}
}

?>