<?
class ValMessageBody extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		if (isset($args['messagegroup']) && $args['messagegroup']) {
			$messagegroup = $args['messagegroup'];
			$errormessagecreatefirst = _L("Please first create the %s message.", Language::getName($messagegroup->defaultlanguagecode));
			
			$hasmessage = $messagegroup->hasMessage($args['type'], $args['subtype']);
			$hasdefault = $hasmessage ? $messagegroup->hasDefaultMessage($args['type'], $args['subtype']) : false;
			
			if (!$hasmessage && $args['languagecode'] == 'autotranslator' &&
				!in_array($messagegroup->defaultlanguagecode, $args['translationlanguages'])
			) {
				return $errormessagecreatefirst;
			}

			// Unless the user is editing the default message or there are no messages, show an error if the messagegroup has no default message.
			if (!$hasdefault) {
				if ($args['languagecode'] == 'autotranslator') { // For autotranslator, $requiredvalues are jsonencoded from TranslationItem.
					if (empty($requiredvalues)) {
						return $errormessagecreatefirst;
					}
					
					if (!in_array($messagegroup->defaultlanguagecode, $args['translationlanguages'])) {
						return $errormessagecreatefirst;
					}
					
					$editingdefault = false;
					foreach ($requiredvalues as $value) {
						$translationitemdata = json_decode($value);
						if ($translationitemdata &&
							isset($translationitemdata->language) &&
							$translationitemdata->language == $messagegroup->defaultlanguagecode &&
							$translationitemdata->enabled
						) {
							$editingdefault = true;
							break;
						}
					}
					
					if (!$editingdefault)
						return _L("Please include %s or first create its message separately.", Language::getName($messagegroup->defaultlanguagecode));
				} else if($args['languagecode'] != $messagegroup->defaultlanguagecode) {
					// It's ok for the default html message to be blank if there's a corresponding plain message.
					if (!($args['subtype'] == 'html' && $messagegroup->hasDefaultMessage('email', 'plain'))) {
						return $errormessagecreatefirst;
					}
				}
			}
		}
		
		if (!empty($args['translationitem'])) {
			$translationdata = json_decode($value);
			if ($translationdata->override || !$translationdata->enabled)
				$text = $translationdata->text;
			else if ($translationdata->enabled && !$translationdata->override)
				$text = $translationdata->englishText;
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
		
		if (isset($args['type']) && $args['type'] == 'phone') {
			if ((isset($translationdata) && $translationdata->enabled) || (isset($args['languagecode']) && $args['languagecode'] == 'autotranslator')) {
				foreach ($parts as $part) {
					if ($part->type == 'A')
						return _L('Translation messages may not have audio parts.');
				}
			}
		} else if (isset($args['maximages'])) {
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