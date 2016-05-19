<?

class PhoneMessageRecorderValidator extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;


		if (!$USER->authorize("starteasy"))
			return _L('%1$s is not allowed for this user account',$this->label);
		$values = json_decode($value);
		if ($values == null || $value == '{}')
			return _L('%1$s does not have a message recorded', $this->label);

		$languages = Language::getLanguageMap();
		foreach ($values as $langCode => $audiofileId) {
			// check if this message is being "deleted" and accept it
			if ($langCode == "delete")
				continue;
			// otherwise, assert that the language code is valid, and the user has access to the audio file
			if (!isset($languages[$langCode]) || !userCanSee("audiofile", $audiofileId))
				return _L('%1$s has an invalid or missing message', $this->label);
		}

		return true;
	}
}
?>