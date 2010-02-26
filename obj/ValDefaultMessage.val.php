<?
class ValDefaultMessage extends Validator {
	var $onlyserverside = true;
	
	// required $args['messagegroup'], a MessageGroup object.
	// required $args['type'], the message's type.
	// required $args['subtype'], the message's subtype.
	// required $args['requesteddefaultlanguagecode'], validate against the requested default language code
	// optional $args['autotranslator'], a boolean indicating that this message is in the autotranslator.
	// so that the message group's default language code does not change until this message has been validated.
	// optional $args['translationlanguages'], required only if $args['autotranslator'] is specified.
	function validate ($value, $args, $requiredvalues) {
		$messagegroup = $args['messagegroup'];
		$inautotranslator = isset($args['autotranslator']) && $args['autotranslator'];
		
		$errormessagecreatefirst = _L("Please first create the %s message.", Language::getName($args['requesteddefaultlanguagecode']));
		
		$hasmessage = $messagegroup->hasMessage($args['type'], $args['subtype']);
		$hasdefault = $hasmessage ? $messagegroup->hasMessage($args['type'], $args['subtype'], $args['requesteddefaultlanguagecode']) : false;
		
		if (!$hasmessage && $inautotranslator && !in_array($args['requesteddefaultlanguagecode'], $args['translationlanguages']))
			return $errormessagecreatefirst;
		
		if ($args['languagecode'] == $args['requesteddefaultlanguagecode'] || $hasdefault)
			return true;
		
		// Unless the user is editing the default message or there are no messages, show an error if the messagegroup has no default message.
		if ($inautotranslator) { // For autotranslator, $requiredvalues are jsonencoded from TranslationItem.
			if (empty($requiredvalues))
				return $errormessagecreatefirst;
			
			if (!in_array($args['requesteddefaultlanguagecode'], $args['translationlanguages']))
				return $errormessagecreatefirst;
			
			$editingdefault = false;
			foreach ($requiredvalues as $value) {
				$translationitemdata = json_decode($value);
				if ($translationitemdata && isset($translationitemdata->language) && $translationitemdata->language == $args['requesteddefaultlanguagecode'] && $translationitemdata->enabled) {
					$editingdefault = true;
					break;
				}
			}
			
			if (!$editingdefault)
				return _L("Please include %s or first create its message separately.", Language::getName($args['requesteddefaultlanguagecode']));
		} else if($args['languagecode'] != $args['requesteddefaultlanguagecode']) {
			// It's ok for the default html message to be blank if there's a corresponding plain message.
			if (!($args['subtype'] == 'html' && $messagegroup->hasMessage('email', 'plain', $args['requesteddefaultlanguagecode'])))
				return $errormessagecreatefirst;
		}
	}
}

?>