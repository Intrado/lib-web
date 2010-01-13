<?
class ValMessageBody extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		if (isset($_SESSION['messagegroupid'])) {
			$messagegroup = new MessageGroup($_SESSION['messagegroupid']);
			$errormessagecreatefirst = _L("Please first create the %s message.", Language::getName($messagegroup->defaultlanguagecode));
			// Unless the user is editing the default message, show an error if the messagegroup has no default message.
			if (!$messagegroup->hasDefaultMessage($args['type'], $args['subtype'])) {
				if ($args['languagecode'] == 'autotranslator') { // For autotranslator, $requiredvalues are jsonencoded from TranslationItem.
					if (empty($requiredvalues))
						return $errormessagecreatefirst;
				
					$editingdefault = false;
					$defaultincluded = false;
					foreach ($requiredvalues as $value) {
						$translationitemdata = json_decode($value);
						if ($translationitemdata->language == $messagegroup->defaultlanguagecode) {
							$defaultincluded = true;
							if ($translationitemdata->enabled) {
								$editingdefault = true;
								break;
							}
						}
					}
					if (!$defaultincluded)
						return $errormessagecreatefirst;
					if (!$editingdefault)
						return _L("Please include %s or first create its message separately.", Language::getName($messagegroup->defaultlanguagecode));
				} else if ($args['languagecode'] != $messagegroup->defaultlanguagecode) {
					return $errormessagecreatefirst;
				}
			}
		}
		
		if (!empty($args['translationitem'])) {
			$msgdata = json_decode($value);
			if ($msgdata->override || !$msgdata->enabled)
				$text = $msgdata->text;
			else if ($msgdata->enabled && !$msgdata->override)
				$text = $msgdata->englishText;
		} else {
			$text = $value;
		}
		
		$message = new Message();
		$errors = array();	
		$parts = $message->parse($text,$errors);  // Fill in with voice id later
		if (count($errors) > 0)	{
			$str = "There was an error parsing the message: ";
			foreach($errors as $error)
			{
				$str .= "\n" . $error;
			}
			
			return $str;
		}
		
		if (isset($args['maximages'])) {
			$imagecount = 0;
			foreach ($parts as $part) {
				if ($part->type == 'I')
					$imagecount++;
				if ($imagecount > $args['maximages'])
					return _L('You may only include %s images.', $args['maximages']);
			}
		}
		
		return true;
	}
}
?>