<?
class ValMessageBody extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$message = new Message();
		$errors = array();	
		$message->parse($value,$errors);  // Fill in with voice id later
		if (count($errors) > 0)	{			
			$str = "There was an error parsing the message: ";
			foreach($errors as $error)
			{
				$str .= "\n" . $error;
			}
			
			return $str;
		} else {
			return true;
		}
	}
}
?>