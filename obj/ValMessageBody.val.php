<?
class ValMessageBody extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		if (isset($_SESSION['messagegroupid'])) {
			$messagegroup = new MessageGroup($_SESSION['messagegroupid']);
			// Unless the user is editing the default message, show an error if the messagegroup has no default message.
			if ($args['languagecode'] != $messagegroup->defaultlanguagecode && !$messagegroup->hasDefaultMessage($args['type'], $args['subtype']))
				return _L("Please first create the %s message.", Language::getName($messagegroup->defaultlanguagecode));
		}
		
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