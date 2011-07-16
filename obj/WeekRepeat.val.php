<?
class ValWeekRepeatItem extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $ACCESS;

		if(!is_array($value)) {
			$value = json_decode($value);
		}
		
		$callearly = $ACCESS->getValue('callearly', '12:00 AM');
		$calllate = $ACCESS->getValue('calllate', '11:59 PM');

		if(count($value) != 8 )
			return _L('An error occurred, try reloading the page'); // decoding problem or anything else
		$time = strtotime($value[7]);
		if($time < strtotime($callearly))
			return _L('Time cannot be before %1$s',$callearly);
		else if($time > strtotime($calllate))
			return _L('Time cannot be after %1$s',$calllate);
		for($i = 0;$i < 7;$i++){
			if(!is_bool($value[$i]))
				return _L('Invalid Input');
		}
		return true;
	}
}
?>